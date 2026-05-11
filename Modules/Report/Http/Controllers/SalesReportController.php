<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Employee\Models\Employee;
use Modules\Establishment\Models\Establishment;
use Modules\Establishment\Models\EstPos;
use Modules\General\Http\Controllers\TransactionController;
use Modules\General\Models\CashRegister;
use Modules\General\Models\PaymentMethod;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionPayments;
use Modules\General\Models\TransactionSellLine;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Subcategory;
use Modules\Report\Exports\ProductSalesExcelExport;
use Modules\Report\Exports\SalesComparisonExcelExport;
use Modules\Report\Exports\SellPaymentExcelExport;
use Modules\Report\Utils\ReportTransactionsUtile;
use Modules\Report\Utils\SalesComparisonPeriodResolver;
use Modules\Report\Utils\TransactionUtile;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Yajra\DataTables\Facades\DataTables;

class SalesReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $transactionUtile = new ReportTransactionsUtile;
        $salesSummary = $transactionUtile->getSalesSummary();

        // return view('reports.sales_summary', compact('salesSummary'));
        return view('report::sales.index', compact('salesSummary'));
    }

    public function combinedPaymentReport()
    {
        return view('report::sales.combined_payment_report');
    }

    public function getBranches(Request $request)
    {
        try {
            $branches = Establishment::where('is_main', 0)->get();

            return response()->json([
                'success' => true,
                'data' => $branches,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching branches: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getSupplier(Request $request)
    {
        try {
            $suppliers = Contact::where('business_type', 'supplier')->get();

            return response()->json([
                'success' => true,
                'data' => $suppliers,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching suppliers: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getCustomers(Request $request)
    {
        try {
            $suppliers = Contact::where('business_type', 'customer')->get();

            return response()->json([
                'success' => true,
                'data' => $suppliers,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching suppliers: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getDevices(Request $request)
    {
        try {
            $devices = EstPos::get();

            return response()->json([
                'success' => true,
                'data' => $devices,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching devices: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getProducts(Request $request)
    {
        try {
            $lang = app()->getLocale();
            $products = Product::whereIn('type', ['ingredint', 'product'])
                ->get()
                ->map(function ($product) use ($lang) {
                    return [
                        'name' => $lang === 'ar' ? $product->name_ar : $product->name_en,
                        'type' => $product->type,
                        'id' => $product->id,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $products,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching products: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getSalesData(Request $request)
    {
        $from = $request->from;
        $to = $request->to;

        $sales = Transaction::where('type', 'sell')->selectRaw('DATE(transaction_date) as date, COUNT(*) as count')
            ->whereBetween('transaction_date', [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $counts = [];

        foreach ($sales as $sale) {
            $labels[] = Carbon::parse($sale->date)->format('M d');
            $counts[] = $sale->count;
        }

        return response()->json([
            'data' => [
                'labels' => $labels,
                'sales_count' => $counts,
            ],
        ]);
    }

    public function getproductSellReport(Request $request)
    {
        $transactionUtile = new ReportTransactionsUtile;

        if ($request->ajax()) {
            $mode = $request->input('report_mode', 'detail');
            if ($mode === 'summary') {
                $query = $this->buildProductSellReportSummaryQuery($request);

                return ReportTransactionsUtile::getProductSalesReportSummary($query);
            }

            $query = $this->buildProductSellReportDetailQuery($request);

            return ReportTransactionsUtile::getProductSalesReport($query);
        }

        $columns = $transactionUtile->getsProductSalesColumns();

        return view('report::sales.indexProductSalesReport')
            ->with(compact(
                'columns'
            ));
    }

    public function productSalesExportExcel(Request $request)
    {
        $isSummary = $request->input('report_mode', 'detail') === 'summary';
        $exportKeys = $this->resolveProductSalesExportColumnKeys($request);
        $query = $isSummary
            ? $this->buildProductSellReportSummaryQuery($request)
            : $this->buildProductSellReportDetailQuery($request);
        $rows = $query->get();
        $mapped = $rows->map(fn ($r) => ReportTransactionsUtile::mapProductSalesRowForExport($r, $isSummary, $exportKeys));
        $meta = $this->buildProductSalesExportMeta($request, $mapped->count(), $exportKeys);

        $filename = 'product-sales-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new ProductSalesExcelExport($mapped, $meta), $filename);
    }

    public function productSalesExportPdf(Request $request)
    {
        $isSummary = $request->input('report_mode', 'detail') === 'summary';
        $exportKeys = $this->resolveProductSalesExportColumnKeys($request);
        $query = $isSummary
            ? $this->buildProductSellReportSummaryQuery($request)
            : $this->buildProductSellReportDetailQuery($request);
        $rows = $query->get();
        $mapped = $rows->map(fn ($r) => ReportTransactionsUtile::mapProductSalesRowForExport($r, $isSummary, $exportKeys));
        $meta = $this->buildProductSalesExportMeta($request, $mapped->count(), $exportKeys);
        $headers = ReportTransactionsUtile::getProductSalesExportHeaderLabels($exportKeys);
        $textStartColIndexes = ReportTransactionsUtile::productSalesExportPdfTextStartIndexes($exportKeys);

        $html = view('report::sales.product_sales_export_pdf', [
            'meta' => $meta,
            'headers' => $headers,
            'rows' => $mapped->all(),
            'textStartColIndexes' => $textStartColIndexes,
        ])->render();

        if (! is_dir(storage_path('temp/mpdf'))) {
            @mkdir(storage_path('temp/mpdf'), 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 6,
            'margin_right' => 6,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => storage_path('temp/mpdf'),
        ]);
        if (app()->getLocale() === 'ar') {
            $mpdf->SetDirectionality('rtl');
        }
        $mpdf->WriteHTML($html);
        $filename = 'product-sales-'.now()->format('Y-m-d-His').'.pdf';
        $binary = $mpdf->Output('', Destination::STRING_RETURN);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  list<string>  $exportKeys
     * @return array{title: string, generated_at: string, filters: string, row_count: int, headers: array}
     */
    private function buildProductSalesExportMeta(Request $request, int $rowCount, array $exportKeys): array
    {
        return [
            'title' => __('menuItemLang.product-sales-report'),
            'generated_at' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            'filters' => $this->productSellReportFilterSummary($request),
            'row_count' => $rowCount,
            'headers' => ReportTransactionsUtile::getProductSalesExportHeaderLabels($exportKeys),
        ];
    }

    /**
     * @return list<string>
     */
    private function resolveProductSalesExportColumnKeys(Request $request): array
    {
        $all = ReportTransactionsUtile::getProductSalesExportColumnKeys();
        $raw = $request->input('export_columns', []);
        $requested = collect(\Illuminate\Support\Arr::wrap($raw))
            ->flatten()
            ->filter(fn ($k) => is_string($k) && $k !== '')
            ->values()
            ->all();
        if ($requested === []) {
            return $all;
        }
        $picked = array_values(array_intersect($all, $requested));

        return $picked === [] ? $all : $picked;
    }

    private function productSellReportFilterSummary(Request $request): string
    {
        $lines = [];
        $mode = $request->input('report_mode', 'detail');
        $lines[] = __('report::general.product_sales_report_mode').': '.($mode === 'summary'
            ? __('report::general.product_sales_report_mode_summary')
            : __('report::general.product_sales_report_mode_detail'));
        $dateRange = trim((string) $request->input('sale_date_range', ''));
        if ($dateRange !== '') {
            $lines[] = __('report::purchase.Sale Date Range').': '.$dateRange;
        }
        $lines = array_merge($lines, $this->sellLineReportFilterSummaryLines($request));

        return implode("\n", $lines);
    }

    /**
     * Shared filter narrative (branches, customers, products, categories, …) for sell-line style reports.
     *
     * @return list<string>
     */
    private function sellLineReportFilterSummaryLines(Request $request): array
    {
        $locale = app()->getLocale();
        $sep = $locale === 'ar' ? '، ' : ', ';
        $lines = [];

        $branchIds = collect($request->input('branch_id'))->filter()->values();
        if ($branchIds->isNotEmpty()) {
            $names = Establishment::whereIn('id', $branchIds)->get()->map(function ($e) use ($locale) {
                return $locale === 'ar' ? ($e->name ?? '') : ($e->name_en ?? $e->name ?? '');
            })->filter()->unique()->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::general.filter_panel_branch').': '.$names;
            }
        }

        $customerIds = collect($request->input('customer_id'))->filter()->values();
        if ($customerIds->isNotEmpty()) {
            $names = Contact::whereIn('id', $customerIds)->pluck('name')->filter()->unique()->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::general.filter_panel_customer').': '.$names;
            }
        }

        $productIds = collect($request->input('product_id'))->filter()->values();
        if ($productIds->isNotEmpty()) {
            $names = Product::whereIn('id', $productIds)->get()->map(function ($p) use ($locale) {
                return $locale === 'ar' ? ($p->name_ar ?: $p->name_en) : ($p->name_en ?: $p->name_ar);
            })->filter()->unique()->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::general.filter_panel_product').': '.$names;
            }
        }

        $categoryIds = collect($request->input('category_id'))->filter()->values();
        if ($categoryIds->isNotEmpty()) {
            $names = Category::restrictByFranchise()->whereIn('id', $categoryIds)->get()->map(function ($c) use ($locale) {
                return $locale === 'ar' ? $c->name_ar : $c->name_en;
            })->filter()->unique()->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::general.filter_panel_category').': '.$names;
            }
        }

        $subcategoryIds = collect($request->input('subcategory_id'))->filter()->values();
        if ($subcategoryIds->isNotEmpty()) {
            $allowedCategoryIds = Category::restrictByFranchise()->pluck('id');
            $names = Subcategory::whereIn('id', $subcategoryIds)
                ->whereIn('category_id', $allowedCategoryIds)
                ->get()
                ->map(fn ($s) => $locale === 'ar' ? $s->name_ar : $s->name_en)
                ->filter()
                ->unique()
                ->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::general.filter_panel_subcategory').': '.$names;
            }
        }

        $unitIds = collect($request->input('unit_id'))->filter()->values();
        if ($unitIds->isNotEmpty()) {
            $names = DB::table('product_unit_transfer')
                ->whereIn('id', $unitIds)
                ->pluck('unit1')
                ->map(fn ($u) => (string) ($u ?? ''))
                ->filter()
                ->unique()
                ->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::general.filter_panel_unit').': '.$names;
            }
        }

        $payMethods = collect($request->input('payment_method'))->filter()->values();
        if ($payMethods->isNotEmpty()) {
            $labels = $payMethods->map(fn ($m) => $this->salesComparisonPaymentMethodLabel((string) $m))->unique()->implode($sep);
            if ($labels !== '') {
                $lines[] = __('report::general.filter_panel_payment').': '.$labels;
            }
        }

        return $lines;
    }

    private function applyProductSellReportFilters($query, Request $request): void
    {
        if ($request->has('branch_id')) {
            $branchIds = collect($request->input('branch_id'))->filter()->values()->toArray();
            if (! empty($branchIds)) {
                $query->whereIn('t.establishment_id', $branchIds);
            }
        }

        if ($request->has('customer_id')) {
            $customerIds = collect($request->input('customer_id'))->filter()->values()->toArray();
            if (! empty($customerIds)) {
                $query->whereIn('t.contact_id', $customerIds);
            }
        }

        if ($request->has('product_id')) {
            $productIds = collect($request->input('product_id'))->filter()->values()->toArray();
            if (! empty($productIds)) {
                $query->whereIn('p.id', $productIds);
            }
        }

        if ($request->has('category_id')) {
            $categoryIds = collect($request->input('category_id'))->filter()->values()->toArray();
            if (! empty($categoryIds)) {
                $query->whereIn('p.category_id', $categoryIds);
            }
        }

        if ($request->has('subcategory_id')) {
            $subcategoryIds = collect($request->input('subcategory_id'))->filter()->values()->toArray();
            if (! empty($subcategoryIds)) {
                $query->whereIn('p.subcategory_id', $subcategoryIds);
            }
        }

        if ($request->has('unit_id')) {
            $unitIds = collect($request->input('unit_id'))->filter()->values()->toArray();
            if (! empty($unitIds)) {
                $query->whereIn('transaction_sell_lines.unit_id', $unitIds);
            }
        }

        if ($request->has('payment_method')) {
            $methods = collect($request->input('payment_method'))->filter()->values()->toArray();
            if (! empty($methods)) {
                $query->whereExists(function ($q) use ($methods) {
                    $q->select(DB::raw(1))
                        ->from('transaction_payments as tpf')
                        ->whereColumn('tpf.transaction_id', 't.id')
                        ->whereIn('tpf.method', $methods);
                });
            }
        }

        if (! empty($request->input('sale_date_range'))) {
            $dateRange = explode(' - ', $request->input('sale_date_range'));
            if (count($dateRange) === 2) {
                $from = date('Y-m-d', strtotime($dateRange[0]));
                $to = date('Y-m-d', strtotime($dateRange[1]));
                $query->whereBetween('t.transaction_date', [$from, $to]);
            }
        }
    }

    private function buildProductSellReportDetailQuery(Request $request)
    {
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $catName = $locale === 'ar' ? 'cat.name_ar' : 'cat.name_en';
        $subName = $locale === 'ar' ? 'sub.name_ar' : 'sub.name_en';

        $query = TransactionSellLine::query()
            ->join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('cs_contacts as c', 't.contact_id', '=', 'c.id')
            ->join('product_products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            ->leftJoin('est_establishments as e', 't.establishment_id', '=', 'e.id')
            ->leftJoin('product_unit_transfer as u', 'transaction_sell_lines.unit_id', '=', 'u.id')
            ->leftJoin('product_categories as cat', 'p.category_id', '=', 'cat.id')
            ->leftJoin('product_subcategories as sub', 'p.subcategory_id', '=', 'sub.id')
            ->where('t.type', 'sell')
            ->where('t.status', 'approved')
            ->select([
                'transaction_sell_lines.id as sell_line_id',
                'p.name_ar as product_name_ar',
                'p.name_en as product_name_en',
                'p.price as product_price',
                'p.SKU as product_SKU',
                'c.name as customer',
                't.id as transaction_id',
                't.ref_no',
                't.transaction_date as transaction_date',
                'transaction_sell_lines.unit_price_before_discount as unit_price',
                'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                DB::raw('transaction_sell_lines.qyt as sell_qty'),
                'transaction_sell_lines.discount_amount as discount_amount',
                'transaction_sell_lines.tax_value as tax_value',
                DB::raw("{$catName} as category"),
                DB::raw("{$subName} as subcategory"),
                DB::raw("CASE WHEN '{$locale}' = 'ar' THEN e.name ELSE e.name_en END as establishment_name"),
                'u.unit1 as line_unit',
                DB::raw('(transaction_sell_lines.qyt * transaction_sell_lines.unit_price_inc_tax) as subtotal'),
                DB::raw('(SELECT GROUP_CONCAT(DISTINCT tpx.method ORDER BY tpx.method SEPARATOR ",") FROM transaction_payments tpx WHERE tpx.transaction_id = t.id) as invoice_payment_methods'),
            ]);

        $this->applyProductSellReportFilters($query, $request);
        $query->orderBy('transaction_sell_lines.id', 'desc');

        return $query;
    }

    private function buildProductSellReportSummaryQuery(Request $request)
    {
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $catName = $locale === 'ar' ? 'cat.name_ar' : 'cat.name_en';
        $subName = $locale === 'ar' ? 'sub.name_ar' : 'sub.name_en';
        $estCol = $locale === 'ar' ? 'e.name' : 'e.name_en';

        $query = TransactionSellLine::query()
            ->join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('cs_contacts as c', 't.contact_id', '=', 'c.id')
            ->join('product_products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            ->leftJoin('est_establishments as e', 't.establishment_id', '=', 'e.id')
            ->leftJoin('product_unit_transfer as u', 'transaction_sell_lines.unit_id', '=', 'u.id')
            ->leftJoin('product_categories as cat', 'p.category_id', '=', 'cat.id')
            ->leftJoin('product_subcategories as sub', 'p.subcategory_id', '=', 'sub.id')
            ->where('t.type', 'sell')
            ->where('t.status', 'approved')
            ->select([
                DB::raw('MIN(transaction_sell_lines.id) as sell_line_id'),
                'p.name_ar as product_name_ar',
                'p.name_en as product_name_en',
                'p.price as product_price',
                'p.SKU as product_SKU',
                DB::raw('NULL as customer'),
                DB::raw('MIN(t.id) as transaction_id'),
                DB::raw('MIN(t.ref_no) as ref_no'),
                DB::raw('MIN(t.transaction_date) as transaction_date'),
                DB::raw('SUM(transaction_sell_lines.qyt * transaction_sell_lines.unit_price_before_discount) / NULLIF(SUM(transaction_sell_lines.qyt), 0) as unit_price'),
                DB::raw('SUM(transaction_sell_lines.qyt * transaction_sell_lines.unit_price_inc_tax) / NULLIF(SUM(transaction_sell_lines.qyt), 0) as unit_sale_price'),
                DB::raw('SUM(transaction_sell_lines.qyt) as sell_qty'),
                DB::raw('SUM(transaction_sell_lines.discount_amount) as discount_amount'),
                DB::raw('SUM(transaction_sell_lines.tax_value) as tax_value'),
                DB::raw("MAX({$catName}) as category"),
                DB::raw("MAX({$subName}) as subcategory"),
                DB::raw("MAX(CASE WHEN '{$locale}' = 'ar' THEN e.name ELSE e.name_en END) as establishment_name"),
                DB::raw('GROUP_CONCAT(DISTINCT NULLIF(u.unit1, \'\') ORDER BY u.unit1 SEPARATOR \', \') as line_unit'),
                DB::raw('SUM(transaction_sell_lines.qyt * transaction_sell_lines.unit_price_inc_tax) as subtotal'),
                DB::raw('NULL as invoice_payment_methods'),
            ])
            ->groupBy('p.id', 'p.name_ar', 'p.name_en', 'p.price', 'p.SKU', 't.establishment_id');

        $this->applyProductSellReportFilters($query, $request);
        $query->orderByRaw("MAX({$estCol}) ASC")
            ->orderByRaw('SUM(transaction_sell_lines.qyt) DESC');

        return $query;
    }

    public function salesComparisonReport(Request $request)
    {
        $transactionUtile = new ReportTransactionsUtile;

        if ($request->ajax()) {
            $merged = $this->buildSalesComparisonMergedCollection($request);
            $footer = $this->computeSalesComparisonFooterTotals($merged ?? collect());

            if ($merged === null) {
                return ReportTransactionsUtile::getSalesComparisonReport(collect(), $footer);
            }

            return ReportTransactionsUtile::getSalesComparisonReport($merged, $footer);
        }

        return view('report::sales.sales_comparison_report');
    }

    public function salesComparisonExportExcel(Request $request)
    {
        $merged = $this->buildSalesComparisonMergedCollection($request);
        if ($merged === null) {
            return response()->json(['message' => __('report::general.export_invalid_periods')], 422);
        }

        $meta = $this->salesComparisonExportMeta($request, $merged);
        $filename = 'sales-comparison-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new SalesComparisonExcelExport($merged, $meta), $filename);
    }

    public function salesComparisonExportPdf(Request $request)
    {
        $merged = $this->buildSalesComparisonMergedCollection($request);
        if ($merged === null) {
            return response()->json(['message' => __('report::general.export_invalid_periods')], 422);
        }

        $meta = $this->salesComparisonExportMeta($request, $merged);
        $rows = $merged->map(fn ($r) => $this->mapSalesComparisonRowForPdf($r))->all();

        $html = view('report::sales.sales_comparison_export_pdf', [
            'meta' => $meta,
            'rows' => $rows,
        ])->render();

        if (! is_dir(storage_path('temp/mpdf'))) {
            @mkdir(storage_path('temp/mpdf'), 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 6,
            'margin_right' => 6,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => storage_path('temp/mpdf'),
        ]);
        if (app()->getLocale() === 'ar') {
            $mpdf->SetDirectionality('rtl');
        }
        $mpdf->WriteHTML($html);
        $filename = 'sales-comparison-'.now()->format('Y-m-d-His').'.pdf';
        $binary = $mpdf->Output('', Destination::STRING_RETURN);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function salesComparisonChartData(Request $request)
    {
        $periodA = SalesComparisonPeriodResolver::resolve(
            $request->input('period_a_preset'),
            $request->input('period_a_range')
        );
        $periodB = SalesComparisonPeriodResolver::resolve(
            $request->input('period_b_preset'),
            $request->input('period_b_range')
        );

        if (! $periodA || ! $periodB) {
            return response()->json([
                'success' => false,
                'message' => 'invalid_periods',
            ], 422);
        }

        [$aFrom, $aTo] = $periodA;
        [$bFrom, $bTo] = $periodB;

        $totA = $this->salesComparisonPeriodTotals($request, $aFrom, $aTo);
        $totB = $this->salesComparisonPeriodTotals($request, $bFrom, $bTo);
        $topProducts = $this->salesComparisonTopProducts($request, $aFrom, $aTo, $bFrom, $bTo, 10);

        return response()->json([
            'success' => true,
            'period_a' => ['from' => $aFrom, 'to' => $aTo],
            'period_b' => ['from' => $bFrom, 'to' => $bTo],
            'totals' => [
                'a' => [
                    'qty' => (float) $totA->qty,
                    'discount' => (float) $totA->discount,
                    'tax' => (float) $totA->tax,
                    'subtotal' => (float) $totA->subtotal,
                    'line_count' => (int) $totA->line_count,
                ],
                'b' => [
                    'qty' => (float) $totB->qty,
                    'discount' => (float) $totB->discount,
                    'tax' => (float) $totB->tax,
                    'subtotal' => (float) $totB->subtotal,
                    'line_count' => (int) $totB->line_count,
                ],
            ],
            'top_products' => $topProducts,
        ]);
    }

    /**
     * Base query for sales comparison (sell lines + same filters). Optionally include joins needed for grouped dimensions.
     */
    private function buildSalesComparisonLinesQuery(Request $request, bool $withDisplayJoins = false): \Illuminate\Database\Query\Builder
    {
        $q = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('product_products as p', 'tsl.product_id', '=', 'p.id')
            ->join('cs_contacts as c', 't.contact_id', '=', 'c.id');

        if ($withDisplayJoins) {
            $q->leftJoin('product_categories as cat', 'p.category_id', '=', 'cat.id')
                ->leftJoin('product_subcategories as sub', 'p.subcategory_id', '=', 'sub.id')
                ->leftJoin('est_establishments as e', 't.establishment_id', '=', 'e.id');
        }

        $q->where('t.type', 'sell')
            ->where('t.status', 'approved');

        if ($request->has('branch_id')) {
            $branchIds = collect($request->input('branch_id'))->filter()->values()->toArray();
            if (! empty($branchIds)) {
                $q->whereIn('t.establishment_id', $branchIds);
            }
        }

        if ($request->has('customer_id')) {
            $customerIds = collect($request->input('customer_id'))->filter()->values()->toArray();
            if (! empty($customerIds)) {
                $q->whereIn('t.contact_id', $customerIds);
            }
        }

        if ($request->has('product_id')) {
            $productIds = collect($request->input('product_id'))->filter()->values()->toArray();
            if (! empty($productIds)) {
                $q->whereIn('p.id', $productIds);
            }
        }

        if ($request->has('category_id')) {
            $categoryIds = collect($request->input('category_id'))->filter()->values()->toArray();
            if (! empty($categoryIds)) {
                $q->whereIn('p.category_id', $categoryIds);
            }
        }

        if ($request->has('subcategory_id')) {
            $subcategoryIds = collect($request->input('subcategory_id'))->filter()->values()->toArray();
            if (! empty($subcategoryIds)) {
                $q->whereIn('p.subcategory_id', $subcategoryIds);
            }
        }

        if ($request->has('unit_id')) {
            $unitIds = collect($request->input('unit_id'))->filter()->values()->toArray();
            if (! empty($unitIds)) {
                $q->whereIn('tsl.unit_id', $unitIds);
            }
        }

        if ($request->has('payment_method')) {
            $paymentMethods = collect($request->input('payment_method'))->filter()->values()->toArray();
            if (! empty($paymentMethods)) {
                $q->whereExists(function ($sub) use ($paymentMethods) {
                    $sub->from('transaction_payments as tp')
                        ->whereColumn('tp.transaction_id', 't.id')
                        ->whereIn('tp.method', $paymentMethods);
                });
            }
        }

        return $q;
    }

    public function getComparisonCategories(Request $request)
    {
        try {
            $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
            $rows = Category::restrictByFranchise()
                ->select('id', 'name_ar', 'name_en')
                ->orderBy('name_'.$locale)
                ->get()
                ->map(function ($c) use ($locale) {
                    return [
                        'id' => $c->id,
                        'name' => $locale === 'ar' ? $c->name_ar : $c->name_en,
                    ];
                });

            return response()->json(['success' => true, 'data' => $rows], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getComparisonSubcategories(Request $request)
    {
        try {
            $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
            $allowedCategoryIds = Category::restrictByFranchise()->pluck('id');
            if ($allowedCategoryIds->isEmpty()) {
                return response()->json(['success' => true, 'data' => []], 200);
            }

            $q = Subcategory::query()
                ->whereIn('category_id', $allowedCategoryIds)
                ->whereHas('productsForSale')
                ->select('id', 'name_ar', 'name_en', 'category_id');

            $filterCategoryIds = collect($request->input('category_id'))->filter()->values()->toArray();
            if (! empty($filterCategoryIds)) {
                $q->whereIn('category_id', $filterCategoryIds);
            }

            $rows = $q->orderBy('name_'.$locale)
                ->get()
                ->map(function ($s) use ($locale) {
                    return [
                        'id' => $s->id,
                        'name' => $locale === 'ar' ? $s->name_ar : $s->name_en,
                        'category_id' => $s->category_id,
                    ];
                });

            return response()->json(['success' => true, 'data' => $rows], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getComparisonUnits(Request $request)
    {
        try {
            $rows = DB::table('product_unit_transfer as u')
                ->join('transaction_sell_lines as tsl', 'tsl.unit_id', '=', 'u.id')
                ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
                ->where('t.type', 'sell')
                ->where('t.status', 'approved')
                ->whereNull('u.deleted_at')
                ->select('u.id', 'u.unit1')
                ->distinct()
                ->orderBy('u.unit1')
                ->get()
                ->map(function ($r) {
                    $label = $r->unit1 ?? '';
                    if ($label === '' || $label === null) {
                        $label = '#'.$r->id;
                    }

                    return [
                        'id' => $r->id,
                        'name' => $label,
                    ];
                });

            return response()->json(['success' => true, 'data' => $rows], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getComparisonPaymentMethods(Request $request)
    {
        try {
            $methods = DB::table('transaction_payments as tp')
                ->join('transactions as t', 'tp.transaction_id', '=', 't.id')
                ->where('t.type', 'sell')
                ->where('t.status', 'approved')
                ->whereNotNull('tp.method')
                ->where('tp.method', '!=', '')
                ->distinct()
                ->orderBy('tp.method')
                ->pluck('tp.method');

            $data = $methods->map(function ($m) {
                $method = (string) $m;

                return [
                    'id' => $method,
                    'name' => $this->salesComparisonPaymentMethodLabel($method),
                ];
            });

            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getPurchaseReportSubcategories(Request $request)
    {
        try {
            $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
            $allowedCategoryIds = Category::restrictByFranchise()->pluck('id');
            if ($allowedCategoryIds->isEmpty()) {
                return response()->json(['success' => true, 'data' => []], 200);
            }

            $q = Subcategory::query()
                ->whereIn('category_id', $allowedCategoryIds)
                ->select('id', 'name_ar', 'name_en', 'category_id');

            $filterCategoryIds = collect($request->input('category_id'))->filter()->values()->toArray();
            if (! empty($filterCategoryIds)) {
                $q->whereIn('category_id', $filterCategoryIds);
            }

            $rows = $q->orderBy('name_'.$locale)
                ->get()
                ->map(function ($s) use ($locale) {
                    return [
                        'id' => $s->id,
                        'name' => $locale === 'ar' ? $s->name_ar : $s->name_en,
                        'category_id' => $s->category_id,
                    ];
                });

            return response()->json(['success' => true, 'data' => $rows], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getPurchaseReportUnits(Request $request)
    {
        try {
            $rows = DB::table('product_unit_transfer as u')
                ->join('transactione_purchases_lines as tpl', 'tpl.unit_id', '=', 'u.id')
                ->join('transactions as t', 'tpl.transaction_id', '=', 't.id')
                ->where('t.type', 'purchases')
                ->where('t.status', 'approved')
                ->whereNull('u.deleted_at')
                ->select('u.id', 'u.unit1')
                ->distinct()
                ->orderBy('u.unit1')
                ->get()
                ->map(function ($r) {
                    $label = $r->unit1 ?? '';
                    if ($label === '' || $label === null) {
                        $label = '#'.$r->id;
                    }

                    return [
                        'id' => $r->id,
                        'name' => $label,
                    ];
                });

            return response()->json(['success' => true, 'data' => $rows], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getPurchaseReportPaymentMethods(Request $request)
    {
        try {
            $methods = DB::table('transaction_payments as tp')
                ->join('transactions as t', 'tp.transaction_id', '=', 't.id')
                ->where('t.type', 'purchases')
                ->where('t.status', 'approved')
                ->whereNotNull('tp.method')
                ->where('tp.method', '!=', '')
                ->distinct()
                ->orderBy('tp.method')
                ->pluck('tp.method');

            $data = $methods->map(function ($m) {
                $method = (string) $m;

                return [
                    'id' => $method,
                    'name' => $this->salesComparisonPaymentMethodLabel($method),
                ];
            });

            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function salesComparisonPeriodTotals(Request $request, string $from, string $to): object
    {
        return $this->buildSalesComparisonLinesQuery($request, false)
            ->whereBetween('t.transaction_date', [$from, $to])
            ->selectRaw('COALESCE(SUM(tsl.qyt), 0) as qty')
            ->selectRaw('COALESCE(SUM(tsl.discount_amount), 0) as discount')
            ->selectRaw('COALESCE(SUM(tsl.tax_value), 0) as tax')
            ->selectRaw('COALESCE(SUM(tsl.qyt * tsl.unit_price_inc_tax), 0) as subtotal')
            ->selectRaw('COUNT(*) as line_count')
            ->first();
    }

    /**
     * @return list<array{name: string, subtotal_a: float, subtotal_b: float}>
     */
    private function salesComparisonTopProducts(Request $request, string $aFrom, string $aTo, string $bFrom, string $bTo, int $limit = 10): array
    {
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';

        $fetchByProduct = function (string $from, string $to) use ($request) {
            return $this->buildSalesComparisonLinesQuery($request, false)
                ->whereBetween('t.transaction_date', [$from, $to])
                ->groupBy('tsl.product_id')
                ->selectRaw('tsl.product_id as product_id')
                ->selectRaw('MAX(p.name_ar) as name_ar')
                ->selectRaw('MAX(p.name_en) as name_en')
                ->selectRaw('COALESCE(SUM(tsl.qyt * tsl.unit_price_inc_tax), 0) as subtotal')
                ->get()
                ->keyBy('product_id');
        };

        $rowsA = $fetchByProduct($aFrom, $aTo);
        $rowsB = $fetchByProduct($bFrom, $bTo);
        $allIds = $rowsA->keys()->merge($rowsB->keys())->unique();

        $out = [];
        foreach ($allIds as $id) {
            $a = $rowsA->get($id);
            $b = $rowsB->get($id);
            $sa = (float) (optional($a)->subtotal ?? 0);
            $sb = (float) (optional($b)->subtotal ?? 0);
            $nameAr = optional($a)->name_ar ?? optional($b)->name_ar ?? '';
            $nameEn = optional($a)->name_en ?? optional($b)->name_en ?? '';
            $name = $locale === 'ar' ? ($nameAr ?: $nameEn) : ($nameEn ?: $nameAr);
            $out[] = [
                'name' => $name !== '' ? $name : ('#'.$id),
                'subtotal_a' => $sa,
                'subtotal_b' => $sb,
                '_score' => $sa + $sb,
            ];
        }

        usort($out, fn ($x, $y) => $y['_score'] <=> $x['_score']);
        $out = array_slice($out, 0, $limit);

        return array_map(function ($row) {
            unset($row['_score']);

            return $row;
        }, $out);
    }

    /**
     * Aggregated sell lines per product, branch, and optionally customer for one date range.
     *
     * @param  list<int>|null  $mysqlWeekdayOneToSeven  Optional MySQL DAYOFWEEK() values (1=Sunday … 7=Saturday). When set, only lines on those weekdays are included.
     * @param  bool  $groupByCustomer  When false, quantities and revenue are summed across all customers for each product–branch (weekday report).
     */
    private function aggregateSalesComparisonPeriod(
        Request $request,
        string $from,
        string $to,
        ?array $mysqlWeekdayOneToSeven = null,
        bool $groupByCustomer = true
    ): \Illuminate\Support\Collection {
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';

        $q = $this->buildSalesComparisonLinesQuery($request, true)
            ->whereDate('t.transaction_date', '>=', $from)
            ->whereDate('t.transaction_date', '<=', $to);

        if ($mysqlWeekdayOneToSeven !== null && $mysqlWeekdayOneToSeven !== []) {
            $dows = array_values(array_unique(array_map('intval', $mysqlWeekdayOneToSeven)));
            sort($dows);
            $inList = implode(',', $dows);
            $q->whereRaw('DAYOFWEEK(DATE(t.transaction_date)) IN ('.$inList.')');
        }

        $q = $q
            ->selectRaw('tsl.product_id as product_id')
            ->selectRaw('t.establishment_id as establishment_id')
            ->selectRaw('MAX(p.name_ar) as product_name_ar')
            ->selectRaw('MAX(p.name_en) as product_name_en')
            ->selectRaw('MAX(p.SKU) as sku')
            ->selectRaw("MAX(CASE WHEN '{$locale}' = 'ar' THEN cat.name_ar ELSE cat.name_en END) as category")
            ->selectRaw("MAX(CASE WHEN '{$locale}' = 'ar' THEN sub.name_ar ELSE sub.name_en END) as subcategory")
            ->selectRaw("MAX(CASE WHEN '{$locale}' = 'ar' THEN e.name ELSE e.name_en END) as establishment_name")
            ->selectRaw('SUM(tsl.qyt) as qty')
            ->selectRaw('SUM(tsl.discount_amount) as discount')
            ->selectRaw('SUM(tsl.tax_value) as tax')
            ->selectRaw('SUM(tsl.qyt * tsl.unit_price_inc_tax) as subtotal')
            ->selectRaw('COUNT(*) as line_count');

        if ($groupByCustomer) {
            $rows = $q->groupBy('tsl.product_id', 't.establishment_id', 't.contact_id')
                ->selectRaw('t.contact_id as contact_id')
                ->selectRaw('MAX(c.name) as customer')
                ->get();

            return $rows->keyBy(fn ($r) => $r->product_id.'|'.$r->establishment_id.'|'.$r->contact_id);
        }

        $rows = $q->groupBy('tsl.product_id', 't.establishment_id')->get();

        return $rows->keyBy(fn ($r) => $r->product_id.'|'.$r->establishment_id);
    }

    private function applyProductPurchaseReportFilters($query, Request $request): void
    {
        if ($request->has('branch_id')) {
            $branchIds = collect($request->input('branch_id'))->filter()->values()->toArray();
            if (! empty($branchIds)) {
                $query->whereIn('t.establishment_id', $branchIds);
            }
        }

        if ($request->has('supplier_id')) {
            $supplierIds = collect($request->input('supplier_id'))->filter()->values()->toArray();
            if (! empty($supplierIds)) {
                $query->whereIn('t.contact_id', $supplierIds);
            }
        }

        if ($request->has('product_id')) {
            $productIds = collect($request->input('product_id'))->filter()->values()->toArray();
            if (! empty($productIds)) {
                $query->whereIn('p.id', $productIds);
            }
        }

        if ($request->has('category_id')) {
            $categoryIds = collect($request->input('category_id'))->filter()->values()->toArray();
            if (! empty($categoryIds)) {
                $query->whereIn('p.category_id', $categoryIds);
            }
        }

        if ($request->has('subcategory_id')) {
            $subcategoryIds = collect($request->input('subcategory_id'))->filter()->values()->toArray();
            if (! empty($subcategoryIds)) {
                $query->whereIn('p.subcategory_id', $subcategoryIds);
            }
        }

        if ($request->has('unit_id')) {
            $unitIds = collect($request->input('unit_id'))->filter()->values()->toArray();
            if (! empty($unitIds)) {
                $query->whereIn('transactione_purchases_lines.unit_id', $unitIds);
            }
        }

        if ($request->has('payment_method')) {
            $methods = collect($request->input('payment_method'))->filter()->values()->toArray();
            if (! empty($methods)) {
                $query->whereExists(function ($q) use ($methods) {
                    $q->select(DB::raw(1))
                        ->from('transaction_payments as tpf')
                        ->whereColumn('tpf.transaction_id', 't.id')
                        ->whereIn('tpf.method', $methods);
                });
            }
        }

        if (! empty($request->input('sale_date_range'))) {
            $dateRange = explode(' - ', $request->input('sale_date_range'));
            if (count($dateRange) === 2) {
                $from = date('Y-m-d', strtotime($dateRange[0]));
                $to = date('Y-m-d', strtotime($dateRange[1]));
                $query->whereBetween('t.transaction_date', [$from, $to]);
            }
        }
    }

    private function buildProductPurchaseReportDetailQuery(Request $request)
    {
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $catName = $locale === 'ar' ? 'cat.name_ar' : 'cat.name_en';
        $subName = $locale === 'ar' ? 'sub.name_ar' : 'sub.name_en';

        $query = TransactionePurchasesLine::query()
            ->join('transactions as t', 'transactione_purchases_lines.transaction_id', '=', 't.id')
            ->join('cs_contacts as c', 't.contact_id', '=', 'c.id')
            ->join('product_products as p', 'transactione_purchases_lines.product_id', '=', 'p.id')
            ->leftJoin('est_establishments as e', 't.establishment_id', '=', 'e.id')
            ->leftJoin('product_unit_transfer as u', 'transactione_purchases_lines.unit_id', '=', 'u.id')
            ->leftJoin('product_categories as cat', 'p.category_id', '=', 'cat.id')
            ->leftJoin('product_subcategories as sub', 'p.subcategory_id', '=', 'sub.id')
            ->where('t.type', 'purchases')
            ->where('t.status', 'approved')
            ->select([
                'transactione_purchases_lines.id as purchase_line_id',
                'p.name_ar as product_name_ar',
                'p.name_en as product_name_en',
                'p.price as product_price',
                'p.SKU as product_SKU',
                'c.name as supplier',
                't.id as transaction_id',
                't.ref_no',
                't.transaction_date as transaction_date',
                'transactione_purchases_lines.unit_price_before_discount as unit_price',
                'transactione_purchases_lines.unit_price_inc_tax as unit_sale_price',
                DB::raw('transactione_purchases_lines.qyt as purchased_quantity'),
                'transactione_purchases_lines.discount_amount as discount_amount',
                'transactione_purchases_lines.tax_value as tax_value',
                DB::raw("{$catName} as category"),
                DB::raw("{$subName} as subcategory"),
                DB::raw("CASE WHEN '{$locale}' = 'ar' THEN e.name ELSE e.name_en END as establishment_name"),
                'u.unit1 as line_unit',
                DB::raw('(transactione_purchases_lines.qyt * transactione_purchases_lines.unit_price_inc_tax) as subtotal'),
                DB::raw('(SELECT GROUP_CONCAT(DISTINCT tpx.method ORDER BY tpx.method SEPARATOR ",") FROM transaction_payments tpx WHERE tpx.transaction_id = t.id) as invoice_payment_methods'),
            ]);

        $this->applyProductPurchaseReportFilters($query, $request);
        $query->orderBy('transactione_purchases_lines.id', 'desc');

        return $query;
    }

    private function buildProductPurchaseReportSummaryQuery(Request $request)
    {
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $catName = $locale === 'ar' ? 'cat.name_ar' : 'cat.name_en';
        $subName = $locale === 'ar' ? 'sub.name_ar' : 'sub.name_en';
        $estCol = $locale === 'ar' ? 'e.name' : 'e.name_en';

        $query = TransactionePurchasesLine::query()
            ->join('transactions as t', 'transactione_purchases_lines.transaction_id', '=', 't.id')
            ->join('cs_contacts as c', 't.contact_id', '=', 'c.id')
            ->join('product_products as p', 'transactione_purchases_lines.product_id', '=', 'p.id')
            ->leftJoin('est_establishments as e', 't.establishment_id', '=', 'e.id')
            ->leftJoin('product_unit_transfer as u', 'transactione_purchases_lines.unit_id', '=', 'u.id')
            ->leftJoin('product_categories as cat', 'p.category_id', '=', 'cat.id')
            ->leftJoin('product_subcategories as sub', 'p.subcategory_id', '=', 'sub.id')
            ->where('t.type', 'purchases')
            ->where('t.status', 'approved')
            ->select([
                DB::raw('MIN(transactione_purchases_lines.id) as purchase_line_id'),
                'p.name_ar as product_name_ar',
                'p.name_en as product_name_en',
                'p.price as product_price',
                'p.SKU as product_SKU',
                DB::raw('NULL as supplier'),
                DB::raw('MIN(t.id) as transaction_id'),
                DB::raw('MIN(t.ref_no) as ref_no'),
                DB::raw('MIN(t.transaction_date) as transaction_date'),
                DB::raw('SUM(transactione_purchases_lines.qyt * transactione_purchases_lines.unit_price_before_discount) / NULLIF(SUM(transactione_purchases_lines.qyt), 0) as unit_price'),
                DB::raw('SUM(transactione_purchases_lines.qyt * transactione_purchases_lines.unit_price_inc_tax) / NULLIF(SUM(transactione_purchases_lines.qyt), 0) as unit_sale_price'),
                DB::raw('SUM(transactione_purchases_lines.qyt) as purchased_quantity'),
                DB::raw('SUM(transactione_purchases_lines.discount_amount) as discount_amount'),
                DB::raw('SUM(transactione_purchases_lines.tax_value) as tax_value'),
                DB::raw("MAX({$catName}) as category"),
                DB::raw("MAX({$subName}) as subcategory"),
                DB::raw("MAX(CASE WHEN '{$locale}' = 'ar' THEN e.name ELSE e.name_en END) as establishment_name"),
                DB::raw('GROUP_CONCAT(DISTINCT NULLIF(u.unit1, \'\') ORDER BY u.unit1 SEPARATOR \', \') as line_unit'),
                DB::raw('SUM(transactione_purchases_lines.qyt * transactione_purchases_lines.unit_price_inc_tax) as subtotal'),
                DB::raw('NULL as invoice_payment_methods'),
            ])
            ->groupBy('p.id', 'p.name_ar', 'p.name_en', 'p.price', 'p.SKU', 't.establishment_id');

        $this->applyProductPurchaseReportFilters($query, $request);
        $query->orderByRaw("MAX({$estCol}) ASC")
            ->orderByRaw('SUM(transactione_purchases_lines.qyt) DESC');

        return $query;
    }

    /**
     * @return list<string>
     */
    private function purchaseLineReportFilterSummaryLines(Request $request): array
    {
        $locale = app()->getLocale();
        $sep = $locale === 'ar' ? '، ' : ', ';
        $lines = [];

        $branchIds = collect($request->input('branch_id'))->filter()->values();
        if ($branchIds->isNotEmpty()) {
            $names = Establishment::whereIn('id', $branchIds)->get()->map(function ($e) use ($locale) {
                return $locale === 'ar' ? ($e->name ?? '') : ($e->name_en ?? $e->name ?? '');
            })->filter()->unique()->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::general.filter_panel_branch').': '.$names;
            }
        }

        $supplierIds = collect($request->input('supplier_id'))->filter()->values();
        if ($supplierIds->isNotEmpty()) {
            $names = Contact::whereIn('id', $supplierIds)->pluck('name')->filter()->unique()->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::purchase.Supplier').': '.$names;
            }
        }

        $productIds = collect($request->input('product_id'))->filter()->values();
        if ($productIds->isNotEmpty()) {
            $names = Product::whereIn('id', $productIds)->get()->map(function ($p) use ($locale) {
                return $locale === 'ar' ? ($p->name_ar ?: $p->name_en) : ($p->name_en ?: $p->name_ar);
            })->filter()->unique()->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::general.filter_panel_product').': '.$names;
            }
        }

        $categoryIds = collect($request->input('category_id'))->filter()->values();
        if ($categoryIds->isNotEmpty()) {
            $names = Category::restrictByFranchise()->whereIn('id', $categoryIds)->get()->map(function ($c) use ($locale) {
                return $locale === 'ar' ? $c->name_ar : $c->name_en;
            })->filter()->unique()->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::general.filter_panel_category').': '.$names;
            }
        }

        $subcategoryIds = collect($request->input('subcategory_id'))->filter()->values();
        if ($subcategoryIds->isNotEmpty()) {
            $allowedCategoryIds = Category::restrictByFranchise()->pluck('id');
            $names = Subcategory::whereIn('id', $subcategoryIds)
                ->whereIn('category_id', $allowedCategoryIds)
                ->get()
                ->map(fn ($s) => $locale === 'ar' ? $s->name_ar : $s->name_en)
                ->filter()
                ->unique()
                ->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::general.filter_panel_subcategory').': '.$names;
            }
        }

        $unitIds = collect($request->input('unit_id'))->filter()->values();
        if ($unitIds->isNotEmpty()) {
            $names = DB::table('product_unit_transfer')
                ->whereIn('id', $unitIds)
                ->pluck('unit1')
                ->map(fn ($u) => (string) ($u ?? ''))
                ->filter()
                ->unique()
                ->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::general.filter_panel_unit').': '.$names;
            }
        }

        $payMethods = collect($request->input('payment_method'))->filter()->values();
        if ($payMethods->isNotEmpty()) {
            $labels = $payMethods->map(fn ($m) => $this->salesComparisonPaymentMethodLabel((string) $m))->unique()->implode($sep);
            if ($labels !== '') {
                $lines[] = __('report::general.filter_panel_payment').': '.$labels;
            }
        }

        return $lines;
    }

    private function productPurchaseReportFilterSummary(Request $request): string
    {
        $lines = [];
        $mode = $request->input('report_mode', 'detail');
        $lines[] = __('report::general.product_purchase_report_mode').': '.($mode === 'summary'
            ? __('report::general.product_purchase_report_mode_summary')
            : __('report::general.product_purchase_report_mode_detail'));
        $dateRange = trim((string) $request->input('sale_date_range', ''));
        if ($dateRange !== '') {
            $lines[] = __('report::purchase.Sale Date Range').': '.$dateRange;
        }
        $lines = array_merge($lines, $this->purchaseLineReportFilterSummaryLines($request));

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $exportKeys
     * @return array{title: string, generated_at: string, filters: string, row_count: int, headers: array}
     */
    private function buildProductPurchasesExportMeta(Request $request, int $rowCount, array $exportKeys): array
    {
        return [
            'title' => __('menuItemLang.product-purchase-report'),
            'generated_at' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            'filters' => $this->productPurchaseReportFilterSummary($request),
            'row_count' => $rowCount,
            'headers' => ReportTransactionsUtile::getProductPurchasesExportHeaderLabels($exportKeys),
        ];
    }

    /**
     * @return list<string>
     */
    private function resolveProductPurchasesExportColumnKeys(Request $request): array
    {
        $all = ReportTransactionsUtile::getProductPurchasesExportColumnKeys();
        $raw = $request->input('export_columns', []);
        $requested = collect(\Illuminate\Support\Arr::wrap($raw))
            ->flatten()
            ->filter(fn ($k) => is_string($k) && $k !== '')
            ->values()
            ->all();
        if ($requested === []) {
            return $all;
        }
        $picked = array_values(array_intersect($all, $requested));

        return $picked === [] ? $all : $picked;
    }

    public function productPurchasesExportExcel(Request $request)
    {
        $isSummary = $request->input('report_mode', 'detail') === 'summary';
        $exportKeys = $this->resolveProductPurchasesExportColumnKeys($request);
        $query = $isSummary
            ? $this->buildProductPurchaseReportSummaryQuery($request)
            : $this->buildProductPurchaseReportDetailQuery($request);
        $rows = $query->get();
        $mapped = $rows->map(fn ($r) => ReportTransactionsUtile::mapProductPurchasesRowForExport($r, $isSummary, $exportKeys));
        $meta = $this->buildProductPurchasesExportMeta($request, $mapped->count(), $exportKeys);
        $filename = 'product-purchase-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new ProductSalesExcelExport($mapped, $meta), $filename);
    }

    public function productPurchasesExportPdf(Request $request)
    {
        $isSummary = $request->input('report_mode', 'detail') === 'summary';
        $exportKeys = $this->resolveProductPurchasesExportColumnKeys($request);
        $query = $isSummary
            ? $this->buildProductPurchaseReportSummaryQuery($request)
            : $this->buildProductPurchaseReportDetailQuery($request);
        $rows = $query->get();
        $mapped = $rows->map(fn ($r) => ReportTransactionsUtile::mapProductPurchasesRowForExport($r, $isSummary, $exportKeys));
        $meta = $this->buildProductPurchasesExportMeta($request, $mapped->count(), $exportKeys);
        $headers = ReportTransactionsUtile::getProductPurchasesExportHeaderLabels($exportKeys);
        $textStartColIndexes = ReportTransactionsUtile::productPurchasesExportPdfTextStartIndexes($exportKeys);

        $html = view('report::sales.product_sales_export_pdf', [
            'meta' => $meta,
            'headers' => $headers,
            'rows' => $mapped->all(),
            'textStartColIndexes' => $textStartColIndexes,
        ])->render();

        if (! is_dir(storage_path('temp/mpdf'))) {
            @mkdir(storage_path('temp/mpdf'), 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 6,
            'margin_right' => 6,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => storage_path('temp/mpdf'),
        ]);
        if (app()->getLocale() === 'ar') {
            $mpdf->SetDirectionality('rtl');
        }
        $mpdf->WriteHTML($html);
        $filename = 'product-purchase-'.now()->format('Y-m-d-His').'.pdf';
        $binary = $mpdf->Output('', Destination::STRING_RETURN);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function getproductPurchaseReport(Request $request)
    {
        $transactionUtile = new ReportTransactionsUtile;

        if ($request->ajax()) {
            $mode = $request->input('report_mode', 'detail');
            if ($mode === 'summary') {
                $query = $this->buildProductPurchaseReportSummaryQuery($request);

                return ReportTransactionsUtile::getProductPurchasesReportSummary($query);
            }

            $query = $this->buildProductPurchaseReportDetailQuery($request);

            return ReportTransactionsUtile::getProductPurchasesReport($query);
        }

        $columns = $transactionUtile->getsProductPurchasesColumns();

        return view('report::sales.product-purchase-report')
            ->with(compact('columns'));
    }

    public function purchasePaymentReport(Request $request)
    {
        $transactionUtile = new ReportTransactionsUtile;

        if ($request->ajax()) {
            $query = $this->buildPurchasePaymentReportQuery($request);

            return $transactionUtile->purchasePaymentReportTable($query);
        }

        $columns = $transactionUtile->purchasePaymentReportColumns();
        $purchasePaymentColumnPicker = ReportTransactionsUtile::getPurchasePaymentColumnPickerMeta();
        $purchasePaymentExportColumnKeys = ReportTransactionsUtile::getPurchasePaymentExportColumnKeys();

        return view('report::sales.purchase_payment_report')
            ->with(compact('columns', 'purchasePaymentColumnPicker', 'purchasePaymentExportColumnKeys'));
    }

    public function purchasePaymentExportExcel(Request $request)
    {
        $exportKeys = $this->resolvePurchasePaymentExportColumnKeys($request);
        $rows = $this->buildPurchasePaymentReportQuery($request)->get();
        $mapped = $rows->map(fn ($r) => ReportTransactionsUtile::mapSellPaymentRowForExport($r, $exportKeys));
        $meta = $this->buildPurchasePaymentExportMeta($request, $mapped->count(), $exportKeys);
        $filename = 'purchase-payment-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new SellPaymentExcelExport($mapped, $meta), $filename);
    }

    public function purchasePaymentExportPdf(Request $request)
    {
        $exportKeys = $this->resolvePurchasePaymentExportColumnKeys($request);
        $rows = $this->buildPurchasePaymentReportQuery($request)->get();
        $mapped = $rows->map(fn ($r) => ReportTransactionsUtile::mapSellPaymentRowForExport($r, $exportKeys));
        $meta = $this->buildPurchasePaymentExportMeta($request, $mapped->count(), $exportKeys);
        $headers = ReportTransactionsUtile::getPurchasePaymentExportHeaderLabels($exportKeys);
        $textStartColIndexes = ReportTransactionsUtile::sellPaymentExportPdfTextStartIndexes($exportKeys);

        $html = view('report::sales.sell_payment_export_pdf', [
            'meta' => $meta,
            'headers' => $headers,
            'rows' => $mapped->all(),
            'textStartColIndexes' => $textStartColIndexes,
        ])->render();

        if (! is_dir(storage_path('temp/mpdf'))) {
            @mkdir(storage_path('temp/mpdf'), 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 6,
            'margin_right' => 6,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => storage_path('temp/mpdf'),
        ]);
        if (app()->getLocale() === 'ar') {
            $mpdf->SetDirectionality('rtl');
        }
        $mpdf->WriteHTML($html);
        $filename = 'purchase-payment-'.now()->format('Y-m-d-His').'.pdf';
        $binary = $mpdf->Output('', Destination::STRING_RETURN);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  list<string>  $exportKeys
     * @return array{title: string, generated_at: string, filters: string, row_count: int, headers: array}
     */
    private function buildPurchasePaymentExportMeta(Request $request, int $rowCount, array $exportKeys): array
    {
        return [
            'title' => __('menuItemLang.purchase-payment-report'),
            'generated_at' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            'filters' => $this->purchasePaymentExportFilterSummary($request),
            'row_count' => $rowCount,
            'headers' => ReportTransactionsUtile::getPurchasePaymentExportHeaderLabels($exportKeys),
        ];
    }

    /**
     * @return list<string>
     */
    private function resolvePurchasePaymentExportColumnKeys(Request $request): array
    {
        $all = ReportTransactionsUtile::getPurchasePaymentExportColumnKeys();
        $raw = $request->input('export_columns', []);
        $requested = collect(\Illuminate\Support\Arr::wrap($raw))
            ->flatten()
            ->filter(fn ($k) => is_string($k) && $k !== '')
            ->values()
            ->all();
        if ($requested === []) {
            return $all;
        }
        $picked = array_values(array_intersect($all, $requested));

        return $picked === [] ? $all : $picked;
    }

    private function purchasePaymentExportFilterSummary(Request $request): string
    {
        $locale = app()->getLocale();
        $sep = $locale === 'ar' ? '، ' : ', ';
        $lines = [];
        $dr = trim((string) $request->input('payment_date_range', ''));
        if ($dr !== '') {
            $lines[] = __('report::purchase.Payment Date Range').': '.$dr;
        }

        $branchIds = collect($request->input('branch_id'))->filter()->values();
        if ($branchIds->isNotEmpty()) {
            $names = Establishment::whereIn('id', $branchIds)->get()->map(function ($e) use ($locale) {
                return $locale === 'ar' ? ($e->name ?? '') : ($e->name_en ?? $e->name ?? '');
            })->filter()->unique()->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::general.filter_panel_branch').': '.$names;
            }
        }

        $deviceIds = collect($request->input('device_id'))->filter()->values();
        if ($deviceIds->isNotEmpty()) {
            $names = EstPos::whereIn('id', $deviceIds)->pluck('name')->filter()->unique()->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::purchase.Device').': '.$names;
            }
        }

        $supplierIds = collect($request->input('supplier_id'))->filter()->values();
        if ($supplierIds->isNotEmpty()) {
            $names = Contact::whereIn('id', $supplierIds)->pluck('name')->filter()->unique()->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::purchase.Supplier').': '.$names;
            }
        }

        $payMethods = collect($request->input('payment_method'))->filter()->values();
        if ($payMethods->isNotEmpty()) {
            $labels = $payMethods->map(fn ($m) => $this->salesComparisonPaymentMethodLabel((string) $m))->unique()->implode($sep);
            if ($labels !== '') {
                $lines[] = __('report::general.filter_panel_payment').': '.$labels;
            }
        }

        $statuses = collect($request->input('payment_status'))->filter()->values();
        if ($statuses->isNotEmpty()) {
            $labels = $statuses->map(function ($s) {
                $s = (string) $s;
                $t = __('report::purchase.'.$s);

                return $t !== 'report::purchase.'.$s ? $t : $s;
            })->unique()->implode($sep);
            if ($labels !== '') {
                $lines[] = __('report::purchase.Payment Status').': '.$labels;
            }
        }

        return $lines === [] ? __('report::general.export_no_filters') : implode("\n", $lines);
    }

    private function buildPurchasePaymentReportQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = TransactionPayments::query()
            ->leftJoin('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->leftJoin('est_establishments as e', 't.establishment_id', '=', 'e.id')
            ->leftJoin('est_pos as d', 't.device_id', '=', 'd.id')
            ->whereIn('t.type', ['purchases'])
            ->select(
                DB::raw("IF(transaction_payments.transaction_id IS NULL,
                                (SELECT c.name FROM transactions as ts
                                JOIN cs_contacts as c ON ts.contact_id=c.id),
                                (SELECT CONCAT(COALESCE(c.name, '')) FROM transactions as ts JOIN
                                    cs_contacts as c ON ts.contact_id=c.id
                                    WHERE ts.id=t.id
                                )
                            ) as supplier"),
                DB::raw("CASE
                WHEN '".app()->getLocale()."' = 'ar' THEN e.name
                ELSE e.name_en
              END as establishment_name"),
                'd.name as device_name',
                'transaction_payments.amount',
                'transaction_payments.method',
                'transaction_payments.paid_on',
                'transaction_payments.payment_ref_no',
                't.ref_no',
                't.id as transaction_id',
                't.final_total',
                't.created_at',
                't.payment_status',
                'transaction_payments.id as DT_RowId'
            );

        if ($request->has('branch_id')) {
            $branchIds = collect($request->input('branch_id'))->filter()->values()->toArray();
            if (! empty($branchIds)) {
                $query->whereIn('t.establishment_id', $branchIds);
            }
        }
        if ($request->has('device_id')) {
            $deviceIds = collect($request->input('device_id'))->filter()->values()->toArray();
            if (! empty($deviceIds)) {
                $query->whereIn('t.device_id', $deviceIds);
            }
        }
        if ($request->has('cashier_id')) {
            $query->where('t.created_by', $request->input('cashier_id'));
        }

        if ($request->has('supplier_id')) {
            $supplierIds = collect($request->input('supplier_id'))->filter()->values()->toArray();
            if (! empty($supplierIds)) {
                $query->whereIn('t.contact_id', $supplierIds);
            }
        }

        if ($request->has('payment_method')) {
            $paymentMethods = collect($request->input('payment_method'))->filter()->values()->toArray();
            if (! empty($paymentMethods)) {
                $query->whereIn('transaction_payments.method', $paymentMethods);
            }
        }

        if ($request->has('payment_status')) {
            $paymentStatuses = collect($request->input('payment_status'))->filter()->values()->toArray();
            if (! empty($paymentStatuses)) {
                $query->whereIn('t.payment_status', $paymentStatuses);
            }
        }

        if (! empty($request->input('payment_date_range'))) {
            $dateRange = explode(' - ', $request->input('payment_date_range'));
            if (count($dateRange) === 2) {
                $from = date('Y-m-d', strtotime($dateRange[0]));
                $to = date('Y-m-d', strtotime($dateRange[1]));
                $query->whereBetween('transaction_payments.paid_on', [$from, $to]);
            }
        }

        return $query->orderBy('t.created_at', 'desc');
    }

    public function salesPaymentReport(Request $request)
    {

        $transactionUtile = new ReportTransactionsUtile;

        if ($request->ajax()) {
            $query = $this->buildSellPaymentReportQuery($request);

            return $transactionUtile->purchasePaymentReportTable($query);
        }

        $columns = $transactionUtile->salesPaymentReportColumns();
        $sellPaymentColumnPicker = ReportTransactionsUtile::getSellPaymentColumnPickerMeta();
        $sellPaymentExportColumnKeys = ReportTransactionsUtile::getSellPaymentExportColumnKeys();

        return view('report::sales.sell_payment_report')
            ->with(compact('columns', 'sellPaymentColumnPicker', 'sellPaymentExportColumnKeys'));
    }

    public function sellPaymentExportExcel(Request $request)
    {
        $exportKeys = $this->resolveSellPaymentExportColumnKeys($request);
        $rows = $this->buildSellPaymentReportQuery($request)->get();
        $mapped = $rows->map(fn ($r) => ReportTransactionsUtile::mapSellPaymentRowForExport($r, $exportKeys));
        $meta = $this->buildSellPaymentExportMeta($request, $mapped->count(), $exportKeys);
        $filename = 'sell-payment-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new SellPaymentExcelExport($mapped, $meta), $filename);
    }

    public function sellPaymentExportPdf(Request $request)
    {
        $exportKeys = $this->resolveSellPaymentExportColumnKeys($request);
        $rows = $this->buildSellPaymentReportQuery($request)->get();
        $mapped = $rows->map(fn ($r) => ReportTransactionsUtile::mapSellPaymentRowForExport($r, $exportKeys));
        $meta = $this->buildSellPaymentExportMeta($request, $mapped->count(), $exportKeys);
        $headers = ReportTransactionsUtile::getSellPaymentExportHeaderLabels($exportKeys);
        $textStartColIndexes = ReportTransactionsUtile::sellPaymentExportPdfTextStartIndexes($exportKeys);

        $html = view('report::sales.sell_payment_export_pdf', [
            'meta' => $meta,
            'headers' => $headers,
            'rows' => $mapped->all(),
            'textStartColIndexes' => $textStartColIndexes,
        ])->render();

        if (! is_dir(storage_path('temp/mpdf'))) {
            @mkdir(storage_path('temp/mpdf'), 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 6,
            'margin_right' => 6,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => storage_path('temp/mpdf'),
        ]);
        if (app()->getLocale() === 'ar') {
            $mpdf->SetDirectionality('rtl');
        }
        $mpdf->WriteHTML($html);
        $filename = 'sell-payment-'.now()->format('Y-m-d-His').'.pdf';
        $binary = $mpdf->Output('', Destination::STRING_RETURN);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  list<string>  $exportKeys
     * @return array{title: string, generated_at: string, filters: string, row_count: int, headers: array}
     */
    private function buildSellPaymentExportMeta(Request $request, int $rowCount, array $exportKeys): array
    {
        return [
            'title' => __('menuItemLang.sell-payment-report'),
            'generated_at' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            'filters' => $this->sellPaymentExportFilterSummary($request),
            'row_count' => $rowCount,
            'headers' => ReportTransactionsUtile::getSellPaymentExportHeaderLabels($exportKeys),
        ];
    }

    /**
     * @return list<string>
     */
    private function resolveSellPaymentExportColumnKeys(Request $request): array
    {
        $all = ReportTransactionsUtile::getSellPaymentExportColumnKeys();
        $raw = $request->input('export_columns', []);
        $requested = collect(\Illuminate\Support\Arr::wrap($raw))
            ->flatten()
            ->filter(fn ($k) => is_string($k) && $k !== '')
            ->values()
            ->all();
        if ($requested === []) {
            return $all;
        }
        $picked = array_values(array_intersect($all, $requested));

        return $picked === [] ? $all : $picked;
    }

    private function sellPaymentExportFilterSummary(Request $request): string
    {
        $locale = app()->getLocale();
        $sep = $locale === 'ar' ? '، ' : ', ';
        $lines = [];
        $dr = trim((string) $request->input('payment_date_range', ''));
        if ($dr !== '') {
            $lines[] = __('report::purchase.Payment Date Range').': '.$dr;
        }

        $branchIds = collect($request->input('branch_id'))->filter()->values();
        if ($branchIds->isNotEmpty()) {
            $names = Establishment::whereIn('id', $branchIds)->get()->map(function ($e) use ($locale) {
                return $locale === 'ar' ? ($e->name ?? '') : ($e->name_en ?? $e->name ?? '');
            })->filter()->unique()->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::general.filter_panel_branch').': '.$names;
            }
        }

        $deviceIds = collect($request->input('device_id'))->filter()->values();
        if ($deviceIds->isNotEmpty()) {
            $names = EstPos::whereIn('id', $deviceIds)->pluck('name')->filter()->unique()->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::purchase.Device').': '.$names;
            }
        }

        $customerIds = collect($request->input('customer_id'))->filter()->values();
        if ($customerIds->isNotEmpty()) {
            $names = Contact::whereIn('id', $customerIds)->pluck('name')->filter()->unique()->implode($sep);
            if ($names !== '') {
                $lines[] = __('report::general.filter_panel_customer').': '.$names;
            }
        }

        $payMethods = collect($request->input('payment_method'))->filter()->values();
        if ($payMethods->isNotEmpty()) {
            $labels = $payMethods->map(fn ($m) => $this->salesComparisonPaymentMethodLabel((string) $m))->unique()->implode($sep);
            if ($labels !== '') {
                $lines[] = __('report::general.filter_panel_payment').': '.$labels;
            }
        }

        $statuses = collect($request->input('payment_status'))->filter()->values();
        if ($statuses->isNotEmpty()) {
            $labels = $statuses->map(function ($s) {
                $s = (string) $s;
                $t = __('report::purchase.'.$s);

                return $t !== 'report::purchase.'.$s ? $t : $s;
            })->unique()->implode($sep);
            if ($labels !== '') {
                $lines[] = __('report::purchase.Payment Status').': '.$labels;
            }
        }

        return $lines === [] ? __('report::general.export_no_filters') : implode("\n", $lines);
    }

    private function buildSellPaymentReportQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = TransactionPayments::query()
            ->leftJoin('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->leftJoin('est_establishments as e', 't.establishment_id', '=', 'e.id')
            ->leftJoin('est_pos as d', 't.device_id', '=', 'd.id')
            ->whereIn('t.type', ['sell'])
            ->select(
                DB::raw("IF(transaction_payments.transaction_id IS NULL,
                                (SELECT c.name FROM transactions as ts
                                JOIN cs_contacts as c ON ts.contact_id=c.id),
                                (SELECT CONCAT(COALESCE(c.name, '')) FROM transactions as ts JOIN
                                    cs_contacts as c ON ts.contact_id=c.id
                                    WHERE ts.id=t.id
                                )
                            ) as supplier"),
                DB::raw("CASE
                WHEN '".app()->getLocale()."' = 'ar' THEN e.name
                ELSE e.name_en
              END as establishment_name"),
                'd.name as device_name',
                'transaction_payments.amount',
                'transaction_payments.method',
                'transaction_payments.paid_on',
                'transaction_payments.payment_ref_no',
                't.ref_no',
                't.id as transaction_id',
                't.final_total',
                't.created_at',
                't.payment_status',
                'transaction_payments.id as DT_RowId'
            );

        if ($request->has('branch_id')) {
            $branchIds = collect($request->input('branch_id'))->filter()->values()->toArray();
            if (! empty($branchIds)) {
                $query->whereIn('t.establishment_id', $branchIds);
            }
        }
        if ($request->has('device_id')) {
            $deviceIds = collect($request->input('device_id'))->filter()->values()->toArray();
            if (! empty($deviceIds)) {
                $query->whereIn('t.device_id', $deviceIds);
            }
        }
        if ($request->has('cashier_id')) {
            $query->where('t.created_by', $request->input('cashier_id'));
        }

        if ($request->has('customer_id')) {
            $supplierIds = collect($request->input('customer_id'))->filter()->values()->toArray();
            if (! empty($supplierIds)) {
                $query->whereIn('t.contact_id', $supplierIds);
            }
        }

        if ($request->has('payment_method')) {
            $paymentMethods = collect($request->input('payment_method'))->filter()->values()->toArray();
            if (! empty($paymentMethods)) {
                $query->whereIn('transaction_payments.method', $paymentMethods);
            }
        }

        if ($request->has('payment_status')) {
            $paymentStatuses = collect($request->input('payment_status'))->filter()->values()->toArray();
            if (! empty($paymentStatuses)) {
                $query->whereIn('t.payment_status', $paymentStatuses);
            }
        }

        if (! empty($request->input('payment_date_range'))) {
            $dateRange = explode(' - ', $request->input('payment_date_range'));
            if (count($dateRange) === 2) {
                $from = date('Y-m-d', strtotime($dateRange[0]));
                $to = date('Y-m-d', strtotime($dateRange[1]));
                $query->whereBetween('transaction_payments.paid_on', [$from, $to]);
            }
        }

        return $query->orderBy('t.created_at', 'desc');
    }

    public function getProfitLoss(Request $request)
    {
        $transactionUtile = new TransactionUtile;

        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $data = $transactionUtile->getProfitLossDetails($start_date, $end_date);

            return view('report::profit_loss_details', compact('data'))->render();
        }
        $data = $transactionUtile->getProfitLossDetails();

        return view('report::profit_loss', compact('data'));
    }

    public function getPurchaseSell(Request $request)
    {
        $transactionUtile = new TransactionUtile;

        // Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $location_id = $request->get('location_id');

            // return $request->date_range;
            $dueDateRange = trim($request->date_range);
            $dates = explode(' إلى ', $dueDateRange);
            if ($request->date_range) {
                $start_date = $dates[0];
                $end_date = $dates[1];
            }

            $purchase_details = $transactionUtile->getPurchaseTotals($start_date, $end_date, $location_id);
            $sell_details = $transactionUtile->getSellTotals($start_date, $end_date, $location_id);

            $transaction_types = [
                'purchases-return',
                'sell-return',
            ];

            $transaction_totals = $transactionUtile->getTransactionTotals(
                $transaction_types,
                $start_date,
                $end_date,
                $location_id
            );

            $total_purchase_return_inc_tax = $transaction_totals['total_purchase_return_inc_tax'];
            $total_sell_return_inc_tax = $transaction_totals['total_sell_return_inc_tax'];

            $purchase_data = '
            <tr>
                <td>'.__('report::general.total_purchase_inc_tax').'</td>
                <td>'.($purchase_details['total_purchase_inc_tax'] ?? 0).'</td>
            </tr>
            <tr>
                <td>'.__('report::general.total_purchase_return').'</td>
                <td>'.($total_purchase_return_inc_tax ?? 0).'</td>
            </tr>
            <tr>
                <td>'.__('report::general.purchase_due').'</td>
                <td>'.($purchase_details['purchase_due'] ?? 0).'</td>
            </tr>';

            $sales_data = '
            <tr>
                <td>'.__('report::general.total_sell_inc_tax').'</td>
                <td>'.($sell_details['total_sell_inc_tax'] ?? 0).'</td>
            </tr>
            <tr>
                <td>'.__('report::general.total_sell_return').'</td>
                <td>'.($total_sell_return_inc_tax ?? 0).'</td>
            </tr>
            <tr>
                <td>'.__('report::general.invoice_due').'</td>
                <td>'.($sell_details['invoice_due'] ?? 0).'</td>
            </tr>';

            $difference = [
                'total' => $sell_details['total_sell_inc_tax'] - $total_sell_return_inc_tax - ($purchase_details['total_purchase_inc_tax'] - $total_purchase_return_inc_tax),
                'due' => $sell_details['invoice_due'] - $purchase_details['purchase_due'],
            ];

            return response()->json([
                'purchase_data' => $purchase_data,
                'sales_data' => $sales_data,
                'difference' => $difference,
            ]);
        }

        return view('report::sales.purchase_sell');
    }

    public function getProfit($by = null)
    {
        $query = TransactionSellLine::join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->leftJoin('transactione_purchases_lines as TPL', function ($join) {
                $join->on('transaction_sell_lines.transaction_id', '=', 'TPL.transaction_id')
                    ->on('transaction_sell_lines.product_id', '=', 'TPL.product_id');
            })
            ->join('product_products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
            ->where('sale.type', 'sell')
            ->where('sale.status', 'approved');
        $query->addSelect(DB::raw('
            SUM(
                (transaction_sell_lines.qyt - COALESCE(TPL.qyt, 0)) *
                (transaction_sell_lines.unit_price_inc_tax - COALESCE(TPL.unit_price_inc_tax, 0))
            ) AS gross_profit
        '));

        if ($by == 'product') {
            $query->addSelect(DB::raw("
        CONCAT(P.name_ar, ' / ', P.name_en, ' (', P.SKU, ')') as product
    "))->groupBy(DB::raw('P.id, P.name_ar, P.name_en, P.SKU'));
        }

        if ($by == 'category') {
            $query->join('product_categories as C', 'C.id', '=', 'P.category_id')
                ->addSelect(DB::raw("
            CONCAT(C.name_ar, ' / ', C.name_en) as category
           "))
                ->groupBy('C.id', 'C.name_ar', 'C.name_en');
        }

        if ($by == 'location') {
            $query->join('est_establishments as E', 'sale.establishment_id', '=', 'E.id')
                ->addSelect('E.name as location')
                ->groupBy('E.id', 'E.name');
        }

        if ($by == 'invoice') {
            $query->addSelect(
                'sale.ref_no',
                'sale.id as transaction_id',
                'sale.discount_type',
                'sale.discount_amount',
                'sale.total_before_tax'
            )
                ->groupBy(
                    'sale.ref_no',
                    'sale.id',
                    'sale.discount_type',
                    'sale.discount_amount',
                    'sale.total_before_tax'
                );
        }
        if ($by == 'date') {
            $query->addSelect('sale.transaction_date')
                ->groupBy(DB::raw('DATE(sale.transaction_date)'), 'sale.transaction_date');
        }

        if ($by == 'day') {
            $results = $query->addSelect(DB::raw('DAYNAME(sale.transaction_date) as day'))
                ->addSelect(DB::raw('SUM(
                (transaction_sell_lines.qyt - COALESCE(TPL.qyt, 0)) *
                (transaction_sell_lines.unit_price_inc_tax - COALESCE(TPL.unit_price_inc_tax, 0))
            ) AS gross_profit'))
                ->groupBy(DB::raw('DAYNAME(sale.transaction_date)'))  // إضافة DAYNAME إلى GROUP BY
                ->get();
            $profits = [];
            foreach ($results as $result) {
                $profits[strtolower($result->day)] = $result->gross_profit;
            }

            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

            foreach ($days as $day) {
                if (! isset($profits[$day])) {
                    $profits[$day] = 0;
                }
            }

            return view('report::profit_by_day')->with(compact('profits', 'days'));
        }

        if ($by == 'customer') {
            $query->join('cs_contacts as CU', 'sale.contact_id', '=', 'CU.id')
                ->addSelect('CU.name as customer')
                ->groupBy('sale.contact_id', 'CU.name');
        }
        $datatable = DataTables::of($query);

        if (in_array($by, ['invoice'])) {
            $datatable->editColumn('gross_profit', function ($row) {
                $discount = $row->discount_amount;
                if ($row->discount_type == 'percent') {
                    $discount = ($row->discount_amount * $row->total_before_vat) / 100;
                }

                return $profit = $row->gross_profit - $discount;
                // return $this->transactionUtil->num_f($profit, true);
            });
        } else {
            $datatable->editColumn(
                'gross_profit',
                function ($row) {
                    return $row->gross_profit;
                }
            );
        }

        if ($by == 'category') {
            $datatable->editColumn(
                'category',
                '{{$category ?? __("report::general.uncategorized")}}'
            );
        }

        if ($by == 'date') {
            $datatable->editColumn('transaction_date', '{{($transaction_date)}}');
        }

        if ($by == 'customer') {
            $datatable->editColumn('customer', '{{$customer}}');
        }

        if ($by == 'invoice') {
            $datatable->editColumn('ref_no', function ($row) {
                return '<a href="'.action([TransactionController::class, 'show'], [$row->transaction_id])
                    .'"  data-container=".view_modal" class="btn-modal">'.$row->ref_no.'</a>';
            });
        }

        return $datatable->rawColumns(['gross_profit', 'category', 'customer', 'ref_no'])
            ->make(true);
    }

    public function productInventoryReport(Request $request)
    {
        $transactionUtile = new ReportTransactionsUtile;

        if ($request->ajax()) {
            $query = DB::table('transactions as t')
                ->leftJoin('cs_contacts as c', 't.contact_id', '=', 'c.id')
                ->leftJoin('transactione_purchases_lines as pl', 't.id', '=', 'pl.transaction_id')
                ->leftJoin('transaction_sell_lines as sl', 't.id', '=', 'sl.transaction_id')
                ->leftJoin('product_products as p', function ($join) {
                    $join->on('pl.product_id', '=', 'p.id')
                        ->orOn('sl.product_id', '=', 'p.id');
                })
                ->leftJoin('product_unit_transfer as u', function ($join) {
                    $join->orOn('pl.unit_id', '=', 'u.id')
                        ->orOn('sl.unit_id', '=', 'u.id');
                })
                ->leftJoin('est_establishments as e', 't.establishment_id', '=', 'e.id')
                ->select(
                    app()->getLocale() == 'ar' ? 'p.name_ar as product_name' : 'p.name_en as product_name',
                    app()->getLocale() == 'ar' ? 'e.name as establishment_name' : 'e.name_en as establishment_name',
                    DB::raw("CASE
                        WHEN sl.id IS NOT NULL THEN '-'
                        ELSE '+'
                    END as transfer_in_out"),
                    't.transfer_status as process',
                    DB::raw('CASE
                        WHEN sl.id IS NOT NULL THEN sl.qyt
                        ELSE pl.qyt
                    END as quantity'),
                    't.created_at as transfer_date',
                    't.type as type',
                    'u.unit1 as unit',
                    'c.name as entity',
                    't.transaction_date as transaction_date'
                )
                ->where(function ($query) {
                    $query->where(function ($subQuery) {
                        $subQuery->whereIn('t.type', ['purchases', 'WASTE', 'PREP', 'sell', 'purchases-return', 'sell-return', 'PO0'])
                            ->where('t.status', 'approved');
                    })
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('t.type', 'TRANSFER')
                                ->where(function ($q) {
                                    $q->where('t.transfer_status', 'partiallyReceived')
                                        ->orWhere('t.transfer_status', 'fullyReceived');
                                })
                                ->where('t.status', 'approved');
                        });
                });
            if ($request->has('branch_id')) {
                $branchIds = collect($request->input('branch_id'))->filter()->values()->toArray();
                if (! empty($branchIds)) {
                    $query->whereIn('t.establishment_id', $branchIds);
                }
            }
            if ($request->has('product_id')) {
                $products = collect($request->input('product_id'))->filter()->values()->toArray();
                if (! empty($products)) {
                    $query->whereIn('p.id', $products);
                }
            }
            if ($request->has('process_type')) {
                $processType = collect($request->input('process_type'))->filter()->values()->toArray();
                if (! empty($processType)) {
                    $query->whereIn('t.type', $processType);
                }
            }

            if (! empty($request->input('inventory_date_range'))) {
                $dateRange = explode(' - ', $request->input('inventory_date_range'));
                if (count($dateRange) === 2) {
                    $from = date('Y-m-d', strtotime($dateRange[0]));
                    $to = date('Y-m-d', strtotime($dateRange[1]));
                    $query->whereBetween('t.transaction_date', [$from, $to]);
                }
            }

            $results = $query->orderBy('t.created_at', 'desc')->get();

            return $transactionUtile->productInventoryReportTable($results);
        }

        $columns = $transactionUtile->productInventoryReportColumns();

        return view('report::sales.product_inventory_report')
            ->with(compact('columns'));
    }

    public function productInventorySummary(Request $request)
    {
        $transactionUtile = new ReportTransactionsUtile;

        if ($request->ajax()) {
            $query = DB::table('transactions as t')
                ->leftJoin('transactione_purchases_lines as pl', 't.id', '=', 'pl.transaction_id')
                ->leftJoin('transaction_sell_lines as sl', 't.id', '=', 'sl.transaction_id')
                ->leftJoin('product_products as p', function ($join) {
                    $join->on('pl.product_id', '=', 'p.id')
                        ->orOn('sl.product_id', '=', 'p.id');
                })
                ->leftJoin('est_establishments as e', 't.establishment_id', '=', 'e.id')
                ->leftJoin('product_unit_transfer as pu', 'pl.unit_id', '=', 'pu.id')
                ->leftJoin('product_unit_transfer as su', 'sl.unit_id', '=', 'su.id')
                /*      ->leftJoin('product_inventories as pi', function ($join) {
                    $join->on('p.id', '=', 'pi.product_id')
                        ->on('t.establishment_id', '=', 'pi.establishment_id');
                })*/
                ->select(
                    'p.sku as sku',
                    'p.id as product_id',
                    'e.id as establishment_id',
                    app()->getLocale() == 'ar' ? 'p.name_ar as product_name' : 'p.name_en as product_name',
                    DB::raw("CASE
                    WHEN '".app()->getLocale()."' = 'ar' THEN e.name
                    ELSE e.name_en
                END as establishment_name"),
                    DB::raw("
                    (
                        SELECT COALESCE(
                            MAX(
                                CASE
                                    WHEN pu_disp_sub.unit2 IS NOT NULL AND '".app()->getLocale()."' = 'ar' THEN pu_disp_sub.unit1
                                    WHEN pu_disp_sub.unit2 IS NOT NULL AND '".app()->getLocale()."' = 'en' THEN pu_disp_sub.unit1
                                    ELSE NULL
                                END
                            ),
                            MAX(
                                CASE
                                    WHEN '".app()->getLocale()."' = 'ar' THEN pu_disp_sub.unit1
                                    ELSE pu_disp_sub.unit1
                                END
                            )
                        )
                        FROM product_unit_transfer as pu_disp_sub
                        WHERE pu_disp_sub.product_id = p.id
                    ) as base_unit_name
                "),
                    DB::raw("
                    SUM(
                        CASE
                            WHEN t.type = 'purchases' AND t.status = 'approved'
                            THEN pl.qyt * (
                                CASE
                                    WHEN pu.transfer > 0 THEN 1
                                    ELSE (
                                        SELECT COALESCE(MAX(transfer), 1)
                                        FROM product_unit_transfer AS pu_max
                                        WHERE pu_max.product_id = p.id AND pu_max.transfer > 0
                                    )
                                END
                            )
                            ELSE 0
                        END
                    ) as purchased_quantity
                "),
                    DB::raw("
                    SUM(
                        CASE
                            WHEN t.type = 'sell' AND t.status = 'approved'
                            THEN sl.qyt * (
                                CASE
                                    WHEN su.transfer > 0 THEN 1
                                    ELSE (
                                        SELECT COALESCE(MAX(transfer), 1)
                                        FROM product_unit_transfer AS su_max
                                        WHERE su_max.product_id = p.id AND su_max.transfer > 0
                                    )
                                END
                            )
                            ELSE 0
                        END
                    ) as sales_quantity
                "),
                    DB::raw("
SUM(
        CASE
            WHEN t.type = 'WASTE' AND t.status = 'approved'
            THEN sl.qyt * (
                CASE
                    WHEN su.transfer > 0 THEN 1
                    ELSE (
                        SELECT COALESCE(MAX(transfer), 1)
                        FROM product_unit_transfer AS su_max
                        WHERE su_max.product_id = p.id AND su_max.transfer > 0
                    )
                END
            )
            ELSE 0
        END
    ) as waste
"),
                    DB::raw("
                    SUM(
                        CASE
                            WHEN t.type = 'purchases-return' AND t.status = 'approved'
                            THEN pl.qyt * (
                                CASE
                                    WHEN pu.transfer > 0 THEN 1
                                    ELSE (
                                        SELECT COALESCE(MAX(transfer), 1)
                                        FROM product_unit_transfer AS pu_max
                                        WHERE pu_max.product_id = p.id AND pu_max.transfer > 0
                                    )
                                END
                            ) * -1
                            ELSE 0
                        END
                    ) as purchase_returns
                "),
                    DB::raw("
    SUM(
        CASE
            WHEN t.type = 'TRANSFER' AND t.status = 'approved' AND t.transfer_status IN ('partiallyReceived', 'fullyReceived')
            THEN (
                SELECT COALESCE(SUM(
                    pl_inner.qyt * (
                        CASE
                            WHEN pu_inner.transfer > 0 THEN 1
                            ELSE (
                                SELECT COALESCE(MAX(transfer), 1)
                                FROM product_unit_transfer AS pu_max
                                WHERE pu_max.product_id = p.id AND pu_max.transfer > 0
                            )
                        END
                    )
                ), 0)
                FROM transactions AS t_inner
                LEFT JOIN transactione_purchases_lines AS pl_inner ON t_inner.id = pl_inner.transaction_id
                LEFT JOIN product_unit_transfer AS pu_inner ON pl_inner.unit_id = pu_inner.id
                WHERE t_inner.parent_id = t.id
                    AND t_inner.status = 'approved'
                    AND pl_inner.product_id = p.id
            )
            ELSE 0
        END
    ) as transferred_quantity
"),
                    DB::raw("
                    SUM(
                        CASE
                            WHEN t.type = 'PREP' AND t.status = 'approved'
                            THEN pl.qyt * (
                                CASE
                                    WHEN pu.transfer > 0 THEN 1
                                    ELSE (
                                        SELECT COALESCE(MAX(transfer), 1)
                                        FROM product_unit_transfer AS pu_max
                                        WHERE pu_max.product_id = p.id AND pu_max.transfer > 0
                                    )
                                END
                            )
                            ELSE 0
                        END
                    ) as production_quantity
                "),
                    DB::raw("
                    SUM(
                        CASE
                            WHEN t.type = 'PO0' AND t.status = 'approved'
                            THEN pl.qyt * (
                                CASE
                                    WHEN pu.transfer > 0 THEN 1
                                    ELSE (
                                        SELECT COALESCE(MAX(transfer), 1)
                                        FROM product_unit_transfer AS pu_max
                                        WHERE pu_max.product_id = p.id AND pu_max.transfer > 0
                                    )
                                END
                            )
                            ELSE 0
                        END
                    ) as opening_inventory
                "),
                    DB::raw('NULL as counted_quantity'),
                    //     DB::raw("NULL as quantity_on_inventory"),
                    DB::raw('
    (
        SELECT FORMAT(SUM(pi.qty *
            CASE
                WHEN (SELECT COUNT(*) FROM product_unit_transfer WHERE product_id = p.id) > 1
                THEN (SELECT MAX(transfer) FROM product_unit_transfer WHERE product_id = p.id)
                ELSE 1
            END
        ), 2)
        FROM product_inventories as pi
        WHERE pi.establishment_id = t.establishment_id
        AND pi.product_id = p.id
    ) as quantity_on_inventory
')
                )
                ->groupBy('p.id', 'p.sku', app()->getLocale() == 'ar' ? 'p.name_ar' : 'p.name_en', 't.establishment_id', 'e.name', 'e.name_en', 'e.id')
                ->where(function ($query) {
                    $query->where(function ($subQuery) {
                        $subQuery->whereIn('t.type', ['purchases', 'WASTE', 'PREP', 'sell', 'purchases-return', 'sell-return', 'PO0'])
                            ->where('t.status', 'approved');
                    })
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('t.type', 'TRANSFER')
                                ->where(function ($q) {
                                    $q->where('t.transfer_status', 'partiallyReceived')
                                        ->orWhere('t.transfer_status', 'fullyReceived');
                                })
                                ->where('t.status', 'approved');
                        });
                });

            if ($request->has('branch_id')) {
                $branchIds = collect($request->input('branch_id'))->filter()->values()->toArray();
                if (! empty($branchIds)) {
                    $query->whereIn('t.establishment_id', $branchIds);
                }
            }
            if ($request->has('product_id')) {
                $products = collect($request->input('product_id'))->filter()->values()->toArray();
                if (! empty($products)) {
                    $query->whereIn('p.id', $products);
                }
            }
            if ($request->has('process_type')) {
                $processType = collect($request->input('process_type'))->filter()->values()->toArray();
                if (! empty($processType)) {
                    $query->whereIn('t.type', $processType);
                }
            }

            $results = $query->get();

            return $transactionUtile->productInventorySummaryTable($results);
        }

        $columns = $transactionUtile->productInventorySummaryColumns();

        return view('report::sales.product_inventory_summary')
            ->with(compact('columns'));
    }

    public function productInventoryRecord(Request $request, $product_id, $establishment_id)
    {
        $transactionUtile = new ReportTransactionsUtile;
        Log::info($request);
        if ($request->ajax()) {
            $query = DB::table('transactions as t')
                ->leftJoin('cs_contacts as c', 't.contact_id', '=', 'c.id')
                ->leftJoin('transactione_purchases_lines as pl', 't.id', '=', 'pl.transaction_id')
                ->leftJoin('transaction_sell_lines as sl', 't.id', '=', 'sl.transaction_id')
                ->leftJoin('product_products as p', function ($join) {
                    $join->on('pl.product_id', '=', 'p.id')
                        ->orOn('sl.product_id', '=', 'p.id');
                })
                ->leftJoin('product_unit_transfer as u', function ($join) {
                    $join->orOn('pl.unit_id', '=', 'u.id')
                        ->orOn('sl.unit_id', '=', 'u.id');
                })
                ->leftJoin('est_establishments as e', 't.establishment_id', '=', 'e.id')
                ->where('t.establishment_id', $establishment_id)
                ->where(function ($query) use ($product_id) {
                    $query->where('pl.product_id', $product_id)
                        ->orWhere('sl.product_id', $product_id);
                })
                ->select(
                    app()->getLocale() == 'ar' ? 'p.name_ar as product_name' : 'p.name_en as product_name',
                    app()->getLocale() == 'ar' ? 'e.name as establishment_name' : 'e.name_en as establishment_name',
                    DB::raw("CASE
                    WHEN sl.id IS NOT NULL THEN '-'
                    ELSE '+'
                END as transfer_in_out"),
                    't.transfer_status as process',
                    DB::raw('CASE
                    WHEN sl.id IS NOT NULL THEN sl.qyt
                    ELSE pl.qyt
                END as quantity'),
                    't.created_at as transfer_date',
                    't.type as type',
                    'u.unit1 as unit',
                    'c.name as entity',
                    't.transaction_date as transaction_date'
                )
                ->where(function ($query) {
                    $query->whereIn('t.type', ['purchases', 'waste', 'PREP', 'sell', 'purchases-return', 'sell-return', 'transfer', 'PO0'])
                        ->where('t.status', 'approved');
                });

            if ($request->has('branch_id')) {
                $branchIds = collect($request->input('branch_id'))->filter()->values()->toArray();
                if (! empty($branchIds)) {
                    $query->whereIn('t.establishment_id', $branchIds);
                }
            }

            if ($request->has('process_type')) {
                $processType = collect($request->input('process_type'))->filter()->values()->toArray();
                if (! empty($processType)) {
                    $query->whereIn('t.type', $processType);
                }
            }

            if (! empty($request->input('inventory_date_range'))) {
                $dateRange = explode(' - ', $request->input('inventory_date_range'));
                if (count($dateRange) === 2) {
                    $from = date('Y-m-d', strtotime($dateRange[0]));
                    $to = date('Y-m-d', strtotime($dateRange[1]));
                    $query->whereBetween('t.transaction_date', [$from, $to]);
                }
            }

            $results = $query->orderBy('t.created_at', 'desc')->get();

            return $transactionUtile->productInventoryReportTable($results);
        }
        $openingInventory = $request->opening_inventory;
        $purchasedQuantity = $request->purchased_quantity;
        $salesQuantity = $request->sales_quantity;
        $waste = $request->waste;
        $purchaseReturns = $request->purchase_returns;
        $transferredQuantity = $request->transferred_quantity;
        $productionQuantity = $request->production_quantity;
        $quantityOnInventory = $request->quantity_on_inventory;
        $columns = $transactionUtile->productInventoryReportColumns();

        return view('report::sales.product_inventory_record')
            ->with(compact(
                'columns',
                'product_id',
                'establishment_id',
                'openingInventory',
                'purchasedQuantity',
                'salesQuantity',
                'waste',
                'purchaseReturns',
                'transferredQuantity',
                'productionQuantity',
                'quantityOnInventory'
            ));
    }

    public function productStockReport(Request $request)
    {
        $productId = $request->product_id ?: Product::orderBy('id', 'asc')->first()?->id;
        if (! $productId) {
            return response()->json(['error' => 'No products found'], 404);
        }

        $currentProduct = Product::find($productId);
        $previousProduct = Product::where('id', '<', $productId)->orderBy('id', 'desc')->first();
        $nextProduct = Product::where('id', '>', $productId)->orderBy('id', 'asc')->first();

        $main_establishment = Establishment::notMain()->active()->first();
        $establishmentId = $request->branch_id ?: $main_establishment->id;

        $from = $request->from ? date('Y-m-d', strtotime($request->from)) : '1900-01-01';
        $to = $request->to ? date('Y-m-d', strtotime($request->to)) : now();

        $purchases = TransactionePurchasesLine::join('transactions as t', 'transactione_purchases_lines.transaction_id', '=', 't.id')
            ->where('transactione_purchases_lines.product_id', $productId)
            ->where('t.type', 'purchases')
            ->where('t.establishment_id', $establishmentId)
            ->whereBetween('t.transaction_date', [$from, $to])
            ->sum('transactione_purchases_lines.qyt');

        $salesReturn = TransactionePurchasesLine::join('transactions as t', 'transactione_purchases_lines.transaction_id', '=', 't.id')
            ->where('transactione_purchases_lines.product_id', $productId)
            ->where('t.type', 'sell-return')
            ->where('t.establishment_id', $establishmentId)
            ->whereBetween('t.transaction_date', [$from, $to])
            ->sum('transactione_purchases_lines.qyt');

        $transferIn = TransactionePurchasesLine::join('transactions as t', 'transactione_purchases_lines.transaction_id', '=', 't.id')
            ->where('transactione_purchases_lines.product_id', $productId)
            ->where('t.type', 'TRANSFER')
            ->where('t.establishment_id', $establishmentId)
            ->whereNotNull('t.parent_id')
            ->whereBetween('t.transaction_date', [$from, $to])
            ->sum('transactione_purchases_lines.qyt');

        $totalIn = $purchases + $salesReturn + $transferIn;

        $sales = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->where('transaction_sell_lines.product_id', $productId)
            ->where('t.type', 'sell')
            ->where('t.establishment_id', $establishmentId)
            ->whereBetween('t.transaction_date', [$from, $to])
            ->sum('transaction_sell_lines.qyt');

        $damaged = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->where('transaction_sell_lines.product_id', $productId)
            ->where('t.type', 'damaged')
            ->where('t.establishment_id', $establishmentId)
            ->whereBetween('t.transaction_date', [$from, $to])
            ->sum('transaction_sell_lines.qyt');

        $purchaseReturn = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->where('transaction_sell_lines.product_id', $productId)
            ->where('t.type', 'purchases-return')
            ->where('t.establishment_id', $establishmentId)
            ->whereBetween('t.transaction_date', [$from, $to])
            ->sum('transaction_sell_lines.qyt');

        $transferOut = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->where('transaction_sell_lines.product_id', $productId)
            ->where('t.type', 'TRANSFER')
            ->whereNull('t.parent_id')
            ->where('t.establishment_id', $establishmentId)
            ->whereBetween('t.transaction_date', [$from, $to])
            ->sum('transaction_sell_lines.qyt');

        $totalOut = $sales + $damaged + $purchaseReturn + $transferOut;

        $currentStock = $totalIn - $totalOut;

        $movements = [];

        $allPurchases = TransactionePurchasesLine::with('transaction')->where('product_id', $productId)
            ->whereHas('transaction', fn ($q) => $q->where('establishment_id', $establishmentId)->whereBetween('transaction_date', [$from, $to]))
            ->get();

        $stockQty = 0;
        foreach ($allPurchases as $p) {
            $qty = in_array($p->transaction->type, ['sell-return', 'purchases-return']) ? -$p->qyt : $p->qyt;
            $stockQty += $qty;
            $movements[] = [
                'type' => ucfirst($p->transaction->type),
                'change_qty' => $qty,
                'new_qty' => $stockQty,
                'transaction_date' => $p->transaction->transaction_date,
                'ref_no' => $p->transaction->ref_no,
                'entity' => $p->transaction->client?->name ?? '--',
            ];
        }

        $allSales = TransactionSellLine::with('transaction')->where('product_id', $productId)
            ->whereHas('transaction', fn ($q) => $q->where('establishment_id', $establishmentId)->whereBetween('transaction_date', [$from, $to]))
            ->get();

        foreach ($allSales as $s) {
            $qty = in_array($s->transaction->type, ['sell', 'damaged', 'TRANSFER']) ? -$s->qyt : $s->qyt;
            $stockQty += $qty;
            $movements[] = [
                'type' => ucfirst($s->transaction->type),
                'change_qty' => $qty,
                'new_qty' => $stockQty,
                'transaction_date' => $s->transaction->transaction_date,
                'ref_no' => $s->transaction->ref_no,
                'entity' => $s->transaction->client?->name ?? '--',
            ];
        }

        usort($movements, fn ($a, $b) => strtotime($b['transaction_date']) <=> strtotime($a['transaction_date']));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'currentProduct' => $currentProduct,
                'currentStock' => $currentStock,
                'totalIn' => $totalIn,
                'totalOut' => $totalOut,
                'purchases' => $purchases,
                'salesReturn' => $salesReturn,
                'transferIn' => $transferIn,
                'sales' => $sales,
                'damaged' => $damaged,
                'purchaseReturn' => $purchaseReturn,
                'transferOut' => $transferOut,
                'movements' => $movements,
            ]);
        }

        return view('report::sales.productStockReport', compact(
            'currentProduct',
            'currentStock',
            'totalIn',
            'totalOut',
            'purchases',
            'salesReturn',
            'transferIn',
            'sales',
            'damaged',
            'purchaseReturn',
            'transferOut',
            'movements',
            'previousProduct',
            'nextProduct'
        ));
    }

    public function getRegisterReport(Request $request)
    {
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $user_id = $request->input('user_id');

        $registers = $this->registerReport($start_date, $end_date, $user_id);

        if ($request->ajax()) {
            return Datatables::of($registers)
                ->editColumn('total_card_payment', function ($row) {
                    return '<span data-orig-value="'.$row['total_card_payment'].'">'.$row['total_card_payment'].'</span>';
                })
                ->editColumn('total_cheque_payment', function ($row) {
                    return '<span data-orig-value="'.$row['total_cheque_payment'].'">'.$row['total_cheque_payment'].'</span>';
                })
                ->editColumn('total_cash_payment', function ($row) {
                    return '<span data-orig-value="'.$row['total_cash_payment'].'">'.$row['total_cash_payment'].'</span>';
                })
                ->editColumn('total_bank_transfer_payment', function ($row) {
                    return '<span data-orig-value="'.$row['total_bank_transfer_payment'].'">'.$row['total_bank_transfer_payment'].'</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return $row['created_at'] ?? '';
                })
                ->addColumn('total', function ($row) {
                    $total = $row['total_cash_payment'] + $row['total_cheque_payment'] + $row['total_card_payment'] + $row['total_bank_transfer_payment'];

                    return '<span data-orig-value="'.$total.'">'.$total.'</span>';
                })
                ->addColumn('action', function ($row) {
                    // return '';
                    return '<a type="button" href="'.action([SalesReportController::class, 'show'], [$row['id']]).'" class="btn btn-info " >عرض</a>';
                })
                ->rawColumns(['total_card_payment', 'total_cheque_payment', 'total_cash_payment', 'total_bank_transfer_payment', 'total', 'action'])
                ->make(true);
        }

        $users = Employee::pluck('name', 'id');
        $payment_types = [];

        return view('report::report.register_report', compact('users', 'payment_types'));
    }

    public function registerReport($start_date = null, $end_date = null, $user_id = null)
    {
        $registers = CashRegister::leftjoin(
            'cash_register_transactions as ct',
            'ct.cash_register_id',
            '=',
            'cash_registers.id'
        )->join(
            'emp_employees as u',
            'u.id',
            '=',
            'cash_registers.user_id'
        )->leftJoin(
            'est_establishments as bl',
            'bl.id',
            '=',
            'cash_registers.establishment_id'
        )->select(
            'cash_registers.id',
            'cash_registers.user_id',
            'cash_registers.establishment_id',
            'cash_registers.status',
            'cash_registers.created_at',

            DB::raw("MIN(CONCAT(
                COALESCE(u.name, ''),
                ' - ',
                COALESCE(u.name_en, '')

            )) as user_name"),

            DB::raw('MIN(bl.name) as location_name'),

            DB::raw("SUM(IF(pay_method='cash', IF(transaction_type='sell', amount, 0), 0)) as total_cash_payment"),
            DB::raw("SUM(IF(pay_method='cheque', IF(transaction_type='sell', amount, 0), 0)) as total_cheque_payment"),
            DB::raw("SUM(IF(pay_method='card', IF(transaction_type='sell', amount, 0), 0)) as total_card_payment"),
            DB::raw("SUM(IF(pay_method='bank_transfer', IF(transaction_type='sell', amount, 0), 0)) as total_bank_transfer_payment")
        )
            ->groupBy([
                'cash_registers.id',
                'cash_registers.user_id',
                'cash_registers.establishment_id',
                'cash_registers.status',
                'cash_registers.created_at',
            ]);

        if (! empty(request()->input('register_user_id'))) {
            $registers->where('cash_registers.user_id', request()->input('register_user_id'));
        }
        if (! empty(request()->input('register_status'))) {
            $registers->where('cash_registers.status', request()->input('register_status'));
        }

        if (! empty($start_date) && ! empty($end_date)) {
            $registers->whereDate('cash_registers.created_at', '>=', $start_date)
                ->whereDate('cash_registers.created_at', '<=', $end_date);
        }

        return $registers;
    }

    public function show($id)
    {

        $register_details = $this->getRegisterDetails($id);
        $user_id = $register_details->user_id;
        $open_time = $register_details['open_time'];
        $close_time = ! empty($register_details['closed_at']) ? $register_details['closed_at'] : Carbon::now()->toDateTimeString();
        $details = $this->getRegisterTransactionDetails($user_id, $open_time, $close_time);

        $payment_types = PaymentMethod::all();

        return view('report::report.register_details')
            ->with(compact('register_details', 'payment_types', 'details', 'close_time'));
    }

    public function getRegisterDetails($register_id = null)
    {
        $query = CashRegister::leftjoin(
            'cash_register_transactions as ct',
            'ct.cash_register_id',
            '=',
            'cash_registers.id'
        )->join(
            'emp_employees as u',
            'u.id',
            '=',
            'cash_registers.user_id'
        )->leftJoin(
            'est_establishments as bl',
            'bl.id',
            '=',
            'cash_registers.establishment_id'
        );
        if (empty($register_id)) {
            $user_id = Auth::user()->id;
            $query->where('user_id', $user_id)
                ->where('cash_registers.status', 'open');
        } else {
            $query->where('cash_registers.id', $register_id);
        }

        $register_details = $query->select(
            'cash_registers.id',
            'cash_registers.created_at as open_time',
            'cash_registers.closed_at as closed_at',
            'cash_registers.user_id',
            'cash_registers.closing_note',
            'cash_registers.establishment_id',

            DB::raw("SUM(IF(transaction_type='initial', amount, 0)) as cash_in_hand"),
            DB::raw("SUM(IF(transaction_type='sell', amount, IF(transaction_type='refund', -1 * amount, 0))) as total_sale"),
            DB::raw("SUM(IF(transaction_type='purchases', IF(transaction_type='refund', -1 * amount, amount), 0)) as total_expense"),
            DB::raw("SUM(IF(pay_method='cash', IF(transaction_type='sell', amount, 0), 0)) as total_cash"),
            DB::raw("SUM(IF(pay_method='cash', IF(transaction_type='purchases', amount, 0), 0)) as total_cash_expense"),
            DB::raw("SUM(IF(pay_method='cheque', IF(transaction_type='sell', amount, 0), 0)) as total_cheque"),
            DB::raw("SUM(IF(pay_method='cheque', IF(transaction_type='purchases', amount, 0), 0)) as total_cheque_expense"),
            DB::raw("SUM(IF(pay_method='card', IF(transaction_type='sell', amount, 0), 0)) as total_card"),
            DB::raw("SUM(IF(pay_method='card', IF(transaction_type='purchases', amount, 0), 0)) as total_card_expense"),
            DB::raw("SUM(IF(pay_method='bank_transfer', IF(transaction_type='sell', amount, 0), 0)) as total_bank_transfer"),
            DB::raw("SUM(IF(pay_method='bank_transfer', IF(transaction_type='purchases', amount, 0), 0)) as total_bank_transfer_expense"),
            DB::raw("SUM(IF(pay_method='advance', IF(transaction_type='sell', amount, 0), 0)) as total_advance"),
            DB::raw("SUM(IF(pay_method='cheque', 1, 0)) as total_cheques"),
            DB::raw("SUM(IF(pay_method='card', 1, 0)) as total_card_slips"),
            DB::raw("CONCAT(COALESCE(u.name, ''), ' - ', COALESCE(u.name_en, '')) as user_name"),
            'u.email',
            'bl.name as location_name'
        )
            ->groupBy('cash_registers.id', 'cash_registers.created_at', 'cash_registers.closed_at', 'cash_registers.user_id', 'cash_registers.closing_note', 'cash_registers.establishment_id', 'u.name', 'u.name_en', 'u.email', 'bl.name')
            ->first();

        return $register_details;
    }

    public function getRegisterTransactionDetails($user_id, $open_time, $close_time)
    {
        $product_details = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('product_products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            ->where('t.created_by', $user_id)
            ->whereBetween('t.created_at', [$open_time, $close_time])
            ->where('t.type', 'sell')
            ->where('t.status', 'approved')
            ->select(
                'p.id',
                'p.name_ar as product_name_ar',
                'p.name_en as product_name_en',
                DB::raw('SUM(transaction_sell_lines.qyt) as total_quantity'),
                DB::raw('SUM(transaction_sell_lines.unit_price_inc_tax * transaction_sell_lines.qyt) as total_amount')
            )
            ->groupBy('p.id', 'p.name_ar', 'p.name_en')
            ->get();

        $transaction_details = Transaction::where('created_by', $user_id)
            ->whereBetween('created_at', [$open_time, $close_time])
            ->where('type', 'sell')
            ->where('status', 'approved')
            ->select(
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(IF(discount_type = "percent", total_before_tax*discount_amount/100, discount_amount)) as total_discount'),
                DB::raw('SUM(final_total) as total_sales'),
            )
            ->first();

        return [
            'product_details' => $product_details,
            'transaction_details' => $transaction_details,
        ];
    }

    /**
     * Aggregated footer row for the sales comparison DataTable (full filtered dataset, not current page).
     *
     * @param  \Illuminate\Support\Collection<int, object>  $merged
     * @return array<string, string>
     */
    private function computeSalesComparisonFooterTotals(Collection $merged): array
    {
        $label = __('report::general.table_footer_total');
        $dash = '—';
        $empty = [
            'product_name' => $label,
            'category' => '',
            'subcategory' => '',
            'establishment_name' => '',
            'SKU' => '',
            'customer' => '',
            'qty_period_a' => $dash,
            'avg_unit_price_period_a' => $dash,
            'discount_period_a' => $dash,
            'tax_period_a' => $dash,
            'subtotal_period_a' => $dash,
            'lines_period_a' => $dash,
            'qty_period_b' => $dash,
            'avg_unit_price_period_b' => $dash,
            'discount_period_b' => $dash,
            'tax_period_b' => $dash,
            'subtotal_period_b' => $dash,
            'lines_period_b' => $dash,
            'qty_difference' => $dash,
            'qty_change_percent' => $dash,
            'subtotal_difference' => $dash,
            'subtotal_change_percent' => $dash,
            'discount_difference' => $dash,
            'tax_difference' => $dash,
            'lines_difference' => $dash,
        ];

        if ($merged->isEmpty()) {
            return $empty;
        }

        $sumQtyA = (float) $merged->sum(fn ($r) => (float) ($r->qty_period_a ?? 0));
        $sumQtyB = (float) $merged->sum(fn ($r) => (float) ($r->qty_period_b ?? 0));
        $sumDiscA = (float) $merged->sum(fn ($r) => (float) ($r->discount_period_a ?? 0));
        $sumDiscB = (float) $merged->sum(fn ($r) => (float) ($r->discount_period_b ?? 0));
        $sumTaxA = (float) $merged->sum(fn ($r) => (float) ($r->tax_period_a ?? 0));
        $sumTaxB = (float) $merged->sum(fn ($r) => (float) ($r->tax_period_b ?? 0));
        $sumSubA = (float) $merged->sum(fn ($r) => (float) ($r->subtotal_period_a ?? 0));
        $sumSubB = (float) $merged->sum(fn ($r) => (float) ($r->subtotal_period_b ?? 0));
        $sumLinesA = (int) $merged->sum(fn ($r) => (int) ($r->lines_period_a ?? 0));
        $sumLinesB = (int) $merged->sum(fn ($r) => (int) ($r->lines_period_b ?? 0));

        $avgA = $sumQtyA > 0 ? $sumSubA / $sumQtyA : null;
        $avgB = $sumQtyB > 0 ? $sumSubB / $sumQtyB : null;

        $fmt = static fn ($v) => number_format((float) $v, 2);
        $fmtQty = static fn ($v) => number_format((float) $v, 3);
        $fmtPct = static function ($v) {
            if ($v === null) {
                return '—';
            }

            return number_format($v, 2).'%';
        };
        $fmtAvg = static function ($v) {
            if ($v === null) {
                return '—';
            }

            return number_format((float) $v, 4);
        };

        return [
            'product_name' => $label,
            'category' => '',
            'subcategory' => '',
            'establishment_name' => '',
            'SKU' => '',
            'customer' => '',
            'qty_period_a' => $fmtQty($sumQtyA),
            'avg_unit_price_period_a' => $fmtAvg($avgA),
            'discount_period_a' => $fmt($sumDiscA),
            'tax_period_a' => $fmt($sumTaxA),
            'subtotal_period_a' => $fmt($sumSubA),
            'lines_period_a' => (string) $sumLinesA,
            'qty_period_b' => $fmtQty($sumQtyB),
            'avg_unit_price_period_b' => $fmtAvg($avgB),
            'discount_period_b' => $fmt($sumDiscB),
            'tax_period_b' => $fmt($sumTaxB),
            'subtotal_period_b' => $fmt($sumSubB),
            'lines_period_b' => (string) $sumLinesB,
            'qty_difference' => $fmtQty($sumQtyB - $sumQtyA),
            'qty_change_percent' => $fmtPct(ReportTransactionsUtile::computePercentChange($sumQtyA, $sumQtyB)),
            'subtotal_difference' => $fmt($sumSubB - $sumSubA),
            'subtotal_change_percent' => $fmtPct(ReportTransactionsUtile::computePercentChange($sumSubA, $sumSubB)),
            'discount_difference' => $fmt($sumDiscB - $sumDiscA),
            'tax_difference' => $fmt($sumTaxB - $sumTaxA),
            'lines_difference' => (string) ($sumLinesB - $sumLinesA),
        ];
    }

    private function buildSalesComparisonMergedCollection(Request $request): ?Collection
    {
        $periodA = SalesComparisonPeriodResolver::resolve(
            $request->input('period_a_preset'),
            $request->input('period_a_range')
        );
        $periodB = SalesComparisonPeriodResolver::resolve(
            $request->input('period_b_preset'),
            $request->input('period_b_range')
        );

        if (! $periodA || ! $periodB) {
            return null;
        }

        [$aFrom, $aTo] = $periodA;
        [$bFrom, $bTo] = $periodB;

        $aggA = $this->aggregateSalesComparisonPeriod($request, $aFrom, $aTo);
        $aggB = $this->aggregateSalesComparisonPeriod($request, $bFrom, $bTo);

        $allKeys = $aggA->keys()->merge($aggB->keys())->unique()->values();
        $merged = collect();

        foreach ($allKeys as $key) {
            $rowA = $aggA->get($key);
            $rowB = $aggB->get($key);
            $base = $rowA ?? $rowB;

            $qtyA = (float) ($rowA->qty ?? 0);
            $qtyB = (float) ($rowB->qty ?? 0);
            $discA = (float) ($rowA->discount ?? 0);
            $discB = (float) ($rowB->discount ?? 0);
            $taxA = (float) ($rowA->tax ?? 0);
            $taxB = (float) ($rowB->tax ?? 0);
            $subA = (float) ($rowA->subtotal ?? 0);
            $subB = (float) ($rowB->subtotal ?? 0);
            $linesA = (int) ($rowA->line_count ?? 0);
            $linesB = (int) ($rowB->line_count ?? 0);

            $avgA = $qtyA > 0 ? $subA / $qtyA : null;
            $avgB = $qtyB > 0 ? $subB / $qtyB : null;

            $merged->push((object) [
                'product_name' => app()->getLocale() === 'ar' ? $base->product_name_ar : $base->product_name_en,
                'category' => $base->category ?? '--',
                'subcategory' => $base->subcategory ?? '--',
                'establishment_name' => $base->establishment_name ?? '--',
                'SKU' => $base->sku ?? '--',
                'customer' => $base->customer ?? '--',
                'qty_period_a' => $qtyA,
                'avg_unit_price_period_a' => $avgA,
                'discount_period_a' => $discA,
                'tax_period_a' => $taxA,
                'subtotal_period_a' => $subA,
                'lines_period_a' => $linesA,
                'qty_period_b' => $qtyB,
                'avg_unit_price_period_b' => $avgB,
                'discount_period_b' => $discB,
                'tax_period_b' => $taxB,
                'subtotal_period_b' => $subB,
                'lines_period_b' => $linesB,
                'qty_difference' => $qtyB - $qtyA,
                'qty_change_percent' => ReportTransactionsUtile::computePercentChange($qtyA, $qtyB),
                'subtotal_difference' => $subB - $subA,
                'subtotal_change_percent' => ReportTransactionsUtile::computePercentChange($subA, $subB),
                'discount_difference' => $discB - $discA,
                'tax_difference' => $taxB - $taxA,
                'lines_difference' => $linesB - $linesA,
            ]);
        }

        return $merged;
    }

    /**
     * @return array{title: string, generated_at: string, period_a_line: string, period_b_line: string, filters: string, row_count: int}
     */
    private function salesComparisonExportMeta(Request $request, Collection $merged): array
    {
        $periodA = SalesComparisonPeriodResolver::resolve(
            $request->input('period_a_preset'),
            $request->input('period_a_range')
        );
        $periodB = SalesComparisonPeriodResolver::resolve(
            $request->input('period_b_preset'),
            $request->input('period_b_range')
        );
        [$aFrom, $aTo] = $periodA;
        [$bFrom, $bTo] = $periodB;

        $presetA = $request->input('period_a_preset') ?: SalesComparisonPeriodResolver::PRESET_CUSTOM;
        if (! in_array($presetA, SalesComparisonPeriodResolver::PRESETS, true)) {
            $presetA = SalesComparisonPeriodResolver::PRESET_CUSTOM;
        }
        $presetB = $request->input('period_b_preset') ?: SalesComparisonPeriodResolver::PRESET_CUSTOM;
        if (! in_array($presetB, SalesComparisonPeriodResolver::PRESETS, true)) {
            $presetB = SalesComparisonPeriodResolver::PRESET_CUSTOM;
        }

        $labelA = __('report::general.preset_'.$presetA);
        $labelB = __('report::general.preset_'.$presetB);

        return [
            'title' => __('menuItemLang.sales-comparison-report'),
            'generated_at' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            'period_a_line' => $labelA.' — '.$aFrom.' / '.$aTo,
            'period_b_line' => $labelB.' — '.$bFrom.' / '.$bTo,
            'filters' => $this->salesComparisonFilterSummary($request),
            'row_count' => $merged->count(),
        ];
    }

    private function salesComparisonFilterSummary(Request $request): string
    {
        $lines = $this->sellLineReportFilterSummaryLines($request);

        return $lines === [] ? __('report::general.export_no_filters') : implode("\n", $lines);
    }

    private function salesComparisonPaymentMethodLabel(string $method): string
    {
        $key = strtolower(trim($method));
        if ($key === '') {
            return '--';
        }

        $ar = [
            'cash' => 'نقدي',
            'card' => 'بطاقة',
            'bank_transfer' => 'تحويل بنكي',
            'bank' => 'بنك',
            'cheque' => 'شيك',
            'check' => 'شيك',
            'credit' => 'آجل',
            'due' => 'آجل',
            'wallet' => 'محفظة',
            'advance' => 'سلفة',
        ];
        $en = [
            'cash' => 'Cash',
            'card' => 'Card',
            'bank_transfer' => 'Bank transfer',
            'bank' => 'Bank',
            'cheque' => 'Cheque',
            'check' => 'Check',
            'credit' => 'Credit',
            'due' => 'Due',
            'wallet' => 'Wallet',
            'advance' => 'Advance',
        ];

        if (app()->getLocale() === 'ar') {
            return $ar[$key] ?? $method;
        }

        return $en[$key] ?? ucfirst(str_replace('_', ' ', $method));
    }

    /**
     * Compare approved sell lines aggregated by weekday (Sun–Sat) for two periods — same filters as sales comparison.
     */
    public function weekdaySalesReport(Request $request)
    {
        if ($request->ajax() || $request->has('draw')) {
            $scope = $this->normalizeWeekdayReportScope($request);
            $viewMode = $this->normalizeWeekdayReportViewMode($request);
            $merged = match ($viewMode) {
                'by_day' => $this->buildWeekdaySalesByDayMergedCollection($request),
                'by_date' => $this->buildWeekdaySalesByDateMergedCollection($request),
                'by_date_product' => $this->buildWeekdaySalesByDateProductMergedCollection($request),
                default => $this->buildWeekdaySalesMergedCollection($request),
            };
            $kpi = $this->buildWeekdaySalesKpiSummary($merged ?? collect(), $request);
            $wsrTableMode = in_array($scope, ['single_this_month', 'single_last_month'], true) ? 'single' : 'full';
            $extra = [
                'wsr_table_mode' => $wsrTableMode,
                'wsr_view_mode' => $viewMode,
                'wsr_kpi' => $kpi,
            ];

            if ($merged === null) {
                return ReportTransactionsUtile::getSalesComparisonReport(
                    collect(),
                    $this->computeSalesComparisonFooterTotals(collect()),
                    $extra
                );
            }

            $footer = $this->computeSalesComparisonFooterTotals($merged);

            return ReportTransactionsUtile::getSalesComparisonReport($merged, $footer, $extra);
        }

        return view('report::sales.weekday_sales_report');
    }

    public function weekdaySalesExportExcel(Request $request)
    {
        $viewMode = $this->normalizeWeekdayReportViewMode($request);
        $merged = match ($viewMode) {
            'by_day' => $this->buildWeekdaySalesByDayMergedCollection($request),
            'by_date' => $this->buildWeekdaySalesByDateMergedCollection($request),
            'by_date_product' => $this->buildWeekdaySalesByDateProductMergedCollection($request),
            default => $this->buildWeekdaySalesMergedCollection($request),
        };
        if ($merged === null) {
            return response()->json(['message' => __('report::general.export_invalid_periods')], 422);
        }

        $meta = $this->weekdaySalesExportMeta($request, $merged);
        $meta['wsr_view_mode'] = $viewMode;
        $filename = 'weekday-sales-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new SalesComparisonExcelExport($merged, $meta), $filename);
    }

    public function weekdaySalesExportPdf(Request $request)
    {
        $viewMode = $this->normalizeWeekdayReportViewMode($request);
        $merged = match ($viewMode) {
            'by_day' => $this->buildWeekdaySalesByDayMergedCollection($request),
            'by_date' => $this->buildWeekdaySalesByDateMergedCollection($request),
            'by_date_product' => $this->buildWeekdaySalesByDateProductMergedCollection($request),
            default => $this->buildWeekdaySalesMergedCollection($request),
        };
        if ($merged === null) {
            return response()->json(['message' => __('report::general.export_invalid_periods')], 422);
        }

        $meta = $this->weekdaySalesExportMeta($request, $merged);
        $meta['wsr_view_mode'] = $viewMode;
        $rows = $merged->map(fn ($r) => $this->mapSalesComparisonRowForPdf($r))->all();

        $html = view('report::sales.sales_comparison_export_pdf', [
            'meta' => $meta,
            'rows' => $rows,
        ])->render();

        if (! is_dir(storage_path('temp/mpdf'))) {
            @mkdir(storage_path('temp/mpdf'), 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 6,
            'margin_right' => 6,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => storage_path('temp/mpdf'),
        ]);
        if (app()->getLocale() === 'ar') {
            $mpdf->SetDirectionality('rtl');
        }
        $mpdf->WriteHTML($html);
        $filename = 'weekday-sales-'.now()->format('Y-m-d-His').'.pdf';
        $binary = $mpdf->Output('', Destination::STRING_RETURN);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return array<string, string|int>
     */
    private function weekdaySalesExportMeta(Request $request, Collection $merged): array
    {
        $scope = $this->normalizeWeekdayReportScope($request);
        $overrides = $this->weekdayReportSyntheticPeriodInputs($scope);
        $forMeta = $overrides === null
            ? $request
            : $request->duplicate(null, array_merge($request->all(), $overrides));

        $meta = $this->salesComparisonExportMeta($forMeta, $merged);
        $meta['title'] = __('menuItemLang.weekday-sales-report');
        $meta['weekdays_line'] = $this->weekdayExportWeekdaysSummary($request);

        $singleWindow = in_array($scope, ['single_this_month', 'single_last_month'], true);
        $meta['wsr_export_single_window'] = $singleWindow;

        if ($singleWindow) {
            $kpi = $this->buildWeekdaySalesKpiSummary($merged, $request);
            $meta['wsr_kpi_pdf'] = [
                'qty' => number_format($kpi['sum_qty_a'], 3),
                'revenue' => number_format($kpi['sum_subtotal_a'], 2),
                'lines' => (string) $kpi['sum_lines_a'],
            ];
        }

        return $meta;
    }

    private function normalizeWeekdayReportViewMode(Request $request): string
    {
        if (! $request->has('wsr_view')) {
            return 'by_product';
        }

        $v = (string) $request->input('wsr_view');
        $allowed = ['by_product', 'by_day', 'by_date', 'by_date_product'];

        return in_array($v, $allowed, true) ? $v : 'by_product';
    }

    private function normalizeWeekdayReportScope(Request $request): string
    {
        if (! $request->has('weekday_report_scope')) {
            return 'custom_periods';
        }

        $s = (string) $request->input('weekday_report_scope');
        $allowed = ['custom_periods', 'compare_this_vs_last_month', 'single_this_month', 'single_last_month'];

        return in_array($s, $allowed, true) ? $s : 'custom_periods';
    }

    /**
     * @return array<string, string>|null  null when the request already carries the real period fields
     */
    private function weekdayReportSyntheticPeriodInputs(string $scope): ?array
    {
        return match ($scope) {
            'compare_this_vs_last_month' => [
                'period_a_preset' => 'last_month',
                'period_b_preset' => 'this_month',
                'period_a_range' => '',
                'period_b_range' => '',
            ],
            'single_this_month' => [
                'period_a_preset' => 'this_month',
                'period_b_preset' => 'this_month',
                'period_a_range' => '',
                'period_b_range' => '',
            ],
            'single_last_month' => [
                'period_a_preset' => 'last_month',
                'period_b_preset' => 'last_month',
                'period_a_range' => '',
                'period_b_range' => '',
            ],
            default => null,
        };
    }

    /**
     * @return array{0: array{0: string, 1: string}|null, 1: array{0: string, 1: string}|null, 2: string}
     */
    private function resolveWeekdayReportPeriods(Request $request): array
    {
        $scope = $this->normalizeWeekdayReportScope($request);

        if ($scope === 'compare_this_vs_last_month') {
            return [
                SalesComparisonPeriodResolver::resolve('last_month', null),
                SalesComparisonPeriodResolver::resolve('this_month', null),
                $scope,
            ];
        }

        if ($scope === 'single_this_month') {
            $a = SalesComparisonPeriodResolver::resolve('this_month', null);

            return [$a, $a, $scope];
        }

        if ($scope === 'single_last_month') {
            $a = SalesComparisonPeriodResolver::resolve('last_month', null);

            return [$a, $a, $scope];
        }

        $periodA = SalesComparisonPeriodResolver::resolve(
            $request->input('period_a_preset'),
            $request->input('period_a_range')
        );
        $periodB = SalesComparisonPeriodResolver::resolve(
            $request->input('period_b_preset'),
            $request->input('period_b_range')
        );

        return [$periodA, $periodB, $scope];
    }

    /**
     * @return array{a: string, b: string}
     */
    private function weekdayKpiPeriodLabels(Request $request): array
    {
        [$pA, $pB] = $this->resolveWeekdayReportPeriods($request);
        $fmt = static function (?array $p): string {
            if (! $p || ! isset($p[0], $p[1])) {
                return '—';
            }

            return $p[0].' – '.$p[1];
        };

        return [
            'a' => $fmt($pA),
            'b' => $fmt($pB),
        ];
    }

    private function shortenKpiLabel(string $label, int $max = 28): string
    {
        if ($label === '' || $label === '—') {
            return $label;
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($label) <= $max) {
                return $label;
            }

            return mb_substr($label, 0, $max).'…';
        }

        return strlen($label) <= $max ? $label : substr($label, 0, $max).'…';
    }

    /**
     * @return array{
     *     is_single: bool,
     *     period_a_label: string,
     *     period_b_label: string,
     *     chart_label_a: string,
     *     chart_label_b: string,
     *     sum_qty_a: float,
     *     sum_qty_b: float,
     *     sum_subtotal_a: float,
     *     sum_subtotal_b: float,
     *     sum_lines_a: int,
     *     sum_lines_b: int,
     *     qty_change_pct: float|null,
     *     revenue_change_pct: float|null,
     *     delta_qty: float,
     *     delta_revenue: float
     * }
     */
    private function buildWeekdaySalesKpiSummary(Collection $merged, Request $request): array
    {
        $scope = $this->normalizeWeekdayReportScope($request);
        $isSingle = in_array($scope, ['single_this_month', 'single_last_month'], true);
        $labels = $this->weekdayKpiPeriodLabels($request);

        $sumQtyA = $merged->isEmpty() ? 0.0 : (float) $merged->sum(fn ($r) => (float) ($r->qty_period_a ?? 0));
        $sumQtyB = $merged->isEmpty() ? 0.0 : (float) $merged->sum(fn ($r) => (float) ($r->qty_period_b ?? 0));
        $sumSubA = $merged->isEmpty() ? 0.0 : (float) $merged->sum(fn ($r) => (float) ($r->subtotal_period_a ?? 0));
        $sumSubB = $merged->isEmpty() ? 0.0 : (float) $merged->sum(fn ($r) => (float) ($r->subtotal_period_b ?? 0));
        $sumLinesA = $merged->isEmpty() ? 0 : (int) $merged->sum(fn ($r) => (int) ($r->lines_period_a ?? 0));
        $sumLinesB = $merged->isEmpty() ? 0 : (int) $merged->sum(fn ($r) => (int) ($r->lines_period_b ?? 0));

        return [
            'is_single' => $isSingle,
            'period_a_label' => $labels['a'],
            'period_b_label' => $labels['b'],
            'chart_label_a' => $this->shortenKpiLabel($labels['a']),
            'chart_label_b' => $this->shortenKpiLabel($labels['b']),
            'sum_qty_a' => $sumQtyA,
            'sum_qty_b' => $sumQtyB,
            'sum_subtotal_a' => $sumSubA,
            'sum_subtotal_b' => $sumSubB,
            'sum_lines_a' => $sumLinesA,
            'sum_lines_b' => $sumLinesB,
            'qty_change_pct' => ReportTransactionsUtile::computePercentChange($sumQtyA, $sumQtyB),
            'revenue_change_pct' => ReportTransactionsUtile::computePercentChange($sumSubA, $sumSubB),
            'delta_qty' => $sumQtyB - $sumQtyA,
            'delta_revenue' => $sumSubB - $sumSubA,
        ];
    }

    private function weekdayExportWeekdaysSummary(Request $request): string
    {
        $phpDows = $this->resolveWeekdayPhpDaysForReport($request);
        if (count($phpDows) === 7) {
            return __('report::general.weekday_export_all_days');
        }

        $sep = app()->getLocale() === 'ar' ? '، ' : ', ';

        return collect($phpDows)
            ->map(fn (int $d) => __('report::general.weekday_long_'.$d))
            ->implode($sep);
    }

    /**
     * PHP weekday indices 0–6 (Sun–Sat), unique and sorted; empty or invalid request values => all seven days.
     *
     * Accepts an array (e.g. weekday[]=0&weekday[]=2) or a comma-separated string (used with jQuery
     * traditional serialization where duplicate keys would otherwise collapse to the last value only).
     *
     * @return array<int, int>
     */
    private function resolveWeekdayPhpDaysForReport(Request $request): array
    {
        $rawWeekday = $request->input('weekday');
        if ($rawWeekday === null || $rawWeekday === '') {
            $rawWeekday = [];
        } elseif (is_string($rawWeekday)) {
            $rawWeekday = trim($rawWeekday);
            $rawWeekday = $rawWeekday === ''
                ? []
                : array_values(array_filter(array_map('trim', explode(',', $rawWeekday)), fn ($s) => $s !== ''));
        } elseif (! is_array($rawWeekday)) {
            $rawWeekday = [$rawWeekday];
        }

        $phpDows = collect($rawWeekday)
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v >= 0 && $v <= 6)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($phpDows === []) {
            return range(0, 6);
        }

        return $phpDows;
    }

    /**
     * Aggregate approved sell lines by weekday within a single period.
     *
     * @param  list<int>  $mysqlWeekdayOneToSeven
     * @return \Illuminate\Support\Collection<int, object> keyed by mysql_dow (1..7)
     */
    private function aggregateWeekdayTotalsPeriod(
        Request $request,
        string $from,
        string $to,
        array $mysqlWeekdayOneToSeven
    ): \Illuminate\Support\Collection {
        $q = $this->buildSalesComparisonLinesQuery($request, false)
            ->whereDate('t.transaction_date', '>=', $from)
            ->whereDate('t.transaction_date', '<=', $to);

        $dows = array_values(array_unique(array_map('intval', $mysqlWeekdayOneToSeven)));
        sort($dows);
        $inList = implode(',', $dows);

        $q->whereRaw('DAYOFWEEK(DATE(t.transaction_date)) IN ('.$inList.')');

        $rows = $q
            ->selectRaw('DAYOFWEEK(DATE(t.transaction_date)) as mysql_dow')
            ->selectRaw('SUM(tsl.qyt) as qty')
            ->selectRaw('SUM(tsl.discount_amount) as discount')
            ->selectRaw('SUM(tsl.tax_value) as tax')
            ->selectRaw('SUM(tsl.qyt * tsl.unit_price_inc_tax) as subtotal')
            ->selectRaw('COUNT(*) as line_count')
            ->groupBy(DB::raw('DAYOFWEEK(DATE(t.transaction_date))'))
            ->orderBy('mysql_dow')
            ->get();

        return $rows->keyBy(fn ($r) => (int) $r->mysql_dow);
    }

    /**
     * Aggregate approved sell lines by calendar date within a single period.
     *
     * @param  list<int>  $mysqlWeekdayOneToSeven
     * @return \Illuminate\Support\Collection<int, object> keyed by date string (Y-m-d)
     */
    private function aggregateDateTotalsPeriod(
        Request $request,
        string $from,
        string $to,
        array $mysqlWeekdayOneToSeven
    ): \Illuminate\Support\Collection {
        $q = $this->buildSalesComparisonLinesQuery($request, false)
            ->whereDate('t.transaction_date', '>=', $from)
            ->whereDate('t.transaction_date', '<=', $to);

        $dows = array_values(array_unique(array_map('intval', $mysqlWeekdayOneToSeven)));
        sort($dows);
        $inList = implode(',', $dows);

        $q->whereRaw('DAYOFWEEK(DATE(t.transaction_date)) IN ('.$inList.')');

        $rows = $q
            ->selectRaw('DATE(t.transaction_date) as tx_date')
            ->selectRaw('SUM(tsl.qyt) as qty')
            ->selectRaw('SUM(tsl.discount_amount) as discount')
            ->selectRaw('SUM(tsl.tax_value) as tax')
            ->selectRaw('SUM(tsl.qyt * tsl.unit_price_inc_tax) as subtotal')
            ->selectRaw('COUNT(*) as line_count')
            ->groupBy(DB::raw('DATE(t.transaction_date)'))
            ->orderBy('tx_date')
            ->get();

        return $rows->keyBy(fn ($r) => (string) $r->tx_date);
    }

    /**
     * Build rows grouped by weekday (Sun–Sat). Uses the same filters and weekday checkboxes,
     * but instead of product+branch rows it returns one row per weekday.
     *
     * @return \Illuminate\Support\Collection<int, object>|null
     */
    private function buildWeekdaySalesByDayMergedCollection(Request $request): ?Collection
    {
        [$periodA, $periodB] = $this->resolveWeekdayReportPeriods($request);

        if (! $periodA || ! $periodB) {
            return null;
        }

        [$aFrom, $aTo] = $periodA;
        [$bFrom, $bTo] = $periodB;

        $phpDows = $this->resolveWeekdayPhpDaysForReport($request);
        $mysqlDows = array_values(array_unique(array_map(fn (int $p) => $p + 1, $phpDows)));
        sort($mysqlDows);

        $aggA = $this->aggregateWeekdayTotalsPeriod($request, $aFrom, $aTo, $mysqlDows);
        $aggB = $this->aggregateWeekdayTotalsPeriod($request, $bFrom, $bTo, $mysqlDows);

        $merged = collect();

        foreach ($phpDows as $phpDow) {
            $mysqlDow = $phpDow + 1; // 1..7
            $rowA = $aggA->get($mysqlDow);
            $rowB = $aggB->get($mysqlDow);

            $qtyA = (float) ($rowA->qty ?? 0);
            $qtyB = (float) ($rowB->qty ?? 0);
            $discA = (float) ($rowA->discount ?? 0);
            $discB = (float) ($rowB->discount ?? 0);
            $taxA = (float) ($rowA->tax ?? 0);
            $taxB = (float) ($rowB->tax ?? 0);
            $subA = (float) ($rowA->subtotal ?? 0);
            $subB = (float) ($rowB->subtotal ?? 0);
            $linesA = (int) ($rowA->line_count ?? 0);
            $linesB = (int) ($rowB->line_count ?? 0);

            $avgA = $qtyA > 0 ? $subA / $qtyA : null;
            $avgB = $qtyB > 0 ? $subB / $qtyB : null;

            $merged->push((object) [
                // Re-use the existing DataTable schema: show the weekday in the first column.
                'product_name' => __('report::general.weekday_long_'.$phpDow),
                'category' => '',
                'subcategory' => '',
                'establishment_name' => '',
                'SKU' => '',
                'customer' => '',
                'qty_period_a' => $qtyA,
                'avg_unit_price_period_a' => $avgA,
                'discount_period_a' => $discA,
                'tax_period_a' => $taxA,
                'subtotal_period_a' => $subA,
                'lines_period_a' => $linesA,
                'qty_period_b' => $qtyB,
                'avg_unit_price_period_b' => $avgB,
                'discount_period_b' => $discB,
                'tax_period_b' => $taxB,
                'subtotal_period_b' => $subB,
                'lines_period_b' => $linesB,
                'qty_difference' => $qtyB - $qtyA,
                'qty_change_percent' => ReportTransactionsUtile::computePercentChange($qtyA, $qtyB),
                'subtotal_difference' => $subB - $subA,
                'subtotal_change_percent' => ReportTransactionsUtile::computePercentChange($subA, $subB),
                'discount_difference' => $discB - $discA,
                'tax_difference' => $taxB - $taxA,
                'lines_difference' => $linesB - $linesA,
            ]);
        }

        return $merged;
    }

    /**
     * Build rows grouped by calendar date, filtered to selected weekdays.
     *
     * Note: This is primarily useful for single-period scopes (this month only / last month only),
     * where the user wants to see each matching date separately (e.g., every Wednesday in the month).
     *
     * @return \Illuminate\Support\Collection<int, object>|null
     */
    private function buildWeekdaySalesByDateMergedCollection(Request $request): ?Collection
    {
        [$periodA, $periodB, $scope] = $this->resolveWeekdayReportPeriods($request);

        if (! $periodA || ! $periodB) {
            return null;
        }

        // If the user tries by-date in a two-period comparison, fall back to by-day to avoid confusing mismatched dates.
        if (! in_array($scope, ['single_this_month', 'single_last_month'], true)) {
            return $this->buildWeekdaySalesByDayMergedCollection($request);
        }

        [$aFrom, $aTo] = $periodA;

        $phpDows = $this->resolveWeekdayPhpDaysForReport($request);
        $mysqlDows = array_values(array_unique(array_map(fn (int $p) => $p + 1, $phpDows)));
        sort($mysqlDows);

        $aggA = $this->aggregateDateTotalsPeriod($request, $aFrom, $aTo, $mysqlDows);

        $merged = collect();

        foreach ($aggA->keys() as $date) {
            $rowA = $aggA->get($date);

            $qtyA = (float) ($rowA->qty ?? 0);
            $discA = (float) ($rowA->discount ?? 0);
            $taxA = (float) ($rowA->tax ?? 0);
            $subA = (float) ($rowA->subtotal ?? 0);
            $linesA = (int) ($rowA->line_count ?? 0);
            $avgA = $qtyA > 0 ? $subA / $qtyA : null;

            $dateStr = (string) $date;
            try {
                $phpDow = Carbon::parse($dateStr)->dayOfWeek; // 0..6 (Sun..Sat)
                $dayName = __('report::general.weekday_long_'.$phpDow);
                $dateStr = $dateStr.' ('.$dayName.')';
            } catch (\Throwable $e) {
                // keep raw date string
            }

            $merged->push((object) [
                // Re-use the existing DataTable schema: show the date in the first column.
                'product_name' => $dateStr,
                'category' => '',
                'subcategory' => '',
                'establishment_name' => '',
                'SKU' => '',
                'customer' => '',
                'qty_period_a' => $qtyA,
                'avg_unit_price_period_a' => $avgA,
                'discount_period_a' => $discA,
                'tax_period_a' => $taxA,
                'subtotal_period_a' => $subA,
                'lines_period_a' => $linesA,
                // Keep the rest of columns at 0; UI will hide them in single mode anyway.
                'qty_period_b' => 0,
                'avg_unit_price_period_b' => null,
                'discount_period_b' => 0,
                'tax_period_b' => 0,
                'subtotal_period_b' => 0,
                'lines_period_b' => 0,
                'qty_difference' => 0,
                'qty_change_percent' => null,
                'subtotal_difference' => 0,
                'subtotal_change_percent' => null,
                'discount_difference' => 0,
                'tax_difference' => 0,
                'lines_difference' => 0,
            ]);
        }

        return $merged;
    }

    /**
     * Build rows grouped by (date + product + branch), filtered to selected weekdays.
     *
     * This matches the request: when user selects (last month) + (Thursday), show each Thursday date
     * and for each date show how much each product sold (split by branch).
     *
     * @return \Illuminate\Support\Collection<int, object>|null
     */
    private function buildWeekdaySalesByDateProductMergedCollection(Request $request): ?Collection
    {
        [$periodA, $periodB, $scope] = $this->resolveWeekdayReportPeriods($request);

        if (! $periodA || ! $periodB) {
            return null;
        }

        // Only meaningful for single-period views; in compare mode, fallback to by-day to avoid mismatched dates.
        if (! in_array($scope, ['single_this_month', 'single_last_month'], true)) {
            return $this->buildWeekdaySalesByDayMergedCollection($request);
        }

        [$aFrom, $aTo] = $periodA;

        $phpDows = $this->resolveWeekdayPhpDaysForReport($request);
        $mysqlDows = array_values(array_unique(array_map(fn (int $p) => $p + 1, $phpDows)));
        sort($mysqlDows);

        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';

        $q = $this->buildSalesComparisonLinesQuery($request, true)
            ->whereDate('t.transaction_date', '>=', $aFrom)
            ->whereDate('t.transaction_date', '<=', $aTo);

        $inList = implode(',', array_map('intval', $mysqlDows));
        $q->whereRaw('DAYOFWEEK(DATE(t.transaction_date)) IN ('.$inList.')');

        $rows = $q
            ->selectRaw('DATE(t.transaction_date) as tx_date')
            ->selectRaw('tsl.product_id as product_id')
            ->selectRaw('t.establishment_id as establishment_id')
            ->selectRaw('MAX(p.name_ar) as product_name_ar')
            ->selectRaw('MAX(p.name_en) as product_name_en')
            ->selectRaw('MAX(p.SKU) as sku')
            ->selectRaw("MAX(CASE WHEN '{$locale}' = 'ar' THEN cat.name_ar ELSE cat.name_en END) as category")
            ->selectRaw("MAX(CASE WHEN '{$locale}' = 'ar' THEN sub.name_ar ELSE sub.name_en END) as subcategory")
            ->selectRaw("MAX(CASE WHEN '{$locale}' = 'ar' THEN e.name ELSE e.name_en END) as establishment_name")
            ->selectRaw('SUM(tsl.qyt) as qty')
            ->selectRaw('SUM(tsl.discount_amount) as discount')
            ->selectRaw('SUM(tsl.tax_value) as tax')
            ->selectRaw('SUM(tsl.qyt * tsl.unit_price_inc_tax) as subtotal')
            ->selectRaw('COUNT(*) as line_count')
            ->groupBy(DB::raw('DATE(t.transaction_date)'), 'tsl.product_id', 't.establishment_id')
            ->orderBy('tx_date')
            ->orderBy(DB::raw("MAX(CASE WHEN '{$locale}' = 'ar' THEN p.name_ar ELSE p.name_en END)"))
            ->get();

        $merged = collect();

        foreach ($rows as $r) {
            $qtyA = (float) ($r->qty ?? 0);
            $subA = (float) ($r->subtotal ?? 0);
            $avgA = $qtyA > 0 ? $subA / $qtyA : null;

            // Put the date into the "category" column in this view (UI will relabel it to Date).
            // Also include weekday name next to date for clarity: 2026-05-03 (Thursday).
            $dateStr = (string) ($r->tx_date ?? '--');
            try {
                $phpDow = Carbon::parse($dateStr)->dayOfWeek; // 0..6 (Sun..Sat)
                $dayName = __('report::general.weekday_long_'.$phpDow);
                $dateStr = $dateStr.' ('.$dayName.')';
            } catch (\Throwable $e) {
                // keep raw date string
            }

            $merged->push((object) [
                'product_name' => $locale === 'ar' ? ($r->product_name_ar ?? '--') : ($r->product_name_en ?? '--'),
                'category' => $dateStr,
                'subcategory' => '',
                'establishment_name' => $r->establishment_name ?? '--',
                'SKU' => $r->sku ?? '--',
                'customer' => __('report::general.weekday_report_customer_rollup'),
                'qty_period_a' => $qtyA,
                'avg_unit_price_period_a' => $avgA,
                'discount_period_a' => (float) ($r->discount ?? 0),
                'tax_period_a' => (float) ($r->tax ?? 0),
                'subtotal_period_a' => $subA,
                'lines_period_a' => (int) ($r->line_count ?? 0),
                'qty_period_b' => 0,
                'avg_unit_price_period_b' => null,
                'discount_period_b' => 0,
                'tax_period_b' => 0,
                'subtotal_period_b' => 0,
                'lines_period_b' => 0,
                'qty_difference' => 0,
                'qty_change_percent' => null,
                'subtotal_difference' => 0,
                'subtotal_change_percent' => null,
                'discount_difference' => 0,
                'tax_difference' => 0,
                'lines_difference' => 0,
            ]);
        }

        return $merged;
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>|null
     */
    private function buildWeekdaySalesMergedCollection(Request $request): ?Collection
    {
        [$periodA, $periodB] = $this->resolveWeekdayReportPeriods($request);

        if (! $periodA || ! $periodB) {
            return null;
        }

        [$aFrom, $aTo] = $periodA;
        [$bFrom, $bTo] = $periodB;

        $phpDows = $this->resolveWeekdayPhpDaysForReport($request);

        $mysqlDows = array_values(array_unique(array_map(fn (int $p) => $p + 1, $phpDows)));
        sort($mysqlDows);

        $aggA = $this->aggregateSalesComparisonPeriod($request, $aFrom, $aTo, $mysqlDows, false);
        $aggB = $this->aggregateSalesComparisonPeriod($request, $bFrom, $bTo, $mysqlDows, false);

        $allKeys = $aggA->keys()->merge($aggB->keys())->unique()->values();
        $merged = collect();

        foreach ($allKeys as $key) {
            $rowA = $aggA->get($key);
            $rowB = $aggB->get($key);
            $base = $rowA ?? $rowB;

            $qtyA = (float) ($rowA->qty ?? 0);
            $qtyB = (float) ($rowB->qty ?? 0);
            $discA = (float) ($rowA->discount ?? 0);
            $discB = (float) ($rowB->discount ?? 0);
            $taxA = (float) ($rowA->tax ?? 0);
            $taxB = (float) ($rowB->tax ?? 0);
            $subA = (float) ($rowA->subtotal ?? 0);
            $subB = (float) ($rowB->subtotal ?? 0);
            $linesA = (int) ($rowA->line_count ?? 0);
            $linesB = (int) ($rowB->line_count ?? 0);

            $avgA = $qtyA > 0 ? $subA / $qtyA : null;
            $avgB = $qtyB > 0 ? $subB / $qtyB : null;

            $merged->push((object) [
                'product_name' => app()->getLocale() === 'ar' ? $base->product_name_ar : $base->product_name_en,
                'category' => $base->category ?? '--',
                'subcategory' => $base->subcategory ?? '--',
                'establishment_name' => $base->establishment_name ?? '--',
                'SKU' => $base->sku ?? '--',
                'customer' => __('report::general.weekday_report_customer_rollup'),
                'qty_period_a' => $qtyA,
                'avg_unit_price_period_a' => $avgA,
                'discount_period_a' => $discA,
                'tax_period_a' => $taxA,
                'subtotal_period_a' => $subA,
                'lines_period_a' => $linesA,
                'qty_period_b' => $qtyB,
                'avg_unit_price_period_b' => $avgB,
                'discount_period_b' => $discB,
                'tax_period_b' => $taxB,
                'subtotal_period_b' => $subB,
                'lines_period_b' => $linesB,
                'qty_difference' => $qtyB - $qtyA,
                'qty_change_percent' => ReportTransactionsUtile::computePercentChange($qtyA, $qtyB),
                'subtotal_difference' => $subB - $subA,
                'subtotal_change_percent' => ReportTransactionsUtile::computePercentChange($subA, $subB),
                'discount_difference' => $discB - $discA,
                'tax_difference' => $taxB - $taxA,
                'lines_difference' => $linesB - $linesA,
            ]);
        }

        return $merged;
    }

    /**
     * @return array<string, string>
     */
    private function mapSalesComparisonRowForPdf(object $row): array
    {
        $fmt = static fn ($v) => number_format((float) $v, 2);
        $fmtQty = static fn ($v) => number_format((float) $v, 3);
        $fmtPct = static function ($v) {
            if ($v === null) {
                return '—';
            }

            return number_format($v, 2).'%';
        };
        $fmtAvg = static function ($v) {
            if ($v === null) {
                return '—';
            }

            return number_format((float) $v, 4);
        };

        $qtyA = (float) $row->qty_period_a;
        $qtyB = (float) $row->qty_period_b;
        $subA = (float) $row->subtotal_period_a;
        $subB = (float) $row->subtotal_period_b;

        return [
            'product_name' => (string) ($row->product_name ?? '--'),
            'category' => (string) ($row->category ?? '--'),
            'subcategory' => (string) ($row->subcategory ?? '--'),
            'establishment_name' => (string) ($row->establishment_name ?? '--'),
            'SKU' => (string) ($row->SKU ?? '--'),
            'customer' => (string) ($row->customer ?? '--'),
            'qty_period_a' => $fmtQty($qtyA),
            'avg_unit_price_period_a' => $fmtAvg($row->avg_unit_price_period_a),
            'discount_period_a' => $fmt($row->discount_period_a),
            'tax_period_a' => $fmt($row->tax_period_a),
            'subtotal_period_a' => $fmt($subA),
            'lines_period_a' => (string) (int) $row->lines_period_a,
            'qty_period_b' => $fmtQty($qtyB),
            'avg_unit_price_period_b' => $fmtAvg($row->avg_unit_price_period_b),
            'discount_period_b' => $fmt($row->discount_period_b),
            'tax_period_b' => $fmt($row->tax_period_b),
            'subtotal_period_b' => $fmt($subB),
            'lines_period_b' => (string) (int) $row->lines_period_b,
            'qty_difference' => $fmtQty($row->qty_difference),
            'qty_change_percent' => $fmtPct(ReportTransactionsUtile::computePercentChange($qtyA, $qtyB)),
            'subtotal_difference' => $fmt($row->subtotal_difference),
            'subtotal_change_percent' => $fmtPct(ReportTransactionsUtile::computePercentChange($subA, $subB)),
            'discount_difference' => $fmt($row->discount_difference),
            'tax_difference' => $fmt($row->tax_difference),
            'lines_difference' => (string) (int) $row->lines_difference,
        ];
    }
}
