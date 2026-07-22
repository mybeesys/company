<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use App\Helpers\CurrencyHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\FinancialYear;

final class TrialBalanceReportService
{
    public const PRIMARY_TYPE_ORDER = [
        'asset',
        'liabilities',
        'equity',
        'income',
        'expenses',
    ];

    /** @var array<string, string> */
    public const CHART_COLORS = [
        '#1B84FF',
        '#17C653',
        '#F6C000',
        '#7239EA',
        '#F8285A',
        '#50CD89',
    ];

    /** @return array{closing_debit_balance: float, closing_credit_balance: float} */
    public static function closingBalance(object $account): array
    {
        $debitSide = round((float) ($account->debit_opening_balance ?? 0) + (float) ($account->debit_balance ?? 0), 2);
        $creditSide = round((float) ($account->credit_opening_balance ?? 0) + (float) ($account->credit_balance ?? 0), 2);
        $closing = round($creditSide - $debitSide, 2);

        return [
            'closing_debit_balance' => $closing < 0 ? round(abs($closing), 2) : 0.0,
            'closing_credit_balance' => $closing >= 0 ? $closing : 0.0,
        ];
    }

    /**
     * Period movement net only (does NOT include opening).
     *
     * @return array{period_net: float, period_debit_net: float, period_credit_net: float, balance_type: string}
     */
    public static function periodMovement(object $account): array
    {
        $debit = round((float) ($account->debit_balance ?? 0), 2);
        $credit = round((float) ($account->credit_balance ?? 0), 2);
        $net = round($debit - $credit, 2);

        return [
            'period_net' => abs($net),
            'period_debit_net' => $net > 0.0001 ? abs($net) : 0.0,
            'period_credit_net' => $net < -0.0001 ? abs($net) : 0.0,
            'balance_type' => static::balanceTypeLabel(
                $net > 0.0001 ? abs($net) : 0.0,
                $net < -0.0001 ? abs($net) : 0.0
            ),
        ];
    }

    public static function balanceTypeLabel(float $debit, float $credit): string
    {
        $diff = round($debit - $credit, 2);
        if (abs($diff) < 0.005) {
            return __('accounting::lang.tb_balance_type_zero');
        }

        return $diff > 0
            ? __('accounting::lang.tb_balance_type_debit')
            : __('accounting::lang.tb_balance_type_credit');
    }

    public static function signedClosing(object $account): float
    {
        $c = static::closingBalance($account);

        return round((float) $c['closing_credit_balance'] - (float) $c['closing_debit_balance'], 2);
    }

    public static function isPlAccount(object $account): bool
    {
        $type = static::normalizePrimaryType((string) ($account->account_primary_type ?? ''));
        if (in_array($type, ['income', 'expenses'], true)) {
            return true;
        }

        $gl = preg_replace('/[^0-9]/', '', (string) ($account->gl_code ?? '')) ?? '';

        return $gl !== '' && in_array($gl[0], ['4', '5'], true);
    }

    public static function hasNonZeroOpening(object $account): bool
    {
        return abs((float) ($account->debit_opening_balance ?? 0)) > 0.0001
            || abs((float) ($account->credit_opening_balance ?? 0)) > 0.0001;
    }

    /**
     * Detect P&L opening residuals when the report starts at a fiscal year start.
     *
     * @return array{
     *     show_warning: bool,
     *     pl_opening_count: int,
     *     message: string|null,
     *     close_url: string|null,
     *     prior_year_id: int|null
     * }
     */
    public static function plOpeningWarning(Collection $rows, string $startDate): array
    {
        $empty = [
            'show_warning' => false,
            'pl_opening_count' => 0,
            'message' => null,
            'close_url' => null,
            'prior_year_id' => null,
        ];

        $plWithOpening = $rows->filter(
            fn ($account) => ! ($account->is_group ?? false)
                && static::isPlAccount($account)
                && static::hasNonZeroOpening($account)
        );

        if ($plWithOpening->isEmpty()) {
            return $empty;
        }

        $priorYear = null;
        if (Schema::hasTable('financial_years')) {
            $year = FinancialYear::query()
                ->whereDate('start_date', $startDate)
                ->first();

            if ($year) {
                $priorYear = FinancialYear::query()
                    ->whereDate('end_date', '<', $startDate)
                    ->orderByDesc('end_date')
                    ->first();
            }
        }

        $closeUrl = null;
        $priorYearId = null;
        if ($priorYear) {
            $priorYearId = (int) $priorYear->id;
            try {
                $closeUrl = route('accounting.financial-years.accounting-close.page', $priorYearId);
            } catch (\Throwable) {
                $closeUrl = url('accounting/financial-years/'.$priorYearId.'/accounting-close');
            }
        }

        return [
            'show_warning' => true,
            'pl_opening_count' => $plWithOpening->count(),
            'message' => __('accounting::lang.tb_pl_opening_warning', [
                'count' => $plWithOpening->count(),
            ]),
            'close_url' => $closeUrl,
            'prior_year_id' => $priorYearId,
        ];
    }

    /**
     * Insert accordion group header rows before each primary-type block.
     *
     * @return Collection<int, object>
     */
    public static function withAccordionGroups(Collection $rows): Collection
    {
        $sorted = $rows->sortBy(function ($account) {
            $type = static::normalizePrimaryType((string) ($account->account_primary_type ?? 'other'));
            $order = array_search($type, self::PRIMARY_TYPE_ORDER, true);
            $orderKey = $order === false ? 99 : $order;

            return sprintf('%02d-%s', $orderKey, (string) ($account->gl_code ?? ''));
        })->values();

        $output = collect();
        $currentType = null;
        $buffer = collect();

        $flush = function () use (&$output, &$buffer, &$currentType) {
            if ($buffer->isEmpty()) {
                return;
            }

            $type = $currentType ?? 'other';
            $label = Lang::has('accounting::lang.'.$type)
                ? __('accounting::lang.'.$type)
                : $type;

            $group = (object) [
                'id' => null,
                'is_group' => true,
                'group_key' => $type,
                'gl_code' => '',
                'name' => $label,
                'account_primary_type' => $type,
                'debit_opening_balance' => round($buffer->sum(fn ($a) => (float) ($a->debit_opening_balance ?? 0)), 2),
                'credit_opening_balance' => round($buffer->sum(fn ($a) => (float) ($a->credit_opening_balance ?? 0)), 2),
                'debit_balance' => round($buffer->sum(fn ($a) => (float) ($a->debit_balance ?? 0)), 2),
                'credit_balance' => round($buffer->sum(fn ($a) => (float) ($a->credit_balance ?? 0)), 2),
                'child_count' => $buffer->count(),
            ];

            $output->push($group);
            foreach ($buffer as $row) {
                $row->is_group = false;
                $row->group_key = $type;
                $output->push($row);
            }

            $buffer = collect();
        };

        foreach ($sorted as $account) {
            $type = static::normalizePrimaryType((string) ($account->account_primary_type ?? 'other'));
            if ($currentType !== null && $type !== $currentType) {
                $flush();
            }
            $currentType = $type;
            $buffer->push($account);
        }
        $flush();

        return $output;
    }

    /** @return array<string, mixed> */
    public static function buildAnalytics(Collection $rows, bool $aggregated = false): array
    {
        $detailRows = $rows->filter(fn ($a) => ! ($a->is_group ?? false));

        $totalDebitOpening = 0.0;
        $totalCreditOpening = 0.0;
        $totalDebitPeriod = 0.0;
        $totalCreditPeriod = 0.0;
        $totalClosingDebit = 0.0;
        $totalClosingCredit = 0.0;
        $activeCount = 0;
        $movementByAccount = [];

        $typeTotals = [];

        foreach ($detailRows as $account) {
            $dOpen = (float) ($account->debit_opening_balance ?? 0);
            $cOpen = (float) ($account->credit_opening_balance ?? 0);
            $dPeriod = (float) ($account->debit_balance ?? 0);
            $cPeriod = (float) ($account->credit_balance ?? 0);
            $closing = static::closingBalance($account);

            $totalDebitOpening += $dOpen;
            $totalCreditOpening += $cOpen;
            $totalDebitPeriod += $dPeriod;
            $totalCreditPeriod += $cPeriod;
            $totalClosingDebit += (float) $closing['closing_debit_balance'];
            $totalClosingCredit += (float) $closing['closing_credit_balance'];

            $movement = $dPeriod + $cPeriod + $dOpen + $cOpen;
            if ($movement > 0.0001) {
                $activeCount++;
            }

            $movementByAccount[] = [
                'gl_code' => $account->gl_code ?? '',
                'name' => $account->name ?? '',
                'movement' => round($dPeriod + $cPeriod, 2),
                'closing_signed' => static::signedClosing($account),
            ];

            $typeKey = static::normalizePrimaryType((string) ($account->account_primary_type ?? 'other'));
            if (! isset($typeTotals[$typeKey])) {
                $typeTotals[$typeKey] = 0.0;
            }
            $typeTotals[$typeKey] += abs(static::signedClosing($account));
        }

        usort($movementByAccount, fn ($a, $b) => $b['movement'] <=> $a['movement']);
        $topMovement = array_slice($movementByAccount, 0, 5);

        $difference = abs($totalClosingDebit - $totalClosingCredit);
        $isBalanced = $difference < 0.005;

        $chart = static::buildChartPayload($typeTotals);

        return [
            'kpis' => [
                'total_debit_period' => round($totalDebitPeriod, 2),
                'total_credit_period' => round($totalCreditPeriod, 2),
                'total_debit_opening' => round($totalDebitOpening, 2),
                'total_credit_opening' => round($totalCreditOpening, 2),
                'closing_debit' => round($totalClosingDebit, 2),
                'closing_credit' => round($totalClosingCredit, 2),
                'difference' => round($difference, 2),
                'is_balanced' => $isBalanced,
                'account_count' => $detailRows->count(),
                'active_accounts' => $activeCount,
                'inactive_accounts' => max(0, $detailRows->count() - $activeCount),
                'aggregated' => $aggregated,
            ],
            'chart' => $chart,
            'top_movement' => $topMovement,
            'type_totals' => $typeTotals,
        ];
    }

    /** @param  array<string, float>  $typeTotals */
    public static function buildChartPayload(array $typeTotals): array
    {
        $labels = [];
        $series = [];
        $colors = [];
        $i = 0;

        foreach (self::PRIMARY_TYPE_ORDER as $type) {
            if (! isset($typeTotals[$type]) || $typeTotals[$type] < 0.0001) {
                continue;
            }
            $labels[] = Lang::has('accounting::lang.'.$type)
                ? __('accounting::lang.'.$type)
                : ucfirst($type);
            $series[] = round($typeTotals[$type], 2);
            $colors[] = self::CHART_COLORS[$i % count(self::CHART_COLORS)];
            $i++;
        }

        foreach ($typeTotals as $type => $amount) {
            if (in_array($type, self::PRIMARY_TYPE_ORDER, true) || $amount < 0.0001) {
                continue;
            }
            $labels[] = $type;
            $series[] = round($amount, 2);
            $colors[] = self::CHART_COLORS[$i % count(self::CHART_COLORS)];
            $i++;
        }

        return compact('labels', 'series', 'colors');
    }

    public static function normalizePrimaryType(string $type): string
    {
        $type = strtolower(trim($type));

        return match ($type) {
            'liability' => 'liabilities',
            'expense' => 'expenses',
            default => $type !== '' ? $type : 'other',
        };
    }

    public static function growthPercent(?float $current, ?float $previous): ?float
    {
        return CurrencyHelper::growth_percent($current, $previous);
    }
}
