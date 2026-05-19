<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use App\Helpers\CurrencyHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Expense\Support\TreasuryAccounts;

final class CashFlowReportService
{
    public const SECTIONS = ['operating', 'investing', 'financing'];

    /** @var array<string, string> */
    public const CHART_COLORS = [
        '#1B84FF',
        '#17C653',
        '#F6C000',
    ];

    /** @var array<string, array<string, array<int, string>>> */
    public const STATEMENT_LINES = [
        'operating' => [
            'sales_receipts' => ['sell', 'sell_cash', 'sales_revenue', 'receipt_voucher'],
            'supplier_payments' => ['purchases'],
            'payroll' => [],
            'operating_expenses' => ['expense', 'expense_refund', 'payment_voucher'],
            'tax_payments' => [],
        ],
        'investing' => [
            'asset_purchases' => ['asset_purchase', 'capital_expenditure', 'fixed_asset', 'periodic_inventory'],
            'asset_sales' => ['asset_sale'],
            'investments' => [],
        ],
        'financing' => [
            'loans_received' => ['loan', 'loan_received', 'owner_injection', 'capital', 'equity'],
            'loan_repayments' => ['loan_payment', 'owner_withdrawal'],
            'dividends' => [],
        ],
    ];

    /** @return array<string, mixed> */
    public static function dataset(Request $request): array
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $costCenterIds = array_values(array_filter((array) $request->input('choose_cost_center_select', [])));
        $movementType = $request->input('movement_type');
        $selectedSubTypes = (array) $request->input('sub_types', []);
        $activitySection = $request->input('activity_section');
        $compareMode = $request->input('compare_mode', 'none');
        $periodGroup = $request->input('period_group', 'month');

        $flows = static::fetchCashFlows($startDate, $endDate, $costCenterIds, $movementType, $selectedSubTypes, $activitySection);
        $statement = static::buildStatement($flows);
        $sectionSummaries = static::summarizeSections($flows);
        $cashInflows = (float) $flows->where('type', 'credit')->sum('amount');
        $cashOutflows = (float) $flows->where('type', 'debit')->sum('amount');
        $netCashFlow = $cashInflows - $cashOutflows;

        $openingCash = static::cashBalanceBefore($startDate, $costCenterIds);
        $closingCash = $openingCash + $netCashFlow;

        $monthlyTrend = static::monthlyTrend($startDate, $endDate, $costCenterIds, $selectedSubTypes, $activitySection, $periodGroup);
        $chart = static::buildSectionChart($sectionSummaries);
        $barChart = static::buildInOutBarChart($cashInflows, $cashOutflows, $sectionSummaries);
        $analytics = static::buildAnalytics(
            $flows,
            $sectionSummaries,
            $netCashFlow,
            $openingCash,
            $closingCash,
            $cashOutflows
        );

        $comparePeriod = null;
        $compareAnalytics = null;
        if (in_array($compareMode, ['previous_period', 'previous_year'], true)) {
            $comparePeriod = static::resolveComparePeriod($startDate, $endDate, $compareMode);
            $compareFlows = static::fetchCashFlows(
                $comparePeriod['start_date'],
                $comparePeriod['end_date'],
                $costCenterIds,
                $movementType,
                $selectedSubTypes,
                $activitySection
            );
            $compareSummaries = static::summarizeSections($compareFlows);
            $compareNet = (float) $compareFlows->where('type', 'credit')->sum('amount')
                - (float) $compareFlows->where('type', 'debit')->sum('amount');
            $compareAnalytics = [
                'net_cash_flow' => $compareNet,
                'section_summaries' => $compareSummaries,
                'growth_percent' => CurrencyHelper::growth_percent($netCashFlow, $compareNet),
            ];
        }

        $detailRows = static::mapDetailRows($flows);
        $availableSubTypes = static::availableSubTypes($startDate, $endDate);

        return compact(
            'startDate',
            'endDate',
            'costCenterIds',
            'movementType',
            'selectedSubTypes',
            'activitySection',
            'compareMode',
            'periodGroup',
            'flows',
            'statement',
            'sectionSummaries',
            'cashInflows',
            'cashOutflows',
            'netCashFlow',
            'openingCash',
            'closingCash',
            'monthlyTrend',
            'chart',
            'barChart',
            'analytics',
            'comparePeriod',
            'compareAnalytics',
            'detailRows',
            'availableSubTypes'
        );
    }

    /** @return Collection<int, AccountingAccountsTransaction> */
    public static function fetchCashFlows(
        string $startDate,
        string $endDate,
        array $costCenterIds,
        ?string $movementType,
        array $selectedSubTypes,
        ?string $activitySection
    ): Collection {
        $defaultSubTypes = ['sell', 'sell_cash', 'purchases', 'sales_revenue', 'receipt_voucher', 'payment_voucher', 'journal_entry', 'expense', 'expense_refund'];
        $effectiveSubTypes = $selectedSubTypes !== [] ? $selectedSubTypes : array_merge(
            $defaultSubTypes,
            static::allConfiguredSubTypes()
        );

        $treasuryIds = TreasuryAccounts::ids();

        return AccountingAccountsTransaction::query()
            ->with(['accTransMapping', 'costCenter', 'account'])
            ->whereIn('accounting_account_id', $treasuryIds)
            ->whereBetween('operation_date', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->whereIn('sub_type', $effectiveSubTypes)
            ->when($costCenterIds !== [], fn ($q) => $q->whereIn('cost_center_id', $costCenterIds))
            ->when($movementType, fn ($q) => $q->where('type', $movementType))
            ->when($activitySection, function ($q) use ($activitySection) {
                $types = static::getSectionSubTypes($activitySection);
                if ($types !== []) {
                    $q->whereIn('sub_type', $types);
                }
            })
            ->orderBy('operation_date')
            ->orderBy('id')
            ->get();
    }

    /** @return array<int, string> */
    public static function allConfiguredSubTypes(): array
    {
        $all = [];
        foreach (self::STATEMENT_LINES as $lines) {
            foreach ($lines as $subTypes) {
                $all = array_merge($all, $subTypes);
            }
        }
        foreach (self::SECTIONS as $section) {
            $all = array_merge($all, static::getSectionSubTypes($section));
        }

        return array_values(array_unique($all));
    }

    /** @return array<int, object> */
    public static function buildStatement(Collection $flows): array
    {
        $sections = [];

        foreach (self::SECTIONS as $sectionKey) {
            $lines = [];
            $sectionIn = 0.0;
            $sectionOut = 0.0;

            foreach (self::STATEMENT_LINES[$sectionKey] as $lineKey => $subTypes) {
                $lineFlows = $flows->filter(fn ($f) => in_array((string) $f->sub_type, $subTypes, true));
                $inflows = (float) $lineFlows->where('type', 'credit')->sum('amount');
                $outflows = (float) $lineFlows->where('type', 'debit')->sum('amount');
                $net = $inflows - $outflows;
                $sectionIn += $inflows;
                $sectionOut += $outflows;

                $lines[] = (object) [
                    'line_key' => $lineKey,
                    'label' => __('accounting::lang.cf_line_'.$lineKey),
                    'inflows' => round($inflows, 2),
                    'outflows' => round($outflows, 2),
                    'amount' => round($net, 2),
                    'is_subtotal' => false,
                    'depth' => 1,
                ];
            }

            $otherFlows = $flows->filter(function ($f) use ($sectionKey) {
                return static::resolveSection((string) $f->sub_type) === $sectionKey
                    && ! static::isMappedSubType((string) $f->sub_type);
            });
            if ($otherFlows->isNotEmpty()) {
                $in = (float) $otherFlows->where('type', 'credit')->sum('amount');
                $out = (float) $otherFlows->where('type', 'debit')->sum('amount');
                $sectionIn += $in;
                $sectionOut += $out;
                $lines[] = (object) [
                    'line_key' => 'other',
                    'label' => __('accounting::lang.cf_line_other'),
                    'inflows' => round($in, 2),
                    'outflows' => round($out, 2),
                    'amount' => round($in - $out, 2),
                    'is_subtotal' => false,
                    'depth' => 1,
                ];
            }

            $netSection = $sectionIn - $sectionOut;
            $lines[] = (object) [
                'line_key' => $sectionKey.'_net',
                'label' => __('accounting::lang.cf_section_net_'.$sectionKey),
                'inflows' => round($sectionIn, 2),
                'outflows' => round($sectionOut, 2),
                'amount' => round($netSection, 2),
                'is_subtotal' => true,
                'depth' => 0,
            ];

            $sections[] = (object) [
                'section_key' => $sectionKey,
                'section_label' => __('accounting::lang.'.$sectionKey.'_activities'),
                'lines' => $lines,
                'net' => round($netSection, 2),
            ];
        }

        return $sections;
    }

    public static function isMappedSubType(string $subType): bool
    {
        foreach (self::STATEMENT_LINES as $lines) {
            foreach ($lines as $subTypes) {
                if (in_array($subType, $subTypes, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return array<string, array{inflows: float, outflows: float, net: float}> */
    public static function summarizeSections(Collection $flows): array
    {
        $summaries = [
            'operating' => ['inflows' => 0.0, 'outflows' => 0.0, 'net' => 0.0],
            'investing' => ['inflows' => 0.0, 'outflows' => 0.0, 'net' => 0.0],
            'financing' => ['inflows' => 0.0, 'outflows' => 0.0, 'net' => 0.0],
        ];

        foreach ($flows as $flow) {
            $section = static::resolveSection((string) $flow->sub_type);
            if ($flow->type === 'credit') {
                $summaries[$section]['inflows'] += (float) $flow->amount;
            } else {
                $summaries[$section]['outflows'] += (float) $flow->amount;
            }
            $summaries[$section]['net'] = $summaries[$section]['inflows'] - $summaries[$section]['outflows'];
        }

        foreach ($summaries as $key => $row) {
            $summaries[$key]['inflows'] = round($row['inflows'], 2);
            $summaries[$key]['outflows'] = round($row['outflows'], 2);
            $summaries[$key]['net'] = round($row['net'], 2);
        }

        return $summaries;
    }

    public static function cashBalanceBefore(string $date, array $costCenterIds): float
    {
        $treasuryIds = TreasuryAccounts::ids();
        if ($treasuryIds === []) {
            return 0.0;
        }

        $q = AccountingAccountsTransaction::query()
            ->whereIn('accounting_account_id', $treasuryIds)
            ->whereDate('operation_date', '<', $date);

        if ($costCenterIds !== []) {
            $q->whereIn('cost_center_id', $costCenterIds);
        }

        $in = (float) (clone $q)->where('type', 'credit')->sum('amount');
        $out = (float) (clone $q)->where('type', 'debit')->sum('amount');

        return round($in - $out, 2);
    }

    /** @return array{labels: array, inflows: array, outflows: array, net: array} */
    public static function monthlyTrend(
        string $startDate,
        string $endDate,
        array $costCenterIds,
        array $selectedSubTypes,
        ?string $activitySection,
        string $periodGroup
    ): array {
        $flows = static::fetchCashFlows($startDate, $endDate, $costCenterIds, null, $selectedSubTypes, $activitySection);
        $format = match ($periodGroup) {
            'quarter' => 'Y-\\QQ',
            'year' => 'Y',
            default => 'Y-m',
        };

        $grouped = $flows->groupBy(fn ($f) => Carbon::parse($f->operation_date)->format(
            $periodGroup === 'quarter' ? 'Y-Q' : ($periodGroup === 'year' ? 'Y' : 'Y-m')
        ));

        if ($periodGroup === 'quarter') {
            $grouped = $flows->groupBy(fn ($f) => Carbon::parse($f->operation_date)->year.'-Q'.Carbon::parse($f->operation_date)->quarter);
        }

        $labels = $grouped->keys()->sort()->values()->all();

        return [
            'labels' => $labels,
            'inflows' => $grouped->map(fn ($g) => round((float) $g->where('type', 'credit')->sum('amount'), 2))->values()->all(),
            'outflows' => $grouped->map(fn ($g) => round((float) $g->where('type', 'debit')->sum('amount'), 2))->values()->all(),
            'net' => $grouped->map(fn ($g) => round(
                (float) $g->where('type', 'credit')->sum('amount') - (float) $g->where('type', 'debit')->sum('amount'),
                2
            ))->values()->all(),
        ];
    }

    /** @param  array<string, array{inflows: float, outflows: float, net: float}>  $sectionSummaries */
    public static function buildSectionChart(array $sectionSummaries): array
    {
        $labels = [];
        $series = [];
        $colors = [];
        $i = 0;

        foreach (self::SECTIONS as $section) {
            $magnitude = abs($sectionSummaries[$section]['net'] ?? 0);
            if ($magnitude < 0.0001) {
                continue;
            }
            $labels[] = __('accounting::lang.'.$section.'_activities');
            $series[] = round($magnitude, 2);
            $colors[] = self::CHART_COLORS[$i % count(self::CHART_COLORS)];
            $i++;
        }

        return compact('labels', 'series', 'colors');
    }

    /** @param  array<string, array{inflows: float, outflows: float, net: float}>  $sectionSummaries */
    public static function buildInOutBarChart(float $inflows, float $outflows, array $sectionSummaries): array
    {
        return [
            'labels' => [
                __('accounting::lang.cash_inflows'),
                __('accounting::lang.cash_outflows'),
                __('accounting::lang.operating_activities'),
                __('accounting::lang.investing_activities'),
                __('accounting::lang.financing_activities'),
            ],
            'inflows' => [
                round($inflows, 2),
                0,
                round($sectionSummaries['operating']['inflows'] ?? 0, 2),
                round($sectionSummaries['investing']['inflows'] ?? 0, 2),
                round($sectionSummaries['financing']['inflows'] ?? 0, 2),
            ],
            'outflows' => [
                0,
                round($outflows, 2),
                round($sectionSummaries['operating']['outflows'] ?? 0, 2),
                round($sectionSummaries['investing']['outflows'] ?? 0, 2),
                round($sectionSummaries['financing']['outflows'] ?? 0, 2),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function buildAnalytics(
        Collection $flows,
        array $sectionSummaries,
        float $netCashFlow,
        float $openingCash,
        float $closingCash,
        float $cashOutflows
    ): array {
        $operatingNet = (float) ($sectionSummaries['operating']['net'] ?? 0);
        $months = max($flows->groupBy(fn ($f) => Carbon::parse($f->operation_date)->format('Y-m'))->count(), 1);
        $burnRate = $netCashFlow < 0 ? round(abs($netCashFlow) / $months, 2) : 0.0;
        $operatingRatio = $cashOutflows > 0.0001
            ? round(($operatingNet / $cashOutflows) * 100, 1)
            : null;

        $bySubType = $flows->groupBy('sub_type')->map(function ($group, $subType) {
            $net = (float) $group->where('type', 'credit')->sum('amount')
                - (float) $group->where('type', 'debit')->sum('amount');

            return [
                'sub_type' => (string) $subType,
                'label' => Lang::has('accounting::lang.'.$subType)
                    ? __('accounting::lang.'.$subType)
                    : (string) $subType,
                'inflows' => (float) $group->where('type', 'credit')->sum('amount'),
                'outflows' => (float) $group->where('type', 'debit')->sum('amount'),
                'net' => $net,
            ];
        });

        $topInflows = $bySubType->sortByDesc('inflows')->take(5)->values()->all();
        $topOutflows = $bySubType->sortByDesc('outflows')->take(5)->values()->all();

        $liquidityGrowth = $openingCash != 0.0
            ? round((($closingCash - $openingCash) / abs($openingCash)) * 100, 1)
            : null;

        return [
            'kpis' => [
                'net_cash_flow' => round($netCashFlow, 2),
                'operating_net' => round($operatingNet, 2),
                'investing_net' => round((float) ($sectionSummaries['investing']['net'] ?? 0), 2),
                'financing_net' => round((float) ($sectionSummaries['financing']['net'] ?? 0), 2),
                'opening_cash' => round($openingCash, 2),
                'closing_cash' => round($closingCash, 2),
                'liquidity_growth' => $liquidityGrowth,
            ],
            'operating_cash_ratio' => $operatingRatio,
            'cash_burn_rate' => $burnRate,
            'top_inflows' => $topInflows,
            'top_outflows' => $topOutflows,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function mapDetailRows(Collection $flows): array
    {
        $localeAr = app()->getLocale() === 'ar';

        return $flows->sortByDesc('operation_date')->values()->map(function ($flow) use ($localeAr) {
            $isOut = $flow->type === 'debit';
            $section = static::resolveSection((string) $flow->sub_type);

            return [
                'section' => __('accounting::lang.'.$section.'_activities'),
                'section_key' => $section,
                'operation_date' => $flow->operation_date,
                'ref_no' => $flow->accTransMapping?->ref_no ?? '—',
                'transaction_type' => Lang::has('accounting::lang.'.$flow->sub_type)
                    ? __('accounting::lang.'.$flow->sub_type)
                    : $flow->sub_type,
                'movement_type' => $isOut ? __('accounting::lang.cash_outflows') : __('accounting::lang.cash_inflows'),
                'is_outflow' => $isOut,
                'cost_center' => $flow->costCenter
                    ? ($localeAr ? $flow->costCenter->name_ar : $flow->costCenter->name_en)
                    : '—',
                'amount' => (float) $flow->amount,
                'detail_url' => $flow->ledgerDetailUrl(),
            ];
        })->all();
    }

    public static function availableSubTypes(string $startDate, string $endDate): Collection
    {
        $treasuryIds = TreasuryAccounts::ids();

        return AccountingAccountsTransaction::query()
            ->whereIn('accounting_account_id', $treasuryIds)
            ->whereBetween('operation_date', [$startDate, $endDate])
            ->distinct()
            ->pluck('sub_type')
            ->filter()
            ->values();
    }

    public static function resolveSection(?string $subType): string
    {
        $subType = (string) $subType;
        if (in_array($subType, static::getSectionSubTypes('investing'), true)) {
            return 'investing';
        }
        if (in_array($subType, static::getSectionSubTypes('financing'), true)) {
            return 'financing';
        }

        return 'operating';
    }

    /** @return array<int, string> */
    public static function getSectionSubTypes(string $section): array
    {
        $map = [
            'operating' => ['sell', 'sell_cash', 'purchases', 'sales_revenue', 'receipt_voucher', 'payment_voucher', 'expense', 'expense_refund', 'journal_entry'],
            'investing' => ['asset_sale', 'asset_purchase', 'fixed_asset', 'capital_expenditure', 'periodic_inventory'],
            'financing' => ['loan', 'loan_received', 'loan_payment', 'equity', 'capital', 'owner_withdrawal', 'owner_injection'],
        ];

        return $map[$section] ?? [];
    }

    public static function resolveComparePeriod(string $startDate, string $endDate, string $mode): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($mode === 'previous_year') {
            return [
                'start_date' => $start->copy()->subYear()->toDateString(),
                'end_date' => $end->copy()->subYear()->toDateString(),
            ];
        }

        $days = $start->diffInDays($end) + 1;

        return [
            'start_date' => $start->copy()->subDays($days)->toDateString(),
            'end_date' => $start->copy()->subDay()->toDateString(),
        ];
    }
}
