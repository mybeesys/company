<?php

namespace Modules\Employee\Services;

use App\Helpers\CurrencyHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Services\AccountingLedgerOverviewService;
use Modules\Accounting\Support\AccountingPermissions;
use Modules\Employee\Support\DashboardAccess;
use Modules\Employee\Support\DashboardHubPermissions;
use Modules\Expense\Services\ExpenseReportService;
use Modules\Establishment\Models\Establishment;
use Modules\Inventory\Services\InventoryStockHealthService;
use Modules\Inventory\Support\InventoryPermissions;
use Modules\Product\Services\ProductCatalogHealthService;
use Modules\Product\Support\ProductPermissions;
use Modules\Purchases\Support\PurchasesPermissions;
use Modules\Sales\Support\SalesPermissions;
use Modules\Sales\Support\TransactionPurpose;

/**
 * Server-side aggregations for the isolated Executive Dashboard (/dashboard).
 * Does not mutate schemas or existing dashboard controllers.
 */
class ExecutiveDashboardService
{
    public const VALID_STATUSES = ['approved', 'final'];

    public const WIDGETS = [
        'kpis',
        'financial-trend',
        'expense-distribution',
        'branch-sales',
        'sales-analysis',
        'product-health',
        'inventory-health',
        'accounting-health',
        'insights',
        'alerts',
        'drilldown',
    ];

    /**
     * @return array<string, mixed>
     */
    public function resolveFilters(Request $request): array
    {
        $today = Carbon::today();
        $start = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfYear()->startOfDay();
        $end = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : $today->copy()->endOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $periodDays = (int) max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $prevStart = $start->copy()->subDays($periodDays)->startOfDay();
        $prevEnd = $start->copy()->subDay()->endOfDay();

        $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
        $activityId = $request->filled('activity_id') ? (int) $request->input('activity_id') : null;

        return [
            'start' => $start,
            'end' => $end,
            'prev_start' => $prevStart,
            'prev_end' => $prevEnd,
            'period_days' => $periodDays,
            'branch_id' => $branchId,
            'activity_id' => $activityId,
            'channel' => 'all',
            'locale' => app()->getLocale(),
            'dimension' => (string) $request->input('dimension', 'product'),
            'month' => $request->input('month'),
            'category_id' => $request->input('category_id'),
            'widget' => (string) $request->input('source', ''),
            'limit' => min(50, max(5, (int) $request->input('limit', 12))),
        ];
    }

    public static function growthPercent(float $current, float $previous): float
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 2);
        }

        return $current > 0 ? 100.0 : 0.0;
    }

    public static function netMarginPercent(float $net, float $sales): float
    {
        if ($sales <= 0) {
            return 0.0;
        }

        return round(($net / $sales) * 100, 1);
    }

    /**
     * Keep the donut readable: top categories stay visible, the rest roll into Others.
     *
     * @param  list<array<string, mixed>>  $slices
     * @return list<array<string, mixed>>
     */
    public static function compactSlices(array $slices, int $maxVisible = 6, string $otherName = 'Others', string $otherColor = '#8B93A7'): array
    {
        $rows = array_values(array_filter($slices, static fn ($slice) => (float) ($slice['value'] ?? 0) > 0));
        usort($rows, static fn ($a, $b) => ((float) $b['value'] <=> (float) $a['value']));
        if (count($rows) <= $maxVisible) {
            return $rows;
        }

        $head = array_slice($rows, 0, $maxVisible - 1);
        $tail = array_slice($rows, $maxVisible - 1);
        $otherValue = array_sum(array_map(static fn ($slice) => (float) $slice['value'], $tail));
        $total = array_sum(array_map(static fn ($slice) => (float) $slice['value'], $rows));
        $head[] = [
            'id' => 'other',
            'name' => $otherName,
            'value' => $otherValue,
            'share_percent' => $total > 0 ? round(($otherValue / $total) * 100, 1) : 0,
            'color' => $otherColor,
            'grouped' => true,
        ];

        return array_values($head);
    }

    /**
     * @return array<string, mixed>
     */
    public function bootstrap(Request $request): array
    {
        $filters = $this->resolveFilters($request);
        $locale = $filters['locale'];
        $isAr = $locale === 'ar';

        $branches = Establishment::query()
            ->get(['id', 'name'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
            ])
            ->values()
            ->all();

        $activities = [];
        try {
            $activities = AccountingCostCenter::query()
                ->get(['id', 'name_ar', 'name_en'])
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => $isAr ? (string) ($row->name_ar ?: $row->name_en) : (string) ($row->name_en ?: $row->name_ar),
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            $activities = [];
        }

        $permissions = $this->permissions();

        return [
            'locale' => $locale,
            'dir' => $isAr ? 'rtl' : 'ltr',
            'currency' => CurrencyHelper::get_format_currency(),
            'filters' => [
                'start_date' => $filters['start']->toDateString(),
                'end_date' => $filters['end']->toDateString(),
                'branch_id' => $filters['branch_id'],
                'activity_id' => $filters['activity_id'],
            ],
            'options' => [
                'branches' => $branches,
                'activities' => $activities,
            ],
            'permissions' => $permissions,
            'routes' => $this->safeRoutes($permissions, $filters),
            'colors' => [
                'sales' => '#F28705',
                'purchases' => '#4E91FF',
                'expenses' => '#FF6470',
                'profit' => '#31D17C',
                'secondary' => '#9A73FF',
                'info' => '#33C8D7',
            ],
        ];
    }

    /**
     * Unified payload for the isolated executive dashboard (all aggregations server-side).
     *
     * @return array<string, mixed>
     */
    public function summary(Request $request): array
    {
        $filters = $this->resolveFilters($request);
        $isAr = $filters['locale'] === 'ar';

        $safe = function (callable $fn, mixed $fallback) {
            try {
                return $fn();
            } catch (\Throwable $e) {
                report($e);

                return $fallback;
            }
        };

        $kpis = $safe(fn () => $this->kpis($filters), ['cards' => [], 'totals' => [], 'contract' => $this->emptyKpiContract()]);
        $trend = $safe(fn () => $this->financialTrend($filters), ['categories' => [], 'series' => [], 'pop' => []]);
        $expenses = $safe(fn () => $this->expenseDistribution($filters), ['total' => 0, 'formatted_total' => '0.00', 'slices' => []]);
        $branches = $safe(fn () => $this->branchSales($filters), ['visible' => false, 'items' => []]);
        $alerts = $safe(fn () => $this->decisionAlerts($filters, $kpis), []);
        $productHealth = $safe(fn () => $this->productHealth($filters), ['visible' => false]);
        $inventoryHealth = $safe(fn () => $this->inventoryHealth($filters), ['visible' => false]);
        $accountingHealth = $safe(fn () => $this->accountingHealth($filters), ['visible' => false]);

        $analysis = [
            'by_product' => [],
            'by_service' => [],
            'by_category' => [],
            'by_customer' => [],
            'by_salesman' => [],
        ];
        $dimMap = [
            'product' => 'by_product',
            'service' => 'by_service',
            'category' => 'by_category',
            'customer' => 'by_customer',
            'rep' => 'by_salesman',
        ];
        foreach ($dimMap as $dim => $key) {
            $analysis[$key] = $safe(
                fn () => $this->salesAnalysis(array_merge($filters, ['dimension' => $dim]))['items'] ?? [],
                []
            );
        }

        return [
            'kpis' => $kpis['contract'] ?? $this->emptyKpiContract(),
            'cards' => $kpis['cards'] ?? [],
            'financial_periods' => $this->periodsFromTrend($trend),
            'financial_chart' => $trend,
            'expense_categories' => $expenses['slices'] ?? [],
            'expense_total' => $expenses['total'] ?? 0,
            'expense_total_formatted' => $expenses['formatted_total'] ?? '0.00',
            'top_branches' => $branches['items'] ?? [],
            'branches_visible' => (bool) ($branches['visible'] ?? false),
            'sales_analysis' => $analysis,
            'decision_alerts' => $alerts,
            'product_health' => $productHealth,
            'inventory_health' => $inventoryHealth,
            'accounting_health' => $accountingHealth,
            'labels' => [
                'empty' => $isAr ? 'لا توجد بيانات' : 'No data',
                'view' => $isAr ? 'عرض' : 'View',
                'details' => $isAr ? 'تفاصيل' : 'Details',
            ],
        ];
    }

    /**
     * @return array<string, float|int|array<string, float>>
     */
    protected function emptyKpiContract(): array
    {
        return [
            'net_profit' => 0,
            'net_margin_pct' => 0,
            'total_expenses' => 0,
            'total_purchases' => 0,
            'total_sales' => 0,
            'low_stock_count' => 0,
            'high_turnover_count' => 0,
            'receivables_total' => 0,
            'receivables_overdue' => 0,
            'overdue_customers_count' => 0,
            'aov' => 0,
            'total_orders' => 0,
            'trends' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function kpis(array $filters): array
    {
        $canSales = $this->canSales();
        $canPurchases = $this->canPurchases();

        $sales = $canSales ? $this->salesTotal($filters['start'], $filters['end'], $filters) : 0.0;
        $prevSales = $canSales ? $this->salesTotal($filters['prev_start'], $filters['prev_end'], $filters) : 0.0;
        $purchases = $canPurchases ? $this->purchasesTotal($filters['start'], $filters['end'], $filters) : 0.0;
        $prevPurchases = $canPurchases ? $this->purchasesTotal($filters['prev_start'], $filters['prev_end'], $filters) : 0.0;
        $expenses = $this->expensesTotal($filters['start'], $filters['end'], $filters);
        $prevExpenses = $this->expensesTotal($filters['prev_start'], $filters['prev_end'], $filters);

        $net = $sales - $purchases - $expenses;
        $prevNet = $prevSales - $prevPurchases - $prevExpenses;
        $margin = self::netMarginPercent($net, $sales);

        $orders = $canSales ? $this->ordersCount($filters['start'], $filters['end'], $filters) : 0;
        $prevOrders = $canSales ? $this->ordersCount($filters['prev_start'], $filters['prev_end'], $filters) : 0;
        $aov = $orders > 0 ? round($sales / $orders, 2) : 0.0;
        $prevAov = $prevOrders > 0 ? round($prevSales / $prevOrders, 2) : 0.0;

        $receivables = $canSales ? $this->receivables($filters) : ['total' => 0.0, 'overdue' => 0.0, 'overdue_customers' => 0];
        $stock = $this->lowStock($filters);
        $isAr = $filters['locale'] === 'ar';

        $profitGrowth = self::growthPercent($net, $prevNet);
        $expenseGrowth = self::growthPercent($expenses, $prevExpenses);
        $purchaseGrowth = self::growthPercent($purchases, $prevPurchases);
        $salesGrowth = self::growthPercent($sales, $prevSales);
        $aovGrowth = self::growthPercent($aov, $prevAov);
        $ordersGrowth = self::growthPercent((float) $orders, (float) $prevOrders);

        $cards = [
            $this->kpiCard('profit', 'صافي الربح التشغيلي', 'Operating profit', $net, $profitGrowth, 'currency', 'income-statement', [
                'ar' => 'المبيعات − المشتريات − المصروفات · هامش '.$margin.'%',
                'en' => 'Sales − purchases − expenses · margin '.$margin.'%',
            ], ['net_margin_percent' => $margin]),
            $this->kpiCard('expenses', 'إجمالي المصروفات', 'Total Expenses', $expenses, $expenseGrowth, 'currency', 'expense-report', [
                'ar' => 'مراقبة التكلفة',
                'en' => 'Cost monitoring',
            ]),
            $this->kpiCard('purchases', 'إجمالي المشتريات', 'Total Purchases', $purchases, $purchaseGrowth, 'currency', 'purchase-dashbord', [
                'ar' => 'مقارنة بالفترة السابقة',
                'en' => 'Versus previous period',
            ]),
            $this->kpiCard('sales', 'إجمالي المبيعات', 'Total Sales', $sales, $salesGrowth, 'currency', 'sales-dashbord', [
                'ar' => 'مقارنة بالفترة السابقة',
                'en' => 'Versus previous period',
            ]),
            $this->kpiCard(
                'low_stock',
                'مخزون تحت الحد',
                'Low Stock Alert',
                (float) $stock['count'],
                null,
                'number',
                'inventory.dashboard',
                [
                    'ar' => $stock['high_turnover'].' أصناف عالية الحركة',
                    'en' => $stock['high_turnover'].' high-turnover items',
                ],
                [
                    'high_turnover' => $stock['high_turnover'],
                    'unit' => $isAr ? 'صنف' : 'items',
                    'badge' => ((int) $stock['count'] > 0)
                        ? ['tone' => 'warning', 'label' => $isAr ? 'إجراء مطلوب' : 'Action required']
                        : null,
                ]
            ),
            $this->kpiCard(
                'receivables',
                'مستحقات العملاء',
                'Receivables',
                (float) $receivables['total'],
                null,
                'currency',
                'customers-suppliers-statement',
                [
                    'ar' => 'منها '.$this->formatNumber((float) $receivables['overdue']).' متأخر',
                    'en' => 'of which '.$this->formatNumber((float) $receivables['overdue']).' overdue',
                ],
                [
                    'overdue' => (float) $receivables['overdue'],
                    'tag' => [
                        'label' => $isAr
                            ? ((int) $receivables['overdue_customers']).' عميل'
                            : ((int) $receivables['overdue_customers']).' customers',
                    ],
                ]
            ),
            $this->kpiCard('aov', 'متوسط قيمة الطلب', 'AOV', $aov, $aovGrowth, 'currency', 'invoices', [
                'ar' => 'AOV',
                'en' => 'AOV',
            ]),
            $this->kpiCard('orders', 'عدد الطلبات', 'Total Orders', (float) $orders, $ordersGrowth, 'number', 'invoices', [
                'ar' => 'طلب مكتمل',
                'en' => 'Completed orders',
            ], ['unit' => $isAr ? 'طلب' : 'orders']),
        ];

        return [
            'cards' => $cards,
            'totals' => [
                'sales' => $sales,
                'purchases' => $purchases,
                'expenses' => $expenses,
                'net' => $net,
                'net_margin_percent' => $margin,
                'orders' => $orders,
                'aov' => $aov,
            ],
            'contract' => [
                'net_profit' => $net,
                'net_margin_pct' => $margin,
                'total_expenses' => $expenses,
                'total_purchases' => $purchases,
                'total_sales' => $sales,
                'low_stock_count' => (int) $stock['count'],
                'high_turnover_count' => (int) $stock['high_turnover'],
                'receivables_total' => (float) $receivables['total'],
                'receivables_overdue' => (float) $receivables['overdue'],
                'overdue_customers_count' => (int) $receivables['overdue_customers'],
                'aov' => $aov,
                'total_orders' => $orders,
                'trends' => [
                    'net_profit' => $profitGrowth,
                    'total_expenses' => $expenseGrowth,
                    'total_purchases' => $purchaseGrowth,
                    'total_sales' => $salesGrowth,
                    'aov' => $aovGrowth,
                    'total_orders' => $ordersGrowth,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function financialTrend(array $filters): array
    {
        $months = $this->trendMonths($filters);
        $canSales = $this->canSales();
        $canPurchases = $this->canPurchases();

        $salesMap = $canSales ? $this->monthlyTotals('sell', $months, $filters) : collect();
        $purchasesMap = $canPurchases ? $this->monthlyTotals('purchases', $months, $filters) : collect();
        $expensesMap = $this->monthlyExpenses($months, $filters);

        $categories = [];
        $sales = [];
        $purchases = [];
        $expenses = [];
        $profit = [];
        $isAr = $filters['locale'] === 'ar';

        foreach ($months as $month) {
            $date = Carbon::parse($month.'-01');
            $categories[] = [
                'key' => $month,
                'label' => $date->locale($isAr ? 'ar' : 'en')->translatedFormat('M Y'),
            ];
            $s = (float) ($salesMap[$month] ?? 0);
            $p = (float) ($purchasesMap[$month] ?? 0);
            $e = (float) ($expensesMap[$month] ?? 0);
            $sales[] = $s;
            $purchases[] = $p;
            $expenses[] = $e;
            $profit[] = round($s - $p - $e, 2);
        }

        $series = [];
        if ($canSales) {
            $series[] = ['key' => 'sales', 'name' => $isAr ? 'المبيعات' : 'Sales', 'type' => 'column', 'color' => '#F28705', 'data' => $sales];
        }
        if ($canPurchases) {
            $series[] = ['key' => 'purchases', 'name' => $isAr ? 'المشتريات' : 'Purchases', 'type' => 'column', 'color' => '#4E91FF', 'data' => $purchases];
        }
        $series[] = ['key' => 'expenses', 'name' => $isAr ? 'المصروفات' : 'Expenses', 'type' => 'column', 'color' => '#FF6470', 'data' => $expenses];
        $series[] = [
            'key' => 'profit',
            'name' => $isAr ? 'الربح التشغيلي' : 'Operating profit',
            'type' => 'line',
            'color' => '#31D17C',
            'data' => $profit,
        ];

        return [
            'categories' => $categories,
            'series' => $series,
            'pop' => $this->periodOverPeriod($series),
            'note' => $isAr
                ? 'الربح التشغيلي = المبيعات − المشتريات − المصروفات. لا يشمل تكلفة المخزون ولا الإهلاك؛ صافي الربح المحاسبي يظهر في قائمة الدخل.'
                : 'Operating profit = sales − purchases − expenses. It excludes inventory COGS and depreciation; accounting net profit is on the income statement.',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function expenseDistribution(array $filters): array
    {
        $isAr = $filters['locale'] === 'ar';
        $other = $isAr ? 'أخرى' : 'Others';
        $palette = ['#FF6470', '#F28705', '#4E91FF', '#9A73FF', '#33C8D7', '#31D17C'];

        $rows = collect();
        try {
            $docs = $this->expenseDocumentBreakdown($filters, $other);
            if ($docs->isNotEmpty()) {
                $rows = $docs;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        if ($rows->isEmpty()) {
            try {
                $rows = $this->expenseLedgerQuery($filters['start'], $filters['end'], $filters)
                    ->select('aa.id')
                    ->selectRaw('MAX(aa.name_ar) as name_ar')
                    ->selectRaw('MAX(aa.name_en) as name_en')
                    ->selectRaw('SUM(aat.amount) as total')
                    ->groupBy('aa.id')
                    ->orderByDesc('total')
                    ->get();
            } catch (\Throwable $e) {
                report($e);
                $rows = collect();
            }
        }

        $total = (float) $rows->sum('total');
        $slices = $rows->values()->map(function ($row, $i) use ($isAr, $total, $palette) {
            $value = (float) $row->total;

            return [
                'id' => $row->id,
                'name' => $isAr ? (string) $row->name_ar : (string) $row->name_en,
                'value' => $value,
                'share_percent' => $total > 0 ? round(($value / $total) * 100, 1) : 0,
                'color' => $palette[$i % count($palette)],
            ];
        })->filter(fn ($slice) => $slice['value'] > 0)->values()->all();

        return [
            'total' => $total,
            'formatted_total' => $this->formatMoney($total),
            'slices' => self::compactSlices($slices, 6, $other),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function branchSales(array $filters): array
    {
        if (! $this->canSales() || ! Schema::hasColumn('transactions', 'establishment_id')) {
            return ['visible' => false, 'items' => []];
        }

        $branchCount = Establishment::query()->count();
        if ($branchCount <= 1) {
            return ['visible' => false, 'items' => []];
        }

        $query = $this->salesQuery($filters['start'], $filters['end'], $filters, 't');
        $rows = $query
            ->leftJoin('est_establishments as e', 'e.id', '=', 't.establishment_id')
            ->selectRaw('t.establishment_id as id, COALESCE(e.name, ?) as name, SUM(t.final_total) as total', [
                $filters['locale'] === 'ar' ? 'غير محدد' : 'Unassigned',
            ])
            ->groupBy('t.establishment_id', 'e.name')
            ->orderByDesc('total')
            ->get();

        $max = (float) ($rows->max('total') ?: 1);

        return [
            'visible' => true,
            'items' => $rows->map(fn ($row) => [
                'id' => $row->id ? (int) $row->id : null,
                'name' => (string) $row->name,
                'value' => (float) $row->total,
                'formatted' => $this->formatMoney((float) $row->total),
                'share_percent' => round(((float) $row->total / $max) * 100, 1),
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function salesAnalysis(array $filters): array
    {
        if (! $this->canSales()) {
            return ['items' => [], 'dimension' => 'product'];
        }

        $dimension = $filters['dimension'] ?: 'product';
        $allowed = ['product', 'service', 'category', 'customer', 'rep'];
        if (! in_array($dimension, $allowed, true)) {
            $dimension = 'product';
        }

        $isAr = $filters['locale'] === 'ar';
        $limit = 5;
        $base = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
            ->leftJoin('product_products as p', 'p.id', '=', 'tsl.product_id')
            ->where('t.type', 'sell')
            ->whereIn('t.status', self::VALID_STATUSES)
            ->whereBetween('t.transaction_date', [$filters['start'], $filters['end']]);

        $this->applyTransactionFilters($base, $filters, 't');

        $amountExpr = 'SUM(CAST(COALESCE(tsl.total_before_vat, tsl.unit_price_inc_tax, 0) AS DECIMAL(16,4))) as total';

        if ($dimension === 'customer') {
            $rows = $base->leftJoin('cs_contacts as c', 'c.id', '=', 't.contact_id')
                ->selectRaw("t.contact_id as id, COALESCE(c.name, ?) as name, {$amountExpr}", [$isAr ? 'بدون عميل' : 'No customer'])
                ->groupBy('t.contact_id', 'c.name')
                ->orderByDesc('total')
                ->limit($limit)
                ->get();
        } elseif ($dimension === 'rep') {
            $rows = $base->leftJoin('emp_employees as emp', 'emp.id', '=', 't.created_by')
                ->selectRaw("t.created_by as id, COALESCE(emp.name, ?) as name, {$amountExpr}", [$isAr ? 'غير محدد' : 'Unassigned'])
                ->groupBy('t.created_by', 'emp.name')
                ->orderByDesc('total')
                ->limit($limit)
                ->get();
        } elseif ($dimension === 'category') {
            $rows = $base->leftJoin('product_categories as pc', 'pc.id', '=', 'p.category_id')
                ->selectRaw(
                    "p.category_id as id, COALESCE(pc.name_ar, pc.name_en, ?) as name_ar, COALESCE(pc.name_en, pc.name_ar, ?) as name_en, {$amountExpr}",
                    [$isAr ? 'بدون تصنيف' : 'Uncategorized', 'Uncategorized']
                )
                ->groupBy('p.category_id', 'pc.name_ar', 'pc.name_en')
                ->orderByDesc('total')
                ->limit($limit)
                ->get()
                ->map(function ($row) use ($isAr) {
                    $row->name = $isAr ? ($row->name_ar ?: $row->name_en) : ($row->name_en ?: $row->name_ar);

                    return $row;
                });
        } elseif ($dimension === 'service') {
            $rows = $base->where(function ($q) {
                $q->where('p.type', 'like', '%service%')
                    ->orWhere('p.class', 'like', '%service%');
            })
                ->selectRaw("p.id as id, COALESCE(p.name_ar, p.name_en, ?) as name, {$amountExpr}", [$isAr ? 'خدمة' : 'Service'])
                ->groupBy('p.id', 'p.name_ar', 'p.name_en')
                ->orderByDesc('total')
                ->limit($limit)
                ->get();
        } else {
            $rows = $base->where(function ($q) {
                $q->whereNull('p.type')
                    ->orWhereIn('p.type', ['product', 'variable', 'variation', 'fastProduct']);
            })
                ->selectRaw("p.id as id, COALESCE(p.name_ar, p.name_en, ?) as name, {$amountExpr}", [$isAr ? 'صنف' : 'Item'])
                ->groupBy('p.id', 'p.name_ar', 'p.name_en')
                ->orderByDesc('total')
                ->limit($limit)
                ->get();
        }

        $max = (float) ($rows->max('total') ?: 1);

        return [
            'dimension' => $dimension,
            'items' => collect($rows)->map(fn ($row) => [
                'id' => $row->id ? (int) $row->id : null,
                'name' => (string) ($row->name ?? ''),
                'value' => (float) $row->total,
                'formatted' => $this->formatMoney((float) $row->total),
                'share_percent' => round(((float) $row->total / $max) * 100, 1),
            ])->values()->all(),
        ];
    }

    /**
     * Product catalog quality, pricing risk, and 6-month creation growth.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function productHealth(array $filters): array
    {
        if (! $this->canProducts()) {
            return ['visible' => false];
        }

        $isAr = $filters['locale'] === 'ar';
        $snap = app(ProductCatalogHealthService::class)->snapshot();
        $canEdit = DashboardAccess::allows(auth()->user(), ProductPermissions::PRODUCT_UPDATE);

        $productName = function ($row) use ($isAr) {
            $arName = trim((string) ($row->name_ar ?? ''));
            $enName = trim((string) ($row->name_en ?? ''));

            return $isAr ? ($arName !== '' ? $arName : $enName) : ($enName !== '' ? $enName : $arName);
        };

        $editUrl = function ($id) use ($canEdit) {
            if (! $canEdit) {
                return null;
            }

            return $this->named('product.edit', ['product' => $id]);
        };

        $zeroCount = (int) $snap['zero_price_count'];
        $lossCount = (int) $snap['negative_margin_count'];
        $growth = (float) $snap['growth_percent'];
        $growthUp = $growth >= 0;

        $months = collect($snap['months'] ?? []);
        $categories = $months->map(function ($month) use ($isAr) {
            return [
                'key' => $month,
                'label' => Carbon::parse($month.'-01')->locale($isAr ? 'ar' : 'en')->translatedFormat('M Y'),
            ];
        })->all();

        return [
            'visible' => true,
            'catalog_url' => $this->named('product.dashboard'),
            'products_url' => $this->named('product.index'),
            'quality' => [
                'level' => $zeroCount > 0 ? 'warning' : 'success',
                'title' => $isAr ? 'تنبيه جودة البيانات' : 'Data quality alert',
                'body' => $isAr
                    ? ($zeroCount > 0
                        ? "يوجد {$zeroCount} منتجات بدون سعر أو بسعر يساوي صفر."
                        : 'أسعار المنتجات مكتملة — لا توجد فجوات سعر.')
                    : ($zeroCount > 0
                        ? "{$zeroCount} products have a missing or zero price."
                        : 'Product prices look complete — no price gaps.'),
                'badge' => $zeroCount > 0
                    ? ($isAr ? 'يتطلب مراجعة' : 'Needs review')
                    : ($isAr ? 'سليم' : 'Healthy'),
                'count' => $zeroCount,
            ],
            'growth' => [
                'percent' => $growth,
                'formatted' => ($growthUp ? '+' : '').number_format($growth, 2).'%',
                'positive' => $growthUp,
                'title' => $isAr ? 'معدل النمو الشهري للمنتجات' : 'Monthly product growth',
                'subtitle' => $isAr
                    ? 'الحالي: '.$snap['current_month_added'].' | السابق: '.$snap['previous_month_added']
                    : 'Current: '.$snap['current_month_added'].' | Previous: '.$snap['previous_month_added'],
            ],
            'margin' => [
                'level' => $lossCount > 0 ? 'critical' : 'success',
                'title' => $isAr ? 'تنبيه ربحية المنتجات' : 'Product profitability alert',
                'body' => $isAr
                    ? ($lossCount > 0
                        ? "يوجد {$lossCount} منتجات تكلفتها أعلى من سعر البيع (خسارة مباشرة)."
                        : 'لا توجد منتجات تُباع بأقل من تكلفتها.')
                    : ($lossCount > 0
                        ? "{$lossCount} products have cost higher than selling price."
                        : 'No products are selling below cost.'),
                'badge' => $lossCount > 0
                    ? ($isAr ? 'خطر تسعير' : 'Pricing risk')
                    : ($isAr ? 'لا يوجد خطر' : 'No risk'),
                'count' => $lossCount,
            ],
            'price_fixes' => [
                'title' => $isAr ? 'منتجات تحتاج تصحيح سعر' : 'Products needing a price fix',
                'empty' => $isAr ? 'لا توجد أسعار ناقصة' : 'No missing prices',
                'items' => collect($snap['zero_price_products'])->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => $productName($row) !== '' ? $productName($row) : '#'.$row->id,
                    'price' => $this->formatMoney((float) ($row->price_with_tax ?? 0)),
                    'url' => $editUrl($row->id),
                ])->all(),
            ],
            'loss_makers' => [
                'title' => $isAr ? 'منتجات بخسارة (التكلفة > سعر البيع)' : 'Loss-making products (cost > sell price)',
                'empty' => $isAr ? 'لا توجد منتجات خاسرة' : 'No loss-making products',
                'items' => collect($snap['negative_margin_products'])->map(function ($row) use ($productName, $editUrl) {
                    $cost = (float) ($row->cost ?? 0);
                    $price = (float) ($row->price_with_tax ?? 0);

                    return [
                        'id' => (int) $row->id,
                        'name' => $productName($row) !== '' ? $productName($row) : '#'.$row->id,
                        'cost' => $this->formatMoney($cost),
                        'price' => $this->formatMoney($price),
                        'gap' => $this->formatMoney($price - $cost),
                        'url' => $editUrl($row->id),
                    ];
                })->all(),
            ],
            'catalog_growth' => [
                'title' => $isAr ? 'نمو المنتجات والقوائم (آخر 6 أشهر)' : 'Products & menus growth (last 6 months)',
                'categories' => $categories,
                'series' => [
                    [
                        'key' => 'products',
                        'name' => $isAr ? 'المنتجات' : 'Products',
                        'color' => '#4E91FF',
                        'data' => array_map('intval', $snap['products_monthly'] ?? []),
                    ],
                    [
                        'key' => 'menus',
                        'name' => $isAr ? 'القوائم المخصصة' : 'Custom menus',
                        'color' => '#31D17C',
                        'data' => array_map('intval', $snap['menus_monthly'] ?? []),
                    ],
                ],
            ],
        ];
    }

    /**
     * Warehouse stock risk, waste ops, and inbound/outbound movement.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function inventoryHealth(array $filters): array
    {
        if (! $this->canInventory()) {
            return ['visible' => false];
        }

        $isAr = $filters['locale'] === 'ar';
        $warehouseId = ! empty($filters['branch_id']) ? (int) $filters['branch_id'] : null;
        $stock = app(InventoryStockHealthService::class);
        $snap = $stock->snapshot(
            $warehouseId,
            $filters['start'],
            $filters['end']
        );

        $inventoryUrl = $this->named('inventory.dashboard');
        $wasteUrl = $this->named('waste.index');
        $productName = function ($row) use ($isAr) {
            $arName = trim((string) ($row->name_ar ?? ''));
            $enName = trim((string) ($row->name_en ?? ''));

            return $isAr ? ($arName !== '' ? $arName : $enName) : ($enName !== '' ? $enName : $arName);
        };
        $productNameList = function ($rows) use ($productName) {
            return collect($rows)->map(function ($row) use ($productName) {
                $name = $productName($row);

                return $name !== '' ? $name : '#'.$row->product_id;
            })->filter()->values()->all();
        };

        $neg = (int) $snap['negative_count'];
        $zero = (int) $snap['zero_count'];
        $low = (int) $snap['low_stock_count'];
        $waste = (int) $snap['waste_count'];
        $warehouseIds = $snap['warehouse_ids'] ?? [];
        $negPreview = $productNameList($stock->stockRows($warehouseIds, 'negative', 3));
        $zeroPreview = $productNameList($stock->stockRows($warehouseIds, 'zero', 3));

        $months = collect($snap['movement']['months'] ?? []);
        $categories = $months->map(fn ($month) => [
            'key' => $month,
            'label' => Carbon::parse($month.'-01')->locale($isAr ? 'ar' : 'en')->translatedFormat('M Y'),
        ])->all();

        return [
            'visible' => true,
            'inventory_url' => $inventoryUrl,
            'signals' => [
                [
                    'id' => 'negative-stock',
                    'level' => $neg > 0 ? 'critical' : 'success',
                    'title' => $isAr ? 'منتجات برصيد سالب' : 'Negative stock',
                    'badge' => $neg > 0 ? ($isAr ? 'حرج' : 'Critical') : ($isAr ? 'سليم' : 'Healthy'),
                    'count' => $neg,
                    'examples' => $negPreview,
                    'body' => $isAr
                        ? ($neg > 0
                            ? "{$neg} صنفاً برصيد أقل من صفر."
                            : 'لا يوجد رصيد سالب في المستودعات.')
                        : ($neg > 0
                            ? "{$neg} SKUs have a negative on-hand quantity."
                            : 'No negative on-hand quantities.'),
                ],
                [
                    'id' => 'zero-stock',
                    'level' => $zero > 0 ? 'warning' : 'success',
                    'title' => $isAr ? 'منتجات برصيد صفر' : 'Zero stock',
                    'badge' => $zero > 0 ? ($isAr ? 'يتطلب مراجعة' : 'Needs review') : ($isAr ? 'سليم' : 'Healthy'),
                    'count' => $zero,
                    'examples' => $zeroPreview,
                    'body' => $isAr
                        ? ($zero > 0
                            ? "{$zero} صنفاً برصيد صفر في المستودع."
                            : 'لا توجد أصناف برصيد صفر.')
                        : ($zero > 0
                            ? "{$zero} SKUs are at zero quantity."
                            : 'No SKUs are at zero quantity.'),
                ],
                [
                    'id' => 'low_stock',
                    'level' => $low > 0 ? 'warning' : 'success',
                    'title' => $isAr ? 'تنبيه مخزون منخفض' : 'Low stock alert',
                    'badge' => $low > 0 ? ($isAr ? 'تحت الحد' : 'Below limit') : ($isAr ? 'ضمن الحد' : 'Within limit'),
                    'count' => $low,
                    'body' => $isAr
                        ? ($low > 0 ? "{$low} أصنافاً تحت حد إعادة الطلب." : 'كل الأصناف فوق حد إعادة الطلب.')
                        : ($low > 0 ? "{$low} items are below reorder point." : 'All items are above reorder point.'),
                ],
                [
                    'id' => 'waste-ops',
                    'level' => $waste > 0 ? 'info' : 'success',
                    'title' => $isAr ? 'عمليات إتلاف معتمدة' : 'Approved waste operations',
                    'badge' => $isAr ? 'خلال الفترة' : 'In period',
                    'count' => $waste,
                    'body' => $isAr
                        ? "{$waste} عملية إتلاف معتمدة ضمن الفترة المحددة."
                        : "{$waste} approved waste operations in the selected period.",
                ],
            ],
            'movement' => [
                'title' => $isAr ? 'اتجاه الحركة الشهرية (وارد / صادر)' : 'Monthly movement (inbound / outbound)',
                'categories' => $categories,
                'series' => [
                    [
                        'key' => 'inbound',
                        'name' => $isAr ? 'وارد (تحضير)' : 'Inbound (prep)',
                        'color' => '#31D17C',
                        'data' => array_map('intval', $snap['movement']['inbound'] ?? []),
                    ],
                    [
                        'key' => 'outbound',
                        'name' => $isAr ? 'صادر (تحويل + إتلاف)' : 'Outbound (transfer + waste)',
                        'color' => '#FF6470',
                        'data' => array_map('intval', $snap['movement']['outbound'] ?? []),
                    ],
                ],
            ],
            'warehouses' => [
                'title' => $isAr ? 'أعلى / أقل رصيد لكل مستودع' : 'Highest / lowest stock per warehouse',
                'empty' => $isAr ? 'لا توجد مستودعات أو أرصدة' : 'No warehouses or stock rows',
                'items' => collect($snap['warehouse_extremes'])->map(fn ($row) => [
                    'id' => $row['id'],
                    'warehouse_id' => $row['id'],
                    'name' => $row['name'],
                    'highest' => $row['highest_name']
                        ? $row['highest_name'].' ('.$this->formatNumber((float) $row['highest_qty']).')'
                        : '—',
                    'lowest' => $row['lowest_name']
                        ? $row['lowest_name'].' ('.$this->formatNumber((float) $row['lowest_qty']).')'
                        : '—',
                    'url' => null,
                ])->all(),
            ],
            'critical' => [
                'title' => $isAr ? 'أهم العناصر الحرجة (رصيد ≤ 0)' : 'Most critical items (qty ≤ 0)',
                'empty' => $isAr ? 'لا توجد عناصر حرجة' : 'No critical items',
                'items' => collect($snap['critical_items'])->map(fn ($row) => [
                    'id' => (int) $row->product_id.'-'.(int) $row->establishment_id,
                    'name' => $productName($row) !== '' ? $productName($row) : '#'.$row->product_id,
                    'warehouse' => (string) ($row->warehouse_name ?? '—'),
                    'qty' => $this->formatNumber((float) $row->qty),
                    'url' => $this->named('productInventory.index'),
                ])->all(),
            ],
            'waste_url' => $wasteUrl,
        ];
    }

    /**
     * Ledger movement trend and chart-of-accounts mix.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function accountingHealth(array $filters): array
    {
        if (! $this->canAccounting()) {
            return ['visible' => false];
        }

        $isAr = $filters['locale'] === 'ar';
        $costCenterId = ! empty($filters['activity_id']) ? (int) $filters['activity_id'] : null;
        $snap = app(AccountingLedgerOverviewService::class)->snapshot(
            $filters['start'],
            $filters['end'],
            $costCenterId
        );

        $months = collect($snap['monthly']['months'] ?? []);
        $categories = $months->map(fn ($month) => [
            'key' => $month,
            'label' => Carbon::parse($month.'-01')->locale($isAr ? 'ar' : 'en')->translatedFormat('M Y'),
        ])->all();

        $types = collect($snap['types'] ?? []);
        $coaTotal = (float) $types->sum('abs_balance');
        $slices = $types
            ->filter(fn ($row) => (float) $row['abs_balance'] > 0)
            ->values()
            ->map(fn ($row) => [
                'id' => $row['id'],
                'name' => $row['label'],
                'value' => (float) $row['abs_balance'],
                'share_percent' => $coaTotal > 0 ? round(((float) $row['abs_balance'] / $coaTotal) * 100, 1) : 0,
                'color' => $row['color'],
            ])
            ->all();

        return [
            'visible' => true,
            'accounting_url' => $this->named('accounting-dashboard'),
            'trend' => [
                'title' => $isAr ? 'اتجاه الحركات' : 'Transaction trend',
                'categories' => $categories,
                'series' => [
                    [
                        'key' => 'debit',
                        'name' => $isAr ? 'مدين' : 'Debit',
                        'color' => '#FF6470',
                        'data' => array_map('floatval', $snap['monthly']['debit'] ?? []),
                    ],
                    [
                        'key' => 'credit',
                        'name' => $isAr ? 'دائن' : 'Credit',
                        'color' => '#31D17C',
                        'data' => array_map('floatval', $snap['monthly']['credit'] ?? []),
                    ],
                ],
            ],
            'chart_of_accounts' => [
                'title' => $isAr ? 'مخطط الحسابات' : 'Chart of accounts',
                'total' => $coaTotal,
                'formatted_total' => $this->formatMoney($coaTotal),
                'slices' => $slices,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function insights(array $filters): array
    {
        $kpis = $this->kpis($filters);
        $totals = $kpis['totals'];
        $isAr = $filters['locale'] === 'ar';
        $items = [];

        $salesCard = collect($kpis['cards'])->firstWhere('id', 'sales');
        $profitCard = collect($kpis['cards'])->firstWhere('id', 'profit');
        $salesGrowth = (float) ($salesCard['growth_percent'] ?? 0);
        $margin = (float) ($totals['net_margin_percent'] ?? 0);

        if ($salesCard) {
            if ($salesGrowth > 0 && $margin < 15) {
                $items[] = $isAr
                    ? "المبيعات نمت بنسبة {$salesGrowth}% لكن الهامش الصافي {$margin}% — راجع هيكل التكاليف."
                    : "Sales grew {$salesGrowth}% but net margin is {$margin}% — review cost structure.";
            } elseif ($salesGrowth > 0) {
                $items[] = $isAr
                    ? "نمو المبيعات {$salesGrowth}% مع هامش صافي {$margin}% خلال الفترة المحددة."
                    : "Sales growth {$salesGrowth}% with a net margin of {$margin}% in the selected period.";
            } elseif ($salesGrowth < 0) {
                $items[] = $isAr
                    ? "المبيعات انخفضت بنسبة ".abs($salesGrowth)."% مقارنة بالفترة السابقة."
                    : 'Sales declined '.abs($salesGrowth).'% versus the previous period.';
            }
        }

        $stockCard = collect($kpis['cards'])->firstWhere('id', 'low_stock');
        if ($stockCard && (int) $stockCard['value'] > 0) {
            $hot = (int) ($stockCard['meta']['high_turnover'] ?? 0);
            $items[] = $isAr
                ? ((int) $stockCard['value'])." أصنافاً تحت حد إعادة الطلب".($hot ? " منها {$hot} عالية الحركة." : '.')
                : ((int) $stockCard['value']).' items are below reorder point'.($hot ? ", including {$hot} high-turnover SKUs." : '.');
        }

        $recv = collect($kpis['cards'])->firstWhere('id', 'receivables');
        if ($recv && (float) ($recv['meta']['overdue'] ?? 0) > 0) {
            $items[] = $isAr
                ? 'توجد مستحقات متأخرة بقيمة '.$this->formatMoney((float) $recv['meta']['overdue']).' تحتاج متابعة تحصيل.'
                : 'Overdue receivables of '.$this->formatMoney((float) $recv['meta']['overdue']).' need collection follow-up.';
        }

        $dso = $this->collectionDays($filters);
        if ($dso['current'] !== null && $dso['previous'] !== null && $dso['current'] < $dso['previous']) {
            $items[] = $isAr
                ? 'تحسّن متوسط أيام التحصيل من '.$dso['previous'].' إلى '.$dso['current'].' يوماً.'
                : 'Collection period improved from '.$dso['previous'].' to '.$dso['current'].' days.';
        }

        if ($items === []) {
            $items[] = $isAr
                ? 'لا توجد إشارات استثنائية في الفترة الحالية. راقب الاتجاهات عند تغيير الفلاتر.'
                : 'No exceptional signals in this period. Watch trends as you change filters.';
        }

        return ['items' => $items];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function alerts(array $filters): array
    {
        $isAr = $filters['locale'] === 'ar';
        $groups = [];

        if ($this->canSales()) {
            $overdueCount = $this->overdueInvoiceCount($filters);
            $groups[] = [
                'level' => 'critical',
                'label' => $isAr ? 'حرج' : 'Critical',
                'count' => $overdueCount,
                'title' => $isAr ? 'فواتير عملاء متأخرة' : 'Overdue customer invoices',
                'href' => $this->routeIfCan('customers-suppliers-statement', $filters),
            ];
        }

        $stock = $this->lowStock($filters);
        $groups[] = [
            'level' => 'warning',
            'label' => $isAr ? 'تحذير' : 'Warning',
            'count' => (int) $stock['count'],
            'title' => $isAr ? 'أصناف تحت حد إعادة الطلب' : 'Items below reorder point',
            'href' => $this->routeIfCan('inventory.dashboard', $filters),
        ];

        $pending = $this->pendingApprovals();
        $groups[] = [
            'level' => 'info',
            'label' => $isAr ? 'معلومة' : 'Info',
            'count' => $pending,
            'title' => $isAr ? 'طلبات بانتظار الاعتماد' : 'Pending approvals',
            'href' => $this->routeIfCan('invoices', $filters),
        ];

        $dso = $this->collectionDays($filters);
        $improved = $dso['current'] !== null && $dso['previous'] !== null && $dso['current'] < $dso['previous'];
        $groups[] = [
            'level' => 'success',
            'label' => $isAr ? 'نجاح' : 'Success',
            'count' => $improved ? 1 : 0,
            'title' => $isAr ? 'تحسّن فترة التحصيل' : 'Collection period improved',
            'href' => $this->routeIfCan('sales-dashbord', $filters),
        ];

        return ['groups' => $groups];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $kpis
     * @return list<array<string, mixed>>
     */
    protected function decisionAlerts(array $filters, array $kpis): array
    {
        $isAr = $filters['locale'] === 'ar';
        $contract = $kpis['contract'] ?? $this->emptyKpiContract();
        $view = $isAr ? 'عرض' : 'View';
        $details = $isAr ? 'تفاصيل' : 'Details';

        $stockCount = (int) ($contract['low_stock_count'] ?? 0);
        $overdueInvoices = $this->canSales() ? $this->overdueInvoiceCount($filters) : 0;
        $pending = $this->pendingApprovals();
        $margin = (float) ($contract['net_margin_pct'] ?? 0);

        return [
            [
                'id' => 'low_stock',
                'level' => 'warning',
                'title' => $isAr ? 'أصناف تحت حد إعادة الطلب' : 'Items below reorder point',
                'count' => $stockCount,
                'action_label' => $view,
                'href' => $this->routeIfCan('inventory.dashboard', $filters),
            ],
            [
                'id' => 'overdue_invoices',
                'level' => 'critical',
                'title' => $isAr ? 'فواتير متأخرة' : 'Overdue invoices',
                'count' => $overdueInvoices,
                'action_label' => $details,
                'href' => $this->routeIfCan('customers-suppliers-statement', $filters),
            ],
            [
                'id' => 'pending_approvals',
                'level' => 'info',
                'title' => $isAr ? 'طلبات بانتظار الاعتماد' : 'Pending approvals',
                'count' => $pending,
                'action_label' => $view,
                'href' => $this->routeIfCan('invoices', $filters),
            ],
            [
                'id' => 'margin',
                'level' => $margin >= 15 ? 'success' : 'warning',
                'title' => $isAr ? ('الهامش الصافي '.$margin.'%') : ('Net margin '.$margin.'%'),
                'count' => $margin,
                'action_label' => $details,
                'href' => $this->routeIfCan('income-statement', $filters),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $trend
     * @return list<array<string, mixed>>
     */
    protected function periodsFromTrend(array $trend): array
    {
        $categories = $trend['categories'] ?? [];
        $series = $trend['series'] ?? [];
        $pop = $trend['pop'] ?? [];
        $periods = [];

        foreach ($categories as $i => $cat) {
            $row = [
                'period' => $cat['key'] ?? (string) $i,
                'label' => $cat['label'] ?? '',
                'sales' => 0.0,
                'purchases' => 0.0,
                'expenses' => 0.0,
                'profit' => 0.0,
            ];
            foreach ($series as $s) {
                $key = $s['key'] ?? '';
                if ($key === '') {
                    continue;
                }
                $row[$key] = (float) ($s['data'][$i] ?? 0);
                $row[$key.'_change_pct'] = (float) ($pop[$key][$i] ?? 0);
            }
            $periods[] = $row;
        }

        return $periods;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function drilldown(array $filters, string $source): array
    {
        $isAr = $filters['locale'] === 'ar';
        $limit = $filters['limit'];

        if ($source === 'low_stock') {
            return [
                'title' => $isAr ? 'قائمة إعادة الطلب' : 'Reorder list',
                'columns' => [
                    ['key' => 'name', 'label' => $isAr ? 'الصنف' : 'Item'],
                    ['key' => 'qty', 'label' => $isAr ? 'الكمية' : 'Qty'],
                    ['key' => 'threshold', 'label' => $isAr ? 'الحد' : 'Limit'],
                ],
                'rows' => $this->lowStockRows($limit),
                'report_url' => $this->routeIfCan('inventory.dashboard', $filters),
            ];
        }

        if (in_array($source, ['zero-price', 'negative-margin'], true)) {
            return $this->productIssueDrilldown($source, $limit, $isAr);
        }

        if (in_array($source, ['negative-stock', 'zero-stock', 'critical-stock', 'waste-ops'], true)) {
            return $this->inventoryIssueDrilldown($source, $filters, $limit, $isAr);
        }

        if ($source === 'expense-category') {
            $categoryId = (string) ($filters['category_id'] ?? '');
            if ($categoryId === 'other') {
                return $this->expenseOtherCategories($filters, $isAr);
            }

            return $this->expenseVouchers($filters, $limit, $isAr);
        }

        if (in_array($source, ['purchases', 'purchase'], true)) {
            return $this->transactionList('purchases', $filters, $limit, $isAr, $isAr ? 'فواتير المشتريات' : 'Purchase invoices', 'purchase-invoices', 'edit-purchases-invoice');
        }

        if ($source === 'expenses') {
            return $this->expenseVouchers($filters, $limit, $isAr);
        }

        return $this->transactionList('sell', $filters, $limit, $isAr, $isAr ? 'فواتير المبيعات' : 'Sales invoices', 'invoices', 'edit-invoice');
    }

    /**
     * @param  array<string, mixed>  $series
     * @return list<list<float|null>>
     */
    protected function periodOverPeriod(array $series): array
    {
        $result = [];
        foreach ($series as $s) {
            $pops = [];
            $data = $s['data'];
            foreach ($data as $i => $value) {
                $prev = $i > 0 ? (float) $data[$i - 1] : 0.0;
                $pops[] = self::growthPercent((float) $value, $prev);
            }
            $result[$s['key']] = $pops;
        }

        return $result;
    }

    protected function kpiCard(
        string $id,
        string $titleAr,
        string $titleEn,
        float $value,
        ?float $growth,
        string $format,
        string $routeName,
        array $subtitle,
        array $meta = []
    ): array {
        $isAr = app()->getLocale() === 'ar';

        return [
            'id' => $id,
            'title' => $isAr ? $titleAr : $titleEn,
            'value' => $value,
            'formatted' => $format === 'currency' ? $this->formatMoney($value) : $this->formatNumber($value),
            'growth_percent' => $growth,
            'subtitle' => $isAr ? $subtitle['ar'] : $subtitle['en'],
            'drilldown' => $id,
            'report_url' => $this->routeIfCan($routeName, $this->resolveFilters(request())),
            'meta' => $meta,
            'badge' => $meta['badge'] ?? null,
            'tag' => $meta['tag'] ?? null,
            'unit' => $meta['unit'] ?? null,
            'show_currency' => $format === 'currency',
        ];
    }

    protected function salesTotal(Carbon $start, Carbon $end, array $filters): float
    {
        return (float) $this->salesQuery($start, $end, $filters)->sum('final_total');
    }

    protected function purchasesTotal(Carbon $start, Carbon $end, array $filters): float
    {
        $q = DB::table('transactions as t')
            ->where('t.type', 'purchases')
            ->whereIn('t.status', self::VALID_STATUSES)
            ->whereBetween('t.transaction_date', [$start, $end]);
        $this->applyTransactionFilters($q, $filters, 't', withChannel: false);

        return (float) $q->sum('t.final_total');
    }

    protected function ordersCount(Carbon $start, Carbon $end, array $filters): int
    {
        return (int) $this->salesQuery($start, $end, $filters)->count();
    }

    protected function expensesTotal(Carbon $start, Carbon $end, array $filters): float
    {
        $docs = $this->expenseDocumentsTotal($start, $end, $filters);
        if ($docs > 0) {
            return $docs;
        }

        return (float) $this->expenseLedgerQuery($start, $end, $filters)->sum('aat.amount');
    }

    /**
     * Same GL scope as the accounting expense report (expenses + expense + routed purchase accounts).
     */
    protected function expenseLedgerQuery(Carbon $start, Carbon $end, array $filters)
    {
        $q = DB::table('accounting_accounts_transactions as aat')
            ->join('accounting_accounts as aa', 'aa.id', '=', 'aat.accounting_account_id')
            ->where('aat.type', 'debit')
            ->whereBetween('aat.operation_date', [$start, $end]);

        $accountIds = $this->expenseAccountIds();
        if ($accountIds !== []) {
            $q->whereIn('aat.accounting_account_id', $accountIds);
        } else {
            $q->where(function ($inner) {
                $inner->whereIn('aa.account_primary_type', ['expenses', 'expense'])
                    ->orWhere('aa.account_type', 'expenses');
            });
        }

        $this->applyExpenseFilters($q, $filters, 'aat');

        return $q;
    }

    /**
     * @return list<int>
     */
    protected function expenseAccountIds(): array
    {
        try {
            return array_values(array_filter(array_map('intval', ExpenseReportService::reportableAccountIds())));
        } catch (\Throwable) {
            return [];
        }
    }

    protected function expenseDocumentsTotal(Carbon $start, Carbon $end, array $filters): float
    {
        if (! Schema::hasTable('expenses')) {
            return 0.0;
        }

        $q = DB::table('expenses as ex')
            ->whereBetween('ex.date', [$start->toDateString(), $end->toDateString()]);
        if (! empty($filters['activity_id']) && Schema::hasColumn('expenses', 'cost_center_id')) {
            $q->where('ex.cost_center_id', $filters['activity_id']);
        }

        return (float) $q->sum('ex.amount');
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected function expenseDocumentBreakdown(array $filters, string $other)
    {
        if (! Schema::hasTable('expenses')) {
            return collect();
        }

        $hasCategoryCol = Schema::hasColumn('expenses', 'expense_category_id');
        $hasCategories = $hasCategoryCol && Schema::hasTable('expense_categories');
        $q = DB::table('expenses as ex')
            ->when($hasCategories, function ($query) {
                $query->leftJoin('expense_categories as ec', 'ec.id', '=', 'ex.expense_category_id');
            })
            ->leftJoin('accounting_accounts as aa', 'aa.id', '=', 'ex.debit_accounting_account_id')
            ->whereBetween('ex.date', [$filters['start']->toDateString(), $filters['end']->toDateString()]);
        if (! empty($filters['activity_id']) && Schema::hasColumn('expenses', 'cost_center_id')) {
            $q->where('ex.cost_center_id', $filters['activity_id']);
        }

        if ($hasCategories) {
            $nameExpr = 'MAX(COALESCE(NULLIF(ec.name, ""), NULLIF(aa.name_ar, ""), NULLIF(aa.name_en, ""), ?))';

            return $q
                ->selectRaw('COALESCE(ex.expense_category_id, ex.debit_accounting_account_id, 0) as id')
                ->selectRaw("{$nameExpr} as name_ar", [$other])
                ->selectRaw("{$nameExpr} as name_en", [$other])
                ->selectRaw('SUM(ex.amount) as total')
                ->groupByRaw('COALESCE(ex.expense_category_id, ex.debit_accounting_account_id, 0)')
                ->orderByDesc('total')
                ->get();
        }

        return $q
            ->selectRaw('COALESCE(ex.debit_accounting_account_id, 0) as id')
            ->selectRaw('MAX(COALESCE(NULLIF(aa.name_ar, ""), NULLIF(aa.name_en, ""), ?)) as name_ar', [$other])
            ->selectRaw('MAX(COALESCE(NULLIF(aa.name_en, ""), NULLIF(aa.name_ar, ""), ?)) as name_en', [$other])
            ->selectRaw('SUM(ex.amount) as total')
            ->groupBy('ex.debit_accounting_account_id')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Months covered by the selected filter, capped at 12.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    protected function trendMonths(array $filters)
    {
        $start = $filters['start']->copy()->startOfMonth();
        $end = $filters['end']->copy()->startOfMonth();
        if ($end->lt($start)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        $months = collect();
        $cursor = $start->copy();
        while ($cursor->lte($end) && $months->count() < 36) {
            $months->push($cursor->format('Y-m'));
            $cursor->addMonth();
        }

        if ($months->isEmpty()) {
            return collect(range(5, 0))->map(fn ($i) => Carbon::now()->subMonths($i)->format('Y-m'))->values();
        }

        return $months->count() > 12 ? $months->slice(-12)->values() : $months->values();
    }

    /**
     * @return array<string, mixed>
     */
    protected function expenseOtherCategories(array $filters, bool $isAr): array
    {
        $other = $isAr ? 'أخرى' : 'Others';
        $rows = collect();
        try {
            $rows = $this->expenseDocumentBreakdown($filters, $other);
        } catch (\Throwable $e) {
            report($e);
        }

        $items = $rows
            ->sortByDesc('total')
            ->slice(5)
            ->values()
            ->map(function ($row) use ($isAr) {
                $name = $isAr ? (string) $row->name_ar : (string) $row->name_en;

                return [
                    'id' => (string) $row->id,
                    'ref' => $name !== '' ? $name : '#'.$row->id,
                    'amount' => $this->formatMoney((float) $row->total),
                ];
            })
            ->all();

        return [
            'title' => $isAr ? 'بقية بنود المصروف' : 'Remaining expense items',
            'columns' => [
                ['key' => 'ref', 'label' => $isAr ? 'البند' : 'Item'],
                ['key' => 'amount', 'label' => $isAr ? 'المبلغ' : 'Amount'],
            ],
            'rows' => $items,
            'empty' => $isAr ? 'لا توجد بنود إضافية' : 'No additional items',
            'report_url' => $this->routeIfCan('expense-report', $filters),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $months
     * @return \Illuminate\Support\Collection<string, mixed>
     */
    protected function monthlyTotals(string $type, $months, array $filters)
    {
        $q = DB::table('transactions as t')
            ->selectRaw("DATE_FORMAT(t.transaction_date, '%Y-%m') as month, SUM(t.final_total) as total")
            ->where('t.type', $type)
            ->whereIn('t.status', self::VALID_STATUSES)
            ->whereIn(DB::raw("DATE_FORMAT(t.transaction_date, '%Y-%m')"), $months->all())
            ->groupBy('month');

        if ($type === 'sell') {
            $q->where(function ($inner) {
                $inner->whereNull('t.purpose')
                    ->orWhereNotIn('t.purpose', TransactionPurpose::internalAliases());
            });
            $this->applyTransactionFilters($q, array_merge($filters, ['skip_dates' => true]), 't');
        } else {
            $this->applyTransactionFilters($q, array_merge($filters, ['skip_dates' => true]), 't', withChannel: false);
        }

        return $q->pluck('total', 'month');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $months
     * @return \Illuminate\Support\Collection<string, mixed>
     */
    protected function monthlyExpenses($months, array $filters)
    {
        $q = DB::table('accounting_accounts_transactions as aat')
            ->join('accounting_accounts as aa', 'aa.id', '=', 'aat.accounting_account_id')
            ->selectRaw("DATE_FORMAT(aat.operation_date, '%Y-%m') as month, SUM(aat.amount) as total")
            ->where('aat.type', 'debit')
            ->whereIn(DB::raw("DATE_FORMAT(aat.operation_date, '%Y-%m')"), $months->all())
            ->groupBy('month');

        $accountIds = $this->expenseAccountIds();
        if ($accountIds !== []) {
            $q->whereIn('aat.accounting_account_id', $accountIds);
        } else {
            $q->where(function ($inner) {
                $inner->whereIn('aa.account_primary_type', ['expenses', 'expense'])
                    ->orWhere('aa.account_type', 'expenses');
            });
        }
        $this->applyExpenseFilters($q, $filters, 'aat');

        $fromLedger = $q->pluck('total', 'month');
        if (! Schema::hasTable('expenses')) {
            return $fromLedger;
        }

        $docs = DB::table('expenses as ex')
            ->selectRaw("DATE_FORMAT(ex.date, '%Y-%m') as month, SUM(ex.amount) as total")
            ->whereIn(DB::raw("DATE_FORMAT(ex.date, '%Y-%m')"), $months->all())
            ->groupBy('month');
        if (! empty($filters['activity_id']) && Schema::hasColumn('expenses', 'cost_center_id')) {
            $docs->where('ex.cost_center_id', $filters['activity_id']);
        }
        $fromDocs = $docs->pluck('total', 'month');

        if ($fromDocs->filter(fn ($v) => (float) $v > 0)->isNotEmpty()) {
            return $fromDocs;
        }

        return $fromLedger;
    }

    /**
     * @return array{total: float, overdue: float, overdue_customers: int}
     */
    protected function receivables(array $filters): array
    {
        $paymentsSub = DB::table('transaction_payments as tp')
            ->selectRaw('tp.transaction_id, SUM(IF(tp.is_return = 1, -1 * tp.amount, tp.amount)) as total_paid')
            ->groupBy('tp.transaction_id');

        $q = DB::table('transactions as t')
            ->leftJoinSub($paymentsSub, 'tp_sum', fn ($join) => $join->on('t.id', '=', 'tp_sum.transaction_id'))
            ->where('t.type', 'sell')
            ->whereIn('t.status', self::VALID_STATUSES)
            ->where(function ($inner) {
                $inner->whereNull('t.purpose')
                    ->orWhereNotIn('t.purpose', TransactionPurpose::internalAliases());
            });
        $this->applyTransactionFilters($q, array_merge($filters, ['skip_dates' => true]), 't');

        $total = (float) (clone $q)->selectRaw('SUM(GREATEST(t.final_total - COALESCE(tp_sum.total_paid, 0), 0)) as due')->value('due');
        $overdueQuery = (clone $q)
            ->whereNotNull('t.due_date')
            ->whereDate('t.due_date', '<', now()->toDateString())
            ->whereRaw('GREATEST(t.final_total - COALESCE(tp_sum.total_paid, 0), 0) > 0');
        $overdue = (float) (clone $overdueQuery)->selectRaw('SUM(GREATEST(t.final_total - COALESCE(tp_sum.total_paid, 0), 0)) as due')->value('due');
        $overdueCustomers = (int) (clone $overdueQuery)->distinct()->count('t.contact_id');

        return ['total' => $total, 'overdue' => $overdue, 'overdue_customers' => $overdueCustomers];
    }

    /**
     * @return array{count: int, high_turnover: int}
     */
    protected function lowStock(array $filters): array
    {
        try {
            $lowIds = DB::table('inventory_product_inventories as i')
                ->leftJoin(DB::raw('(SELECT product_id, SUM(qty) AS total_qty FROM product_inventories GROUP BY product_id) as s'), 'i.product_id', '=', 's.product_id')
                ->whereNotNull('i.threshold')
                ->where('i.threshold', '>', 0)
                ->whereRaw('COALESCE(s.total_qty, 0) < i.threshold')
                ->pluck('i.product_id');

            $count = $lowIds->count();
            $high = 0;
            if ($count > 0 && $this->canSales()) {
                $high = (int) DB::table('transaction_sell_lines as tsl')
                    ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
                    ->where('t.type', 'sell')
                    ->whereIn('t.status', self::VALID_STATUSES)
                    ->whereBetween('t.transaction_date', [$filters['start'], $filters['end']])
                    ->whereIn('tsl.product_id', $lowIds)
                    ->distinct()
                    ->count('tsl.product_id');
            }

            return ['count' => $count, 'high_turnover' => $high];
        } catch (\Throwable) {
            return ['count' => 0, 'high_turnover' => 0];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function lowStockRows(int $limit): array
    {
        try {
            return DB::table('inventory_product_inventories as i')
                ->leftJoin(DB::raw('(SELECT product_id, SUM(qty) AS total_qty FROM product_inventories GROUP BY product_id) as s'), 'i.product_id', '=', 's.product_id')
                ->leftJoin('product_products as p', 'p.id', '=', 'i.product_id')
                ->whereNotNull('i.threshold')
                ->where('i.threshold', '>', 0)
                ->whereRaw('COALESCE(s.total_qty, 0) < i.threshold')
                ->selectRaw('i.product_id as id, COALESCE(p.name_ar, p.name_en) as name, COALESCE(s.total_qty, 0) as qty, i.threshold')
                ->orderBy('qty')
                ->limit($limit)
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'qty' => $this->formatNumber((float) $row->qty),
                    'threshold' => $this->formatNumber((float) $row->threshold),
                    'url' => $this->routeIfCan('productInventory.show', ['productInventory' => $row->id]) ?? $this->routeIfCan('inventory.dashboard', []),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array{current: int|null, previous: int|null}
     */
    protected function collectionDays(array $filters): array
    {
        if (! $this->canSales()) {
            return ['current' => null, 'previous' => null];
        }
        $recv = $this->receivables($filters);
        $sales = $this->salesTotal($filters['start'], $filters['end'], $filters);
        $prevSales = $this->salesTotal($filters['prev_start'], $filters['prev_end'], $filters);
        $days = (int) $filters['period_days'];
        $current = $sales > 0 ? (int) round($recv['total'] / ($sales / $days)) : null;
        $previous = $prevSales > 0 ? (int) round($recv['total'] / ($prevSales / $days)) : null;

        return ['current' => $current, 'previous' => $previous];
    }

    protected function overdueInvoiceCount(array $filters): int
    {
        $paymentsSub = DB::table('transaction_payments as tp')
            ->selectRaw('tp.transaction_id, SUM(IF(tp.is_return = 1, -1 * tp.amount, tp.amount)) as total_paid')
            ->groupBy('tp.transaction_id');

        $q = DB::table('transactions as t')
            ->leftJoinSub($paymentsSub, 'tp_sum', fn ($join) => $join->on('t.id', '=', 'tp_sum.transaction_id'))
            ->where('t.type', 'sell')
            ->whereIn('t.status', self::VALID_STATUSES)
            ->whereNotNull('t.due_date')
            ->whereDate('t.due_date', '<', now()->toDateString())
            ->whereRaw('GREATEST(t.final_total - COALESCE(tp_sum.total_paid, 0), 0) > 0');
        $this->applyTransactionFilters($q, array_merge($filters, ['skip_dates' => true]), 't');

        return (int) $q->count();
    }

    protected function pendingApprovals(): int
    {
        try {
            return (int) DB::table('transactions')
                ->whereNotIn('status', self::VALID_STATUSES)
                ->whereIn('type', ['sell', 'purchases', 'TRANSFER', 'purchaseOrder'])
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function salesQuery(Carbon $start, Carbon $end, array $filters, string $alias = 't')
    {
        $q = DB::table('transactions as '.$alias)
            ->where($alias.'.type', 'sell')
            ->whereIn($alias.'.status', self::VALID_STATUSES)
            ->where(function ($inner) use ($alias) {
                $inner->whereNull($alias.'.purpose')
                    ->orWhereNotIn($alias.'.purpose', TransactionPurpose::internalAliases());
            })
            ->whereBetween($alias.'.transaction_date', [$start, $end]);
        $this->applyTransactionFilters($q, array_merge($filters, ['skip_dates' => true]), $alias);

        return $q;
    }

    protected function applyTransactionFilters($query, array $filters, string $alias, bool $withChannel = false): void
    {
        if (empty($filters['skip_dates']) && isset($filters['start'], $filters['end'])) {
            $query->whereBetween($alias.'.transaction_date', [$filters['start'], $filters['end']]);
        }
        if (! empty($filters['month'])) {
            $query->where(DB::raw("DATE_FORMAT({$alias}.transaction_date, '%Y-%m')"), $filters['month']);
        }
        if (! empty($filters['branch_id']) && Schema::hasColumn('transactions', 'establishment_id')) {
            $query->where($alias.'.establishment_id', $filters['branch_id']);
        }
        if (! empty($filters['activity_id']) && Schema::hasColumn('transactions', 'cost_center')) {
            $query->where($alias.'.cost_center', $filters['activity_id']);
        }
        if ($withChannel && ($filters['channel'] ?? 'all') !== 'all' && Schema::hasTable('cash_register_transactions')) {
            if ($filters['channel'] === 'pos') {
                $query->whereExists(function ($q) use ($alias) {
                    $q->select(DB::raw(1))
                        ->from('cash_register_transactions as crt')
                        ->whereColumn('crt.transaction_id', $alias.'.id');
                });
            } elseif ($filters['channel'] === 'invoice') {
                $query->whereNotExists(function ($q) use ($alias) {
                    $q->select(DB::raw(1))
                        ->from('cash_register_transactions as crt')
                        ->whereColumn('crt.transaction_id', $alias.'.id');
                });
            }
        }
    }

    protected function applyExpenseFilters($query, array $filters, string $alias): void
    {
        if (! empty($filters['activity_id'])) {
            $query->where($alias.'.cost_center_id', $filters['activity_id']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('aa.account_sub_type_id', $filters['category_id']);
        }
        if (! empty($filters['month'])) {
            $query->where(DB::raw("DATE_FORMAT({$alias}.operation_date, '%Y-%m')"), $filters['month']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function transactionList(string $type, array $filters, int $limit, bool $isAr, string $title, string $reportRoute, string $showRoute): array
    {
        $q = DB::table('transactions as t')
            ->leftJoin('cs_contacts as c', 'c.id', '=', 't.contact_id')
            ->where('t.type', $type)
            ->whereIn('t.status', self::VALID_STATUSES)
            ->orderByDesc('t.transaction_date')
            ->limit($limit)
            ->selectRaw('t.id, t.ref_no, t.transaction_date, t.final_total, c.name as contact, t.status');

        if ($type === 'sell') {
            $q->where(function ($inner) {
                $inner->whereNull('t.purpose')
                    ->orWhereNotIn('t.purpose', TransactionPurpose::internalAliases());
            });
            $this->applyTransactionFilters($q, $filters, 't');
        } else {
            $this->applyTransactionFilters($q, $filters, 't', withChannel: false);
        }

        $rows = $q->get()->map(function ($row) use ($showRoute) {
            $url = null;
            try {
                $url = route($showRoute, ['transaction' => $row->id]);
            } catch (\Throwable) {
                $url = null;
            }

            return [
                'id' => (int) $row->id,
                'ref' => (string) ($row->ref_no ?: '#'.$row->id),
                'date' => Carbon::parse($row->transaction_date)->toDateString(),
                'contact' => (string) ($row->contact ?: '—'),
                'amount' => $this->formatMoney((float) $row->final_total),
                'url' => $url,
            ];
        })->all();

        return [
            'title' => $title,
            'columns' => [
                ['key' => 'ref', 'label' => $isAr ? 'المرجع' : 'Ref'],
                ['key' => 'date', 'label' => $isAr ? 'التاريخ' : 'Date'],
                ['key' => 'contact', 'label' => $isAr ? 'الطرف' : 'Party'],
                ['key' => 'amount', 'label' => $isAr ? 'المبلغ' : 'Amount'],
            ],
            'rows' => $rows,
            'report_url' => $this->routeIfCan($reportRoute, $filters),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function expenseVouchers(array $filters, int $limit, bool $isAr): array
    {
        $q = DB::table('accounting_accounts_transactions as aat')
            ->join('accounting_accounts as aa', 'aa.id', '=', 'aat.accounting_account_id')
            ->leftJoin('accounting_acc_trans_mappings as m', 'm.id', '=', 'aat.acc_trans_mapping_id')
            ->where('aa.account_primary_type', 'expenses')
            ->where('aat.type', 'debit')
            ->whereBetween('aat.operation_date', [$filters['start'], $filters['end']])
            ->orderByDesc('aat.operation_date')
            ->limit($limit)
            ->selectRaw('aat.id, aat.operation_date, aat.amount, COALESCE(m.ref_no, CONCAT("#", aat.id)) as ref, COALESCE(aa.name_ar, aa.name_en) as name, aat.acc_trans_mapping_id');
        $this->applyExpenseFilters($q, $filters, 'aat');

        $rows = $q->get()->map(function ($row) {
            $url = null;
            if ($row->acc_trans_mapping_id) {
                try {
                    $url = route('journal-entry-show', $row->acc_trans_mapping_id);
                } catch (\Throwable) {
                    $url = null;
                }
            }

            return [
                'id' => (int) $row->id,
                'ref' => (string) $row->ref,
                'date' => Carbon::parse($row->operation_date)->toDateString(),
                'contact' => (string) $row->name,
                'amount' => $this->formatMoney((float) $row->amount),
                'url' => $url,
            ];
        })->all();

        return [
            'title' => $isAr ? 'قيود المصروفات' : 'Expense vouchers',
            'columns' => [
                ['key' => 'ref', 'label' => $isAr ? 'المرجع' : 'Ref'],
                ['key' => 'date', 'label' => $isAr ? 'التاريخ' : 'Date'],
                ['key' => 'contact', 'label' => $isAr ? 'الحساب' : 'Account'],
                ['key' => 'amount', 'label' => $isAr ? 'المبلغ' : 'Amount'],
            ],
            'rows' => $rows,
            'report_url' => $this->routeIfCan('expense-report', $filters),
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function permissions(): array
    {
        $user = auth()->user();
        $can = fn (string $perm) => DashboardAccess::allows($user, $perm);

        return [
            'dashboard' => $can(DashboardHubPermissions::DASHBOARD_SHOW),
            'sales' => $this->canSales(),
            'purchases' => $this->canPurchases(),
            'inventory' => $can(DashboardHubPermissions::INVENTORY_SHOW),
            'accounting' => $can(DashboardHubPermissions::ACCOUNTING_SHOW),
            'create_invoice' => $can(SalesPermissions::INVOICES_CREATE),
            'create_purchase_order' => $can(PurchasesPermissions::ORDERS_CREATE),
            'create_transfer' => $can(InventoryPermissions::TRANSFER_CREATE),
            'create_receipt' => $can(AccountingPermissions::RECEIPT_CREATE) || $can(SalesPermissions::RECEIPTS_CREATE),
            'create_journal' => $can(AccountingPermissions::JOURNAL_CREATE),
            'approve' => $can(SalesPermissions::INVOICES_CREATE) || $can(InventoryPermissions::TRANSFER_UPDATE),
            'income_statement' => $can(AccountingPermissions::INCOME_STATEMENT_SHOW),
            'expense_report' => $can(AccountingPermissions::EXPENSE_REPORT_SHOW),
            'products' => $this->canProducts(),
        ];
    }

    /**
     * @param  array<string, bool>  $permissions
     * @param  array<string, mixed>  $filters
     * @return array<string, string|null>
     */
    protected function safeRoutes(array $permissions, array $filters): array
    {
        $qs = $this->queryFromFilters($filters);

        return [
            'sales_report' => $permissions['sales'] ? $this->routeIfCan('sales-dashbord', $filters) : null,
            'purchases_report' => $permissions['purchases'] ? $this->routeIfCan('purchase-dashbord', $filters) : null,
            'expense_report' => $permissions['expense_report'] ? $this->routeIfCan('expense-report', $filters) : null,
            'income_statement' => $permissions['income_statement'] ? $this->routeIfCan('income-statement', $filters) : null,
            'invoices' => $permissions['sales'] ? $this->routeIfCan('invoices', $filters) : null,
            'aging' => $this->routeIfCan('customers-suppliers-statement', $filters),
            'inventory' => $permissions['inventory'] ? $this->routeIfCan('inventory.dashboard', $filters) : null,
            'create_invoice' => $permissions['create_invoice'] ? $this->named('create-invoice') : null,
            'create_purchase_order' => $permissions['create_purchase_order'] ? $this->named('create-purchase-order') : null,
            'create_transfer' => $permissions['create_transfer'] ? $this->named('transfer.create') : null,
            'create_receipt' => $permissions['create_receipt'] ? ($this->named('create-receipts') ?: $this->named('receipt-vouchers')) : null,
            'create_journal' => $permissions['create_journal'] ? $this->named('journal-entry-create') : null,
            'approve' => $this->named('invoices'),
            'compare' => $permissions['income_statement'] ? $this->routeIfCan('income-statement', $filters) : null,
            'product_dashboard' => ! empty($permissions['products']) ? $this->named('product.dashboard') : null,
            'accounting_dashboard' => ! empty($permissions['accounting']) ? $this->named('accounting-dashboard') : null,
            'query' => $qs,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function routeIfCan(string $name, array $filters): ?string
    {
        $url = $this->named($name, is_array($filters) && isset($filters['productInventory']) ? $filters : []);
        if (! $url) {
            return null;
        }
        $qs = $this->queryFromFilters($filters);
        if ($qs === []) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($qs);
    }

    protected function named(string $name, array $params = []): ?string
    {
        try {
            return route($name, $params);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function queryFromFilters(array $filters): array
    {
        $qs = [];
        if (! empty($filters['start'])) {
            $qs['start_date'] = $filters['start'] instanceof Carbon ? $filters['start']->toDateString() : $filters['start'];
        }
        if (! empty($filters['end'])) {
            $qs['end_date'] = $filters['end'] instanceof Carbon ? $filters['end']->toDateString() : $filters['end'];
        }
        if (! empty($filters['activity_id'])) {
            $qs['choose_cost_center_select'] = [$filters['activity_id']];
        }

        return $qs;
    }

    protected function canAccounting(): bool
    {
        $entitled = function_exists('tenant_entitled') ? tenant_entitled('accounting') : true;

        return $entitled && DashboardAccess::allows(auth()->user(), [
            DashboardHubPermissions::ACCOUNTING_SHOW,
            AccountingPermissions::DASHBOARD_SHOW,
        ]);
    }

    protected function canInventory(): bool
    {
        $entitled = function_exists('tenant_entitled') ? tenant_entitled('inventory') : true;

        return $entitled && DashboardAccess::allows(auth()->user(), [
            DashboardHubPermissions::INVENTORY_SHOW,
            InventoryPermissions::DASHBOARD_SHOW,
            InventoryPermissions::PRODUCT_SHOW,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function inventoryIssueDrilldown(string $source, array $filters, int $limit, bool $isAr): array
    {
        $service = app(InventoryStockHealthService::class);
        $warehouseId = ! empty($filters['branch_id']) ? (int) $filters['branch_id'] : null;
        $warehouseIds = $service->warehouseIds($warehouseId);
        $report = $this->named('inventory.dashboard');

        if ($source === 'waste-ops') {
            $rows = $service->wasteRows($warehouseIds, $filters['start'], $filters['end'], $limit)->map(function ($row) {
                $date = $row->transaction_date ?: $row->created_at;

                return [
                    'id' => (int) $row->id,
                    'ref' => (string) ($row->ref_no ?: '#'.$row->id),
                    'date' => $date ? Carbon::parse($date)->toDateString() : '—',
                    'url' => $this->named('waste.show', ['waste' => $row->id]),
                ];
            })->all();

            return [
                'title' => $isAr ? 'عمليات إتلاف معتمدة' : 'Approved waste operations',
                'columns' => [
                    ['key' => 'ref', 'label' => $isAr ? 'المرجع' : 'Ref'],
                    ['key' => 'date', 'label' => $isAr ? 'التاريخ' : 'Date'],
                ],
                'rows' => $rows,
                'action_label' => $isAr ? 'عرض العملية' : 'Open operation',
                'report_url' => $this->named('waste.index') ?: $report,
            ];
        }

        $mode = $source === 'negative-stock' ? 'negative' : ($source === 'zero-stock' ? 'zero' : 'critical');
        $rows = $service->stockRows($warehouseIds, $mode, max($limit, 50))->map(function ($row) use ($isAr) {
            $arName = trim((string) ($row->name_ar ?? ''));
            $enName = trim((string) ($row->name_en ?? ''));
            $name = $isAr ? ($arName !== '' ? $arName : $enName) : ($enName !== '' ? $enName : $arName);

            return [
                'id' => (int) $row->product_id.'-'.(int) $row->establishment_id,
                'ref' => $name !== '' ? $name : '#'.$row->product_id,
                'warehouse' => (string) ($row->warehouse_name ?? '—'),
                'qty' => $this->formatNumber((float) $row->qty),
                'url' => $this->named('productInventory.index'),
            ];
        })->all();

        $titles = [
            'negative' => $isAr ? 'منتجات برصيد سالب' : 'Negative stock',
            'zero' => $isAr ? 'منتجات برصيد صفر' : 'Zero stock',
            'critical' => $isAr ? 'عناصر حرجة (رصيد ≤ 0)' : 'Critical items (qty ≤ 0)',
        ];

        return [
            'title' => $titles[$mode],
            'columns' => [
                ['key' => 'ref', 'label' => $isAr ? 'الصنف' : 'Item'],
                ['key' => 'warehouse', 'label' => $isAr ? 'المستودع' : 'Warehouse'],
                ['key' => 'qty', 'label' => $isAr ? 'الرصيد' : 'Qty'],
            ],
            'rows' => $rows,
            'empty' => $isAr ? 'لا توجد أصناف ضمن هذا التنبيه' : 'No items in this alert',
            'action_label' => $isAr ? 'فتح المخزون' : 'Open inventory',
            'report_url' => $report,
        ];
    }

    protected function canProducts(): bool
    {
        $entitled = function_exists('tenant_entitled') ? tenant_entitled('products') : true;

        return $entitled && DashboardAccess::allows(auth()->user(), [
            DashboardHubPermissions::PRODUCTS_SHOW,
            ProductPermissions::DASHBOARD_SHOW,
            ProductPermissions::PRODUCT_SHOW,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function productIssueDrilldown(string $source, int $limit, bool $isAr): array
    {
        $health = app(ProductCatalogHealthService::class);
        $canEdit = DashboardAccess::allows(auth()->user(), ProductPermissions::PRODUCT_UPDATE);
        $query = $source === 'negative-margin' ? $health->negativeMarginQuery() : $health->zeroPriceQuery();
        $rows = $query
            ->select('id', 'name_ar', 'name_en', 'cost', 'price_with_tax')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($isAr, $canEdit, $source) {
                $arName = trim((string) ($row->name_ar ?? ''));
                $enName = trim((string) ($row->name_en ?? ''));
                $name = $isAr ? ($arName !== '' ? $arName : $enName) : ($enName !== '' ? $enName : $arName);
                $item = [
                    'id' => (int) $row->id,
                    'ref' => $name !== '' ? $name : '#'.$row->id,
                    'price' => $this->formatMoney((float) ($row->price_with_tax ?? 0)),
                    'url' => $canEdit ? $this->named('product.edit', ['product' => $row->id]) : null,
                ];
                if ($source === 'negative-margin') {
                    $item['cost'] = $this->formatMoney((float) ($row->cost ?? 0));
                    $item['gap'] = $this->formatMoney((float) ($row->price_with_tax ?? 0) - (float) ($row->cost ?? 0));
                }

                return $item;
            })
            ->all();

        $columns = [
            ['key' => 'ref', 'label' => $isAr ? 'المنتج' : 'Product'],
        ];
        if ($source === 'negative-margin') {
            $columns[] = ['key' => 'cost', 'label' => $isAr ? 'التكلفة' : 'Cost'];
            $columns[] = ['key' => 'price', 'label' => $isAr ? 'سعر البيع' : 'Sell price'];
            $columns[] = ['key' => 'gap', 'label' => $isAr ? 'فارق الربحية' : 'Margin gap'];
        } else {
            $columns[] = ['key' => 'price', 'label' => $isAr ? 'السعر' : 'Price'];
        }

        return [
            'title' => $source === 'negative-margin'
                ? ($isAr ? 'منتجات بخسارة' : 'Loss-making products')
                : ($isAr ? 'منتجات تحتاج تصحيح سعر' : 'Products needing a price fix'),
            'columns' => $columns,
            'rows' => $rows,
            'action_label' => $source === 'negative-margin'
                ? ($isAr ? 'تعديل السعر' : 'Edit price')
                : ($isAr ? 'تصحيح السعر' : 'Fix price'),
            'report_url' => $this->named('product.dashboard'),
        ];
    }

    protected function canSales(): bool
    {
        $entitled = function_exists('tenant_entitled') ? tenant_entitled('sales') : true;

        return $entitled && DashboardAccess::allows(auth()->user(), [
            SalesPermissions::DASHBOARD_SHOW,
            SalesPermissions::INVOICES_SHOW,
        ]);
    }

    protected function canPurchases(): bool
    {
        $entitled = function_exists('tenant_entitled') ? tenant_entitled('purchases') : true;

        return $entitled && DashboardAccess::allows(auth()->user(), [
            PurchasesPermissions::DASHBOARD_SHOW,
            PurchasesPermissions::INVOICES_SHOW,
        ]);
    }

    protected function formatMoney(float $amount): string
    {
        return number_format($amount, 2);
    }

    protected function formatNumber(float $amount): string
    {
        if (abs($amount - round($amount)) < 0.001) {
            return number_format($amount, 0);
        }

        return number_format($amount, 2);
    }
}
