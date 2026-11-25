<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Support\CashFlowFormula;

/**
 * Computes cash-flow line amounts from COA balances / period activity.
 * Same formula for every period — only the date window changes.
 */
final class CashFlowAmountEngine
{
    /** @var array<int, float> */
    private array $openCache = [];

    /** @var array<int, float> */
    private array $closeCache = [];

    /** @var array<int, float> */
    private array $periodDebitCache = [];

    /** @var array<int, float> */
    private array $periodCreditCache = [];

    /**
     * @param  list<int|string>  $costCenterIds
     */
    public function __construct(
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly array $costCenterIds = [],
    ) {}

    /**
     * Natural BS/IS signed balance as of a date (assets/expenses positive on debit).
     *
     * @param  list<int>  $accountIds
     */
    public function balanceAsOf(array $accountIds, string $asOfDate): float
    {
        $accountIds = array_values(array_unique(array_filter(array_map('intval', $accountIds))));
        if ($accountIds === []) {
            return 0.0;
        }

        $cacheKey = $asOfDate === $this->startDate
            ? 'open'
            : ($asOfDate === $this->endDate ? 'close' : 'asof:'.$asOfDate);

        $sum = 0.0;
        $missing = [];
        foreach ($accountIds as $id) {
            if ($cacheKey === 'open' && array_key_exists($id, $this->openCache)) {
                $sum += $this->openCache[$id];
            } elseif ($cacheKey === 'close' && array_key_exists($id, $this->closeCache)) {
                $sum += $this->closeCache[$id];
            } else {
                $missing[] = $id;
            }
        }

        if ($missing !== []) {
            $rows = $this->querySignedBalances($missing, $asOfDate);
            foreach ($missing as $id) {
                $bal = (float) ($rows[$id] ?? 0.0);
                if ($cacheKey === 'open') {
                    $this->openCache[$id] = $bal;
                } elseif ($cacheKey === 'close') {
                    $this->closeCache[$id] = $bal;
                }
                $sum += $bal;
            }
        }

        return round($sum, 2);
    }

    /**
     * Opening = balance at start of day before period start (exclusive of period).
     *
     * @param  list<int>  $accountIds
     */
    public function openingBalance(array $accountIds): float
    {
        // Transactions on start_date belong to the period; opening is everything strictly before.
        return $this->balanceBefore($accountIds, $this->startDate);
    }

    /**
     * @param  list<int>  $accountIds
     */
    public function closingBalance(array $accountIds): float
    {
        return $this->balanceAsOf($accountIds, $this->endDate);
    }

    /**
     * @param  list<int>  $accountIds
     */
    public function periodDebit(array $accountIds): float
    {
        return $this->periodSide($accountIds, 'debit');
    }

    /**
     * @param  list<int>  $accountIds
     */
    public function periodCredit(array $accountIds): float
    {
        return $this->periodSide($accountIds, 'credit');
    }

    /**
     * Net profit for period from ALL income & expense accounts (not equity plugs).
     * Prior-period adjustment accounts are excluded — they have their own line.
     *
     * @param  Collection<int, object>  $allAccounts
     */
    public function periodAllPlNet(Collection $allAccounts): float
    {
        $incomeIds = [];
        $expenseIds = [];
        foreach ($allAccounts as $acc) {
            if ($this->isPriorPeriodAccount($acc)) {
                continue;
            }
            if ($this->isIncomeAccount($acc)) {
                $incomeIds[] = (int) $acc->id;
            } elseif ($this->isExpenseAccount($acc)) {
                $expenseIds[] = (int) $acc->id;
            }
        }

        $byId = $allAccounts->keyBy(fn ($a) => (int) $a->id);
        $income = 0.0;
        foreach ($incomeIds as $id) {
            $income += $this->periodCredit([$id]) - $this->periodDebit([$id]);
        }
        $expense = 0.0;
        foreach ($expenseIds as $id) {
            $expense += $this->periodDebit([$id]) - $this->periodCredit([$id]);
        }

        return round($income - $expense, 2);
    }

    /**
     * Net profit for period: income (credit−debit) − expenses (debit−credit).
     * Skips non-P&L accounts (equity plugs, assets, liabilities).
     *
     * @param  list<int>  $accountIds
     * @param  Collection<int, object>  $accountsById
     */
    public function periodPlNet(array $accountIds, Collection $accountsById): float
    {
        $income = 0.0;
        $expense = 0.0;
        foreach ($accountIds as $id) {
            $acc = $accountsById->get((int) $id);
            if (! $acc || $this->isPriorPeriodAccount($acc)) {
                continue;
            }
            $debit = $this->periodDebit([(int) $id]);
            $credit = $this->periodCredit([(int) $id]);

            if ($this->isIncomeAccount($acc)) {
                $income += ($credit - $debit);
            } elseif ($this->isExpenseAccount($acc)) {
                $expense += ($debit - $credit);
            }
        }

        return round($income - $expense, 2);
    }

    /**
     * Period expense charge only (مصروف) — never accumulated / allowance BS credits.
     *
     * @param  list<int>  $accountIds
     * @param  Collection<int, object>  $accountsById
     */
    public function periodAdjustment(array $accountIds, Collection $accountsById): float
    {
        $total = 0.0;
        foreach ($accountIds as $id) {
            $acc = $accountsById->get((int) $id);
            if (! $acc || ! $this->isExpenseAccount($acc)) {
                continue;
            }
            if ($this->isAccumulatedOrAllowanceName($acc)) {
                continue;
            }
            $debit = $this->periodDebit([(int) $id]);
            $credit = $this->periodCredit([(int) $id]);
            $total += max(0, $debit - $credit);
        }

        return round($total, 2);
    }

    /**
     * Prior-period adjustments: period amount of the TB account named «تعديلات سنوات سابقة».
     * Missing account or ~0 activity → 0. Not merged into P&L net.
     *
     * Equity-nature signed period movement (credit − debit), matching TB credit/debit columns.
     *
     * @param  list<int>  $accountIds
     */
    public function periodPriorPeriodAmount(array $accountIds): float
    {
        if ($accountIds === []) {
            return 0.0;
        }

        $net = round($this->periodCredit($accountIds) - $this->periodDebit($accountIds), 2);

        return abs($net) < 0.005 ? 0.0 : $net;
    }

    /**
     * @param  list<int>  $accountIds
     */
    public function compute(string $formula, array $accountIds, Collection $accountsById, float $relatedDepreciation = 0.0): float
    {
        $open = $this->openingBalance($accountIds);
        $close = $this->closingBalance($accountIds);

        return match ($formula) {
            CashFlowFormula::PL_NET => $this->periodPlNet($accountIds, $accountsById),
            CashFlowFormula::ADJUSTMENT => $this->periodAdjustment($accountIds, $accountsById),
            CashFlowFormula::PRIOR_PERIOD => $this->periodPriorPeriodAmount($accountIds),
            CashFlowFormula::WC_ASSET, CashFlowFormula::INVESTING_ASSET => round($open - $close, 2),
            CashFlowFormula::WC_LIABILITY, CashFlowFormula::FINANCING_INFLOW => round($close - $open, 2),
            CashFlowFormula::INVESTING_CAPEX => round(-(($close - $open) + max(0, $relatedDepreciation)), 2),
            CashFlowFormula::INVESTING_PROCEEDS => round(max(0, $this->periodCredit($accountIds) - $this->periodDebit($accountIds)), 2),
            CashFlowFormula::FINANCING_OUTFLOW => round(-max(0, $this->periodDebit($accountIds) - $this->periodCredit($accountIds)), 2),
            CashFlowFormula::CASH_OPEN => $open,
            CashFlowFormula::CASH_CLOSE => $close,
            default => 0.0,
        };
    }

    public function isIncomeAccount(object $acc): bool
    {
        $type = strtolower((string) ($acc->account_primary_type ?? $acc->account_type ?? ''));
        if ($type === 'income') {
            return true;
        }
        $gl = preg_replace('/[^0-9]/', '', (string) ($acc->gl_code ?? '')) ?? '';

        return $gl !== '' && str_starts_with($gl, '4');
    }

    public function isExpenseAccount(object $acc): bool
    {
        $type = strtolower((string) ($acc->account_primary_type ?? $acc->account_type ?? ''));
        if (in_array($type, ['expenses', 'expense'], true)) {
            return true;
        }
        $gl = preg_replace('/[^0-9]/', '', (string) ($acc->gl_code ?? '')) ?? '';

        return $gl !== '' && str_starts_with($gl, '5');
    }

    public function isPriorPeriodAccount(object $acc): bool
    {
        $gl = preg_replace('/[^0-9]/', '', (string) ($acc->gl_code ?? '')) ?? '';
        if ($gl !== '' && str_starts_with($gl, '324')) {
            return true;
        }
        $hay = mb_strtolower(trim(($acc->name_ar ?? '').' '.($acc->name_en ?? '')));
        $hay = str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $hay);

        return str_contains($hay, 'تعديلات سنوات سابقه')
            || str_contains($hay, 'prior period');
    }

    public function isAccumulatedOrAllowanceName(object $acc): bool
    {
        $hay = mb_strtolower(trim(($acc->name_ar ?? '').' '.($acc->name_en ?? '')));
        $hay = str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $hay);

        return str_contains($hay, 'مجمع')
            || str_contains($hay, 'accumulated')
            || (str_contains($hay, 'مخصص') && ! str_contains($hay, 'مصروف'));
    }

    /**
     * @param  list<int>  $accountIds
     */
    private function balanceBefore(array $accountIds, string $startDate): float
    {
        $accountIds = array_values(array_unique(array_filter(array_map('intval', $accountIds))));
        if ($accountIds === []) {
            return 0.0;
        }

        $missing = [];
        $sum = 0.0;
        foreach ($accountIds as $id) {
            if (array_key_exists($id, $this->openCache)) {
                $sum += $this->openCache[$id];
            } else {
                $missing[] = $id;
            }
        }

        if ($missing !== []) {
            $rows = $this->querySignedBalancesBefore($missing, $startDate);
            foreach ($missing as $id) {
                $bal = (float) ($rows[$id] ?? 0.0);
                $this->openCache[$id] = $bal;
                $sum += $bal;
            }
        }

        return round($sum, 2);
    }

    /**
     * @param  list<int>  $accountIds
     * @return array<int, float>
     */
    private function querySignedBalances(array $accountIds, string $asOfDate): array
    {
        $expr = $this->signedBalanceExpression();
        $q = DB::table('accounting_accounts')
            ->leftJoin('accounting_accounts_transactions as AAT', function ($join) use ($asOfDate) {
                $join->on('AAT.accounting_account_id', '=', 'accounting_accounts.id')
                    ->whereDate('AAT.operation_date', '<=', $asOfDate);
                if ($this->costCenterIds !== []) {
                    $join->whereIn('AAT.cost_center_id', $this->costCenterIds);
                }
            })
            ->whereIn('accounting_accounts.id', $accountIds)
            ->groupBy('accounting_accounts.id', 'accounting_accounts.account_primary_type', 'accounting_accounts.gl_code')
            ->select('accounting_accounts.id', DB::raw($expr.' as balance'));

        return $q->pluck('balance', 'id')->map(fn ($v) => round((float) $v, 2))->all();
    }

    /**
     * @param  list<int>  $accountIds
     * @return array<int, float>
     */
    private function querySignedBalancesBefore(array $accountIds, string $startDate): array
    {
        $expr = $this->signedBalanceExpression();
        $q = DB::table('accounting_accounts')
            ->leftJoin('accounting_accounts_transactions as AAT', function ($join) use ($startDate) {
                $join->on('AAT.accounting_account_id', '=', 'accounting_accounts.id')
                    ->whereDate('AAT.operation_date', '<', $startDate);
                if ($this->costCenterIds !== []) {
                    $join->whereIn('AAT.cost_center_id', $this->costCenterIds);
                }
            })
            ->whereIn('accounting_accounts.id', $accountIds)
            ->groupBy('accounting_accounts.id', 'accounting_accounts.account_primary_type', 'accounting_accounts.gl_code')
            ->select('accounting_accounts.id', DB::raw($expr.' as balance'));

        return $q->pluck('balance', 'id')->map(fn ($v) => round((float) $v, 2))->all();
    }

    private function signedBalanceExpression(): string
    {
        $debitMinusCredit = '(
            COALESCE(SUM(CASE WHEN AAT.type = \'debit\' THEN AAT.amount ELSE 0 END), 0)
            - COALESCE(SUM(CASE WHEN AAT.type = \'credit\' THEN AAT.amount ELSE 0 END), 0)
        )';

        return "($debitMinusCredit) * CASE
            WHEN accounting_accounts.account_primary_type = 'asset' THEN 1
            WHEN accounting_accounts.account_primary_type IN ('liability', 'liabilities', 'equity') THEN -1
            WHEN accounting_accounts.account_primary_type = 'income' THEN -1
            WHEN accounting_accounts.account_primary_type IN ('expenses', 'expense') THEN 1
            WHEN LEFT(REGEXP_REPLACE(accounting_accounts.gl_code, '[^0-9]', ''), 1) = '1' THEN 1
            WHEN LEFT(REGEXP_REPLACE(accounting_accounts.gl_code, '[^0-9]', ''), 1) IN ('2','3') THEN -1
            WHEN LEFT(REGEXP_REPLACE(accounting_accounts.gl_code, '[^0-9]', ''), 1) = '4' THEN -1
            WHEN LEFT(REGEXP_REPLACE(accounting_accounts.gl_code, '[^0-9]', ''), 1) = '5' THEN 1
            ELSE 1
        END";
    }

    /**
     * @param  list<int>  $accountIds
     */
    private function periodSide(array $accountIds, string $side): float
    {
        $accountIds = array_values(array_unique(array_filter(array_map('intval', $accountIds))));
        if ($accountIds === []) {
            return 0.0;
        }

        $cache = $side === 'debit' ? $this->periodDebitCache : $this->periodCreditCache;
        $sum = 0.0;
        $missing = [];
        foreach ($accountIds as $id) {
            if (array_key_exists($id, $cache)) {
                $sum += $cache[$id];
            } else {
                $missing[] = $id;
            }
        }

        if ($missing !== []) {
            $rows = DB::table('accounting_accounts_transactions as AAT')
                ->whereIn('AAT.accounting_account_id', $missing)
                ->whereBetween('AAT.operation_date', [$this->startDate, $this->endDate])
                ->when($this->costCenterIds !== [], fn ($q) => $q->whereIn('AAT.cost_center_id', $this->costCenterIds))
                ->groupBy('AAT.accounting_account_id')
                ->select(
                    'AAT.accounting_account_id as id',
                    DB::raw("COALESCE(SUM(CASE WHEN AAT.type = 'debit' THEN AAT.amount ELSE 0 END), 0) as debit_amount"),
                    DB::raw("COALESCE(SUM(CASE WHEN AAT.type = 'credit' THEN AAT.amount ELSE 0 END), 0) as credit_amount")
                )
                ->get()
                ->keyBy('id');

            foreach ($missing as $id) {
                $row = $rows->get($id);
                $debit = round((float) ($row->debit_amount ?? 0), 2);
                $credit = round((float) ($row->credit_amount ?? 0), 2);
                $this->periodDebitCache[$id] = $debit;
                $this->periodCreditCache[$id] = $credit;
                $sum += $side === 'debit' ? $debit : $credit;
            }
        }

        return round($sum, 2);
    }
}
