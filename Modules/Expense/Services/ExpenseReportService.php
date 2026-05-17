<?php

namespace Modules\Expense\Services;

use App\Helpers\CurrencyHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountsRoting;
use Modules\Accounting\Utils\PerpetualInventoryAccountResolver;
use Modules\Expense\Models\Expense;
use Modules\General\Models\Setting;

final class ExpenseReportService
{
    public const CATEGORY_KEYS = [
        'purchases',
        'operating',
        'administrative',
        'marketing',
        'payroll',
        'rent',
        'services',
        'transport',
        'tax',
        'other',
    ];

    /** @var array<string, string> */
    public const CHART_COLORS = [
        '#1B84FF',
        '#17C653',
        '#F6C000',
        '#7239EA',
        '#F8285A',
        '#50CD89',
        '#009EF7',
        '#78829D',
        '#4B5675',
    ];

    /** @var array<int, int>|null */
    private static ?array $reportableAccountIdsCache = null;

    /** @var array<int, int>|null */
    private static ?array $purchaseRoutingAccountIdsCache = null;

    /** @var array<int, int>|null */
    private static ?array $vatRoutingAccountIdsCache = null;

    /**
     * Debit-side GL lines on chart-of-accounts scope (expenses + purchases routing + COGS).
     */
    public static function ledgerFilteredQuery(Request $request): Builder
    {
        $accountIds = static::reportableAccountIds();
        $table = (new AccountingAccountsTransaction)->getTable();

        $query = AccountingAccountsTransaction::query()
            ->where("{$table}.type", 'debit')
            ->whereIn("{$table}.accounting_account_id", $accountIds);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate) {
            $query->whereDate("{$table}.operation_date", '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate("{$table}.operation_date", '<=', $endDate);
        }

        $debitIds = array_values(array_filter(array_map('intval', (array) $request->input('debit_account_ids', []))));
        if ($debitIds !== []) {
            $query->whereIn("{$table}.accounting_account_id", $debitIds);
        }

        $creditIds = array_values(array_filter(array_map('intval', (array) $request->input('credit_account_ids', []))));
        if ($creditIds !== []) {
            $query->whereIn("{$table}.acc_trans_mapping_id", function ($sub) use ($creditIds) {
                $sub->select('acc_trans_mapping_id')
                    ->from('accounting_accounts_transactions')
                    ->where('type', 'credit')
                    ->whereIn('accounting_account_id', $creditIds)
                    ->whereNotNull('acc_trans_mapping_id');
            });
        }

        $costCenterIds = array_values(array_filter(array_map('intval', (array) $request->input('cost_center_ids', []))));
        if ($costCenterIds !== []) {
            $query->whereIn("{$table}.cost_center_id", $costCenterIds);
        }

        $createdByIds = array_values(array_filter(array_map('intval', (array) $request->input('created_by_ids', []))));
        if ($createdByIds !== []) {
            $query->whereIn("{$table}.created_by", $createdByIds);
        }

        $categoryFilters = array_values(array_intersect(
            (array) $request->input('expense_categories', []),
            self::CATEGORY_KEYS
        ));
        if ($categoryFilters !== []) {
            $allowedAccountIds = static::accountIdsForCategories($categoryFilters);
            if ($allowedAccountIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn("{$table}.accounting_account_id", $allowedAccountIds);
            }
        }

        if ($request->boolean('with_attachments')) {
            $mappingIds = Expense::query()
                ->has('attachments')
                ->whereNotNull('acc_trans_mapping_id')
                ->pluck('acc_trans_mapping_id');
            $query->whereIn("{$table}.acc_trans_mapping_id", $mappingIds);
        }

        $taxId = $request->input('tax_id');
        if ($taxId !== null && $taxId !== '' && $taxId !== 'all') {
            if ($taxId === 'none') {
                $query->whereNotIn("{$table}.accounting_account_id", static::vatRoutingAccountIds());
                $query->whereNotIn("{$table}.acc_trans_mapping_id", function ($sub) {
                    $sub->select('acc_trans_mapping_id')
                        ->from('expenses')
                        ->whereNotNull('acc_trans_mapping_id')
                        ->where(function ($q) {
                            $q->whereNull('tax_id')->orWhere('tax', '<=', 0);
                        });
                });
            } else {
                $query->where(function ($q) use ($taxId, $table) {
                    $q->whereIn("{$table}.acc_trans_mapping_id", function ($sub) use ($taxId) {
                        $sub->select('acc_trans_mapping_id')
                            ->from('expenses')
                            ->where('tax_id', (int) $taxId)
                            ->whereNotNull('acc_trans_mapping_id');
                    })->orWhereIn("{$table}.accounting_account_id", static::vatRoutingAccountIds());
                });
            }
        }

        $keyword = trim((string) $request->input('q', ''));
        if ($keyword !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $keyword).'%';
            $query->where(function ($q) use ($like, $table) {
                $q->where("{$table}.note", 'like', $like)
                    ->orWhereHas('accTransMapping', fn ($m) => $m->where('note', 'like', $like)->orWhere('ref_no', 'like', $like))
                    ->orWhereHas('account', function ($a) use ($like) {
                        $a->where('name_ar', 'like', $like)
                            ->orWhere('name_en', 'like', $like)
                            ->orWhere('gl_code', 'like', $like);
                    })
                    ->orWhereHas('transaction', function ($t) use ($like) {
                        $t->where('invoice_no', 'like', $like)
                            ->orWhere('ref_no', 'like', $like);
                    });
            });
        }

        return $query;
    }

    /** @deprecated Use ledgerFilteredQuery — kept for exports referencing baseQuery type */
    public static function filteredQuery(Request $request): Builder
    {
        return static::ledgerFilteredQuery($request);
    }

    /** @return array<int, int> */
    public static function reportableAccountIds(): array
    {
        if (static::$reportableAccountIdsCache !== null) {
            return static::$reportableAccountIdsCache;
        }

        $fromCoa = AccountingAccount::query()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereIn('account_primary_type', ['expenses', 'expense'])
                    ->orWhere('account_type', 'expenses');
            })
            ->pluck('id');

        static::$reportableAccountIdsCache = $fromCoa
            ->merge(static::purchaseRoutingAccountIds())
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return static::$reportableAccountIdsCache;
    }

    public static function reportableAccountsQuery(): Builder
    {
        return AccountingAccount::query()
            ->where('status', 'active')
            ->whereIn('id', static::reportableAccountIds())
            ->orderBy('gl_code');
    }

    /** @return array<int, int> */
    public static function purchaseRoutingAccountIds(): array
    {
        if (static::$purchaseRoutingAccountIdsCache !== null) {
            return static::$purchaseRoutingAccountIdsCache;
        }

        $ids = AccountsRoting::query()
            ->whereIn('type', ['purchases_purchase', 'purchases_earned_discount'])
            ->pluck('account_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        if (Setting::isPerpetualInventory()) {
            $inventoryId = PerpetualInventoryAccountResolver::resolveInventoryAssetAccountId(null);
            if ($inventoryId) {
                $ids[] = (int) $inventoryId;
            }
        }

        static::$purchaseRoutingAccountIdsCache = array_values(array_unique($ids));

        return static::$purchaseRoutingAccountIdsCache;
    }

    /** @return array<int, int> */
    public static function vatRoutingAccountIds(): array
    {
        if (static::$vatRoutingAccountIdsCache !== null) {
            return static::$vatRoutingAccountIdsCache;
        }

        static::$vatRoutingAccountIdsCache = AccountsRoting::query()
            ->where('type', 'purchases_vat_calculation')
            ->pluck('account_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return static::$vatRoutingAccountIdsCache;
    }

    public static function isVatRoutingAccount(?AccountingAccount $account): bool
    {
        if ($account === null) {
            return false;
        }

        return in_array((int) $account->id, static::vatRoutingAccountIds(), true);
    }

    public static function isPurchaseRoutingAccount(?AccountingAccount $account): bool
    {
        if ($account === null) {
            return false;
        }

        return in_array((int) $account->id, static::purchaseRoutingAccountIds(), true)
            && ! static::isVatRoutingAccount($account);
    }

    public static function resolveLineCategory(?AccountingAccount $account, string $subType = ''): string
    {
        if ($account === null) {
            return 'other';
        }

        if (static::isVatRoutingAccount($account)) {
            return 'tax';
        }

        if ($subType === 'purchases' || static::isPurchaseRoutingAccount($account)) {
            return 'purchases';
        }

        return static::resolveExpenseCategory($account);
    }

    public static function resolveExpenseCategory(?AccountingAccount $account): string
    {
        if ($account === null) {
            return 'other';
        }

        $account->loadMissing('account_sub_type');
        $subtype = strtolower(trim((string) ($account->account_sub_type?->name_en ?? '')));
        $name = strtolower(trim(($account->name_en ?? '').' '.($account->name_ar ?? '')));

        $subtypeMap = [
            'cost of sales' => 'operating',
            'payroll & hr' => 'payroll',
            'office expenses' => 'administrative',
            'it' => 'services',
            'travel' => 'transport',
            'government fees' => 'tax',
            'professional services' => 'services',
            'insurance' => 'administrative',
            'maintenance' => 'services',
            'pr' => 'marketing',
            'banking' => 'other',
            'other expenses' => 'other',
        ];

        if (isset($subtypeMap[$subtype])) {
            return $subtypeMap[$subtype];
        }

        if (str_contains($name, 'rent') || str_contains($name, 'إيجار') || str_contains($name, 'lease')) {
            return 'rent';
        }
        if (str_contains($name, 'market') || str_contains($name, 'إعلان') || str_contains($name, 'تسويق')) {
            return 'marketing';
        }
        if (str_contains($name, 'payroll') || str_contains($name, 'راتب') || str_contains($name, 'أجور')) {
            return 'payroll';
        }
        if (str_contains($name, 'vat') || str_contains($name, 'ضريبة') || str_contains($name, 'zakat')) {
            return 'tax';
        }
        if (str_contains($name, 'ship') || str_contains($name, 'freight') || str_contains($name, 'نقل') || str_contains($name, 'شحن')) {
            return 'transport';
        }
        if (str_contains($name, 'operat') || str_contains($name, 'تشغيل')) {
            return 'operating';
        }
        if (str_contains($name, 'purchase') || str_contains($name, 'مشتريات') || str_contains($name, 'مخزون') || str_contains($name, 'inventory')) {
            return 'purchases';
        }

        return 'other';
    }

    public static function sourceLabel(string $subType): string
    {
        return match ($subType) {
            'purchases' => __('accounting::lang.journal_source_purchases'),
            'expense' => __('accounting::lang.expense_report_source_expense'),
            'journal_entry', 'manual_journal' => __('accounting::lang.journal_entry'),
            'payment_voucher' => __('accounting::lang.payment_voucher'),
            'receipt_voucher' => __('accounting::lang.receipt_voucher'),
            default => __('accounting::lang.expense_report_source_other'),
        };
    }

    public static function categoryLabel(string $key): string
    {
        return __('accounting::lang.expense_cat_'.$key);
    }

    /** @return array<int, int> */
    private static function accountIdsForCategories(array $categoryFilters): array
    {
        return AccountingAccount::query()
            ->with('account_sub_type')
            ->whereIn('id', static::reportableAccountIds())
            ->get()
            ->filter(function (AccountingAccount $account) use ($categoryFilters) {
                foreach ($categoryFilters as $category) {
                    if (static::resolveLineCategory($account) === $category) {
                        return true;
                    }
                }

                return false;
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return Collection<int, ExpenseReportLine> */
    public static function fetchReportLines(Request $request): Collection
    {
        $lines = (clone static::ledgerFilteredQuery($request))
            ->with([
                'account.account_sub_type',
                'costCenter',
                'accTransMapping.transactions.account',
                'transaction',
            ])
            ->orderByDesc('operation_date')
            ->orderByDesc('id')
            ->get();

        $mappingIds = $lines->pluck('acc_trans_mapping_id')->filter()->unique()->values();
        $expensesByMapping = Expense::query()
            ->whereIn('acc_trans_mapping_id', $mappingIds)
            ->withCount('attachments')
            ->get()
            ->keyBy('acc_trans_mapping_id');

        $grossTotal = max((float) $lines->sum('amount'), 0.000001);

        return $lines->map(function (AccountingAccountsTransaction $line) use ($expensesByMapping, $grossTotal) {
            $linked = $line->acc_trans_mapping_id
                ? $expensesByMapping->get($line->acc_trans_mapping_id)
                : null;

            return ExpenseReportLine::fromLedgerTransaction($line, $grossTotal, $linked);
        });
    }

    /** @return array{count: int, net: float, tax: float, gross: float} */
    public static function summarizeLines(Collection $lines): array
    {
        return [
            'count' => $lines->count(),
            'gross' => round((float) $lines->sum('total'), 2),
            'tax' => round((float) $lines->sum('tax'), 2),
            'net' => round((float) $lines->sum('net'), 2),
        ];
    }

    public static function accountBreakdownFromLines(Collection $lines): Collection
    {
        $localeAr = app()->getLocale() === 'ar';

        return $lines
            ->groupBy(fn (ExpenseReportLine $line) => (int) ($line->debitAccount?->id ?? 0))
            ->map(function (Collection $group) use ($localeAr) {
                $account = $group->first()->debitAccount;

                return (object) [
                    'account_id' => $account?->id,
                    'account_gl_code' => $account?->gl_code,
                    'account_name' => $account
                        ? ($localeAr ? $account->name_ar : $account->name_en)
                        : '—',
                    'expense_count' => $group->count(),
                    'gross_total' => (float) $group->sum('total'),
                    'tax_total' => (float) $group->sum('tax'),
                    'net_total' => (float) $group->sum('net'),
                ];
            })
            ->sortByDesc('gross_total')
            ->values();
    }

    public static function categoryBreakdown(Collection $lines, float $grossTotal): Collection
    {
        $grouped = $lines->groupBy(fn ($line) => $line->category_key ?? 'other');

        return collect(self::CATEGORY_KEYS)
            ->map(function (string $key) use ($grouped, $grossTotal) {
                $items = $grouped->get($key, collect());
                $gross = (float) $items->sum(fn ($line) => (float) $line->total);
                $net = (float) $items->sum(fn ($line) => (float) $line->net);
                $tax = (float) $items->sum(fn ($line) => (float) $line->tax);

                return (object) [
                    'category_key' => $key,
                    'category_label' => self::categoryLabel($key),
                    'expense_count' => $items->count(),
                    'gross_total' => $gross,
                    'net_total' => $net,
                    'tax_total' => $tax,
                    'share_percent' => $grossTotal > 0.0001 ? round(($gross / $grossTotal) * 100, 1) : 0.0,
                    'expenses' => $items->sortByDesc(fn ($line) => $line->date)->values(),
                ];
            })
            ->filter(fn ($row) => $row->expense_count > 0)
            ->sortByDesc('gross_total')
            ->values();
    }

    public static function monthlyTrendFromLines(Collection $lines): array
    {
        $rows = $lines
            ->groupBy(fn (ExpenseReportLine $line) => $line->date->format('Y-m'))
            ->sortKeys();

        return [
            'labels' => $rows->keys()->all(),
            'gross' => $rows->map(fn (Collection $group) => round((float) $group->sum('total'), 2))->values()->all(),
            'counts' => $rows->map(fn (Collection $group) => $group->count())->values()->all(),
        ];
    }

    public static function buildChartPayload(Collection $byCategory): array
    {
        $labels = [];
        $series = [];
        $colors = [];

        foreach ($byCategory as $index => $row) {
            $labels[] = $row->category_label;
            $series[] = round((float) $row->gross_total, 2, PHP_ROUND_HALF_UP);
            $colors[] = self::CHART_COLORS[$index % count(self::CHART_COLORS)];
        }

        return compact('labels', 'series', 'colors');
    }

    public static function buildAnalytics(
        array $summary,
        Collection $byCategory,
        array $monthlyTrend,
        ?array $compareSummary
    ): array {
        $gross = (float) ($summary['gross'] ?? 0);
        $top = $byCategory->first();
        $operatingGross = (float) $byCategory
            ->whereIn('category_key', ['operating', 'purchases'])
            ->sum('gross_total');
        $monthCount = max(count($monthlyTrend['labels'] ?? []), 1);
        $avgMonthly = $gross / $monthCount;

        $growth = null;
        if ($compareSummary !== null && ($compareSummary['gross'] ?? 0) > 0.0001) {
            $growth = CurrencyHelper::growth_percent($gross, (float) $compareSummary['gross']);
        }

        $highThreshold = $byCategory->isEmpty()
            ? 0
            : (float) $byCategory->max('gross_total') * 0.15;

        return [
            'top_category' => $top?->category_label,
            'top_category_amount' => (float) ($top?->gross_total ?? 0),
            'operating_percent' => $gross > 0.0001 ? round(($operatingGross / $gross) * 100, 1) : 0,
            'avg_monthly' => round($avgMonthly, 2),
            'growth_percent' => $growth,
            'high_amount_threshold' => $highThreshold,
            'lowest_category' => $byCategory->sortBy('gross_total')->first()?->category_label,
        ];
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

    /** @return array<string, mixed> */
    public static function dataset(Request $request): array
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $compareMode = $request->input('compare_mode', 'none');

        $request->merge([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $baseQuery = static::ledgerFilteredQuery($request);
        $lines = static::fetchReportLines($request);
        $summary = static::summarizeLines($lines);
        $grossTotal = max((float) ($summary['gross'] ?? 0), 0.000001);

        $lines = $lines->map(function (ExpenseReportLine $line) use ($grossTotal) {
            $line->share_percent = round(((float) $line->total / $grossTotal) * 100, 2);

            return $line;
        });

        $byCategory = static::categoryBreakdown($lines, $grossTotal);
        $byAccount = static::accountBreakdownFromLines($lines);
        $monthlyTrend = static::monthlyTrendFromLines($lines);
        $chart = static::buildChartPayload($byCategory);

        $compareSummary = null;
        $compareByCategory = null;
        $comparePeriod = null;

        if (in_array($compareMode, ['previous_period', 'previous_year'], true)) {
            $comparePeriod = static::resolveComparePeriod($startDate, $endDate, $compareMode);
            $compareRequest = clone $request;
            $compareRequest->merge($comparePeriod);
            $compareLines = static::fetchReportLines($compareRequest);
            $compareSummary = static::summarizeLines($compareLines);
            $compareByCategory = static::categoryBreakdown(
                $compareLines,
                max((float) ($compareSummary['gross'] ?? 0), 0.000001)
            )->keyBy('category_key');
        }

        $byCategory = $byCategory->map(function ($row) use ($compareByCategory) {
            $prev = $compareByCategory?->get($row->category_key);
            $row->compare_gross = (float) ($prev->gross_total ?? 0);
            $row->growth_percent = CurrencyHelper::growth_percent(
                (float) $row->gross_total,
                (float) ($prev->gross_total ?? 0)
            );

            return $row;
        });

        $analytics = static::buildAnalytics($summary, $byCategory, $monthlyTrend, $compareSummary);

        return compact(
            'startDate',
            'endDate',
            'summary',
            'lines',
            'byAccount',
            'byCategory',
            'monthlyTrend',
            'chart',
            'analytics',
            'compareSummary',
            'comparePeriod',
            'compareMode',
            'baseQuery',
            'grossTotal'
        ) + [
            'expenses' => $lines,
        ];
    }
}
