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
     * Net profit for period: income (credit−debit) − expenses (debit−credit).
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
            $debit = $this->periodDebit([(int) $id]);
            $credit = $this->periodCredit([(int) $id]);
            $type = strtolower((string) ($acc->account_primary_type ?? $acc->account_type ?? ''));
            $gl = preg_replace('/[^0-9]/', '', (string) ($acc->gl_code ?? '')) ?? '';

            $isIncome = in_array($type, ['income'], true) || ($gl !== '' && str_starts_with($gl, '4'));
            $isExpense = in_array($type, ['expenses', 'expense'], true) || ($gl !== '' && str_starts_with($gl, '5'));

            if ($isIncome) {
                $income += ($credit - $debit);
            } elseif ($isExpense) {
                $expense += ($debit - $credit);
            } else {
                // Equity plugs like 32201 current-year profit: treat credit-normal as income-like.
                $income += ($credit - $debit);
            }
        }

        return round($income - $expense, 2);
    }

    /**
     * Non-cash charge add-back (typically expense debit net for the period).
     *
     * @param  list<int>  $accountIds
     * @param  Collection<int, object>  $accountsById
     */
    public function periodAdjustment(array $accountIds, Collection $accountsById): float
    {
        $total = 0.0;
        foreach ($accountIds as $id) {
            $acc = $accountsById->get((int) $id);
            $type = strtolower((string) ($acc->account_primary_type ?? $acc->account_type ?? ''));
            $gl = preg_replace('/[^0-9]/', '', (string) ($acc->gl_code ?? '')) ?? '';
            $debit = $this->periodDebit([(int) $id]);
            $credit = $this->periodCredit([(int) $id]);

            // Prefer P&L expense accounts (5x / 54) for add-backs; skip pure contra-asset accumulators when both matched.
            $isExpense = in_array($type, ['expenses', 'expense'], true) || ($gl !== '' && str_starts_with($gl, '5'));
            if ($isExpense) {
                $total += max(0, $debit - $credit);
            }
        }

        // Fallback: if only asset contra codes matched, use period credit on them (accum. depreciation increase).
        if (abs($total) < 0.0001) {
            foreach ($accountIds as $id) {
                $credit = $this->periodCredit([(int) $id]);
                $debit = $this->periodDebit([(int) $id]);
                $total += max(0, $credit - $debit);
            }
        }

        return round($total, 2);
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
