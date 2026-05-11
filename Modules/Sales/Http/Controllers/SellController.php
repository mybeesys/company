<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Models\AccountsRoting;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Accounting\Utils\AutoJournalGuard;
use Modules\Accounting\Utils\PerpetualInventoryAccountResolver;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\ClientsAndSuppliers\utils\ContactUtils;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Actions;
use Modules\General\Models\Country;
use Modules\General\Models\Setting;
use Modules\General\Models\Tax;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;
use Modules\General\Models\TransactionSellLine;
use Modules\General\Utils\ActionUtil;
use Modules\General\Utils\TransactionUtils;
use Modules\Product\Models\Product;
use Modules\Product\Models\RecipeProduct;
use Modules\Sales\Models\Coupon;
use Modules\Sales\Services\ApplyCouponService;
use Modules\Sales\Utils\SalesUtile;
use Mpdf\Mpdf;

// use Illuminate\Support\Facades\Log;

class SellController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function salesDashbord(Request $request)
    {
        $validStatuses = ['approved', 'final'];
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();
        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $periodDays = max(1, $startDate->diffInDays($endDate) + 1);
        $prevStart = $startDate->copy()->subDays($periodDays)->startOfDay();
        $prevEnd = $startDate->copy()->subDay()->endOfDay();

        $paymentsSub = DB::table('transaction_payments')
            ->selectRaw('transaction_id, SUM(IF(is_return = 1, -1 * amount, amount)) as paid_total')
            ->groupBy('transaction_id');

        $salesBase = Transaction::query()
            ->where('type', 'sell')
            ->whereIn('status', $validStatuses);

        $periodSales = (clone $salesBase)->whereBetween('transaction_date', [$startDate, $endDate])->sum('final_total');
        $prevSales = (clone $salesBase)->whereBetween('transaction_date', [$prevStart, $prevEnd])->sum('final_total');
        $salesGrowth = $prevSales > 0 ? round((($periodSales - $prevSales) / $prevSales) * 100, 2) : ($periodSales > 0 ? 100 : 0);
        $periodInvoices = (clone $salesBase)->whereBetween('transaction_date', [$startDate, $endDate])->count();
        $avgInvoice = $periodInvoices > 0 ? $periodSales / $periodInvoices : 0;
        $activeCustomers = (clone $salesBase)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->distinct('contact_id')
            ->count('contact_id');

        $periodSalesQuery = DB::table('transactions as t')
            ->leftJoinSub($paymentsSub, 'tp', fn ($j) => $j->on('t.id', '=', 'tp.transaction_id'))
            ->where('t.type', 'sell')
            ->whereIn('t.status', $validStatuses)
            ->whereBetween('t.transaction_date', [$startDate, $endDate]);
        $overdueAmount = (clone $periodSalesQuery)
            ->whereDate('t.due_date', '<', now()->toDateString())
            ->selectRaw('SUM(GREATEST(t.final_total - COALESCE(tp.paid_total,0),0)) as overdue')
            ->value('overdue') ?? 0;
        $dueAmount = (clone $periodSalesQuery)
            ->selectRaw('SUM(GREATEST(t.final_total - COALESCE(tp.paid_total,0),0)) as due')
            ->value('due') ?? 0;

        $receiptsStats = TransactionPayments::query()
            ->whereHas('transaction', function ($q) use ($validStatuses, $startDate, $endDate) {
                $q->where('type', 'sell')->whereIn('status', $validStatuses)->whereBetween('transaction_date', [$startDate, $endDate]);
            })
            ->selectRaw('COUNT(*) as total_receipts, SUM(amount) as total_collected')
            ->first();

        $couponStats = Coupon::query()
            ->leftJoin('sales_coupons_clients as scc', 'sales_coupons.id', '=', 'scc.coupon_id')
            ->leftJoin('transactions as t', 't.id', '=', 'scc.transaction_id')
            ->whereBetween(DB::raw('COALESCE(t.transaction_date, scc.created_at)'), [$startDate, $endDate])
            ->selectRaw('COUNT(DISTINCT sales_coupons.id) as active_coupons, COUNT(scc.transaction_id) as coupon_usages, SUM(COALESCE(t.discount_amount,0)) as total_coupon_discount')
            ->first();
        $salesReturnStats = Transaction::query()
            ->where('type', 'sell-return')
            ->whereIn('status', $validStatuses)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total_count, SUM(final_total) as total_amount')
            ->first();
        $quotationStats = Transaction::query()
            ->where('type', 'quotation')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total_count, SUM(final_total) as total_amount')
            ->first();
        $favoritesStats = Transaction::query()
            ->whereIn('type', ['sell', 'sell-return', 'quotation'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereHas('favorites', fn ($q) => $q->where('user_id', Auth::id()))
            ->selectRaw('COUNT(*) as total_count, SUM(final_total) as total_amount')
            ->first();

        $months = collect(range(5, 0))
            ->map(fn ($offset) => now()->subMonths($offset)->format('Y-m'))
            ->push(now()->format('Y-m'))
            ->values();
        $salesMonthly = (clone $salesBase)
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month_key, SUM(final_total) as total")
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('month_key')
            ->pluck('total', 'month_key');
        $collectionsMonthly = TransactionPayments::query()
            ->whereHas('transaction', fn ($q) => $q->where('type', 'sell')->whereIn('status', $validStatuses))
            ->selectRaw("DATE_FORMAT(paid_on, '%Y-%m') as month_key, SUM(amount) as total")
            ->whereBetween('paid_on', [$startDate, $endDate])
            ->groupBy('month_key')
            ->pluck('total', 'month_key');
        $monthLabels = [];
        $salesData = [];
        $collectionData = [];
        foreach ($months as $month) {
            $monthLabels[] = Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y');
            $salesData[] = (float) ($salesMonthly[$month] ?? 0);
            $collectionData[] = (float) ($collectionsMonthly[$month] ?? 0);
        }

        $paymentMethods = TransactionPayments::query()
            ->whereHas('transaction', fn ($q) => $q->where('type', 'sell')->whereIn('status', $validStatuses))
            ->whereBetween('paid_on', [$startDate, $endDate])
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        $topProducts = TransactionSellLine::query()
            ->join('transactions as t', 't.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('product_products as p', 'p.id', '=', 'transaction_sell_lines.product_id')
            ->where('t.type', 'sell')
            ->whereIn('t.status', $validStatuses)
            ->where('transaction_sell_lines.is_show', 1)
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->groupBy('transaction_sell_lines.product_id', 'p.name_ar', 'p.name_en')
            ->selectRaw('p.name_ar, p.name_en, SUM(transaction_sell_lines.qyt) as total_qty, SUM(transaction_sell_lines.total_before_vat) as total_sales')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();

        $transactions = DB::table('transactions as t')
            ->leftJoin('cs_contacts as c', 'c.id', '=', 't.contact_id')
            ->leftJoinSub($paymentsSub, 'tp', fn ($j) => $j->on('t.id', '=', 'tp.transaction_id'))
            ->whereIn('t.type', ['sell', 'quotation', 'sell-return'])
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->selectRaw('t.id, t.ref_no, t.type, t.transaction_date, t.payment_status, t.final_total, c.name as client_name, COALESCE(tp.paid_total,0) as paid_amount, GREATEST(t.final_total - COALESCE(tp.paid_total,0),0) as remaining_amount')
            ->orderByDesc('t.id')
            ->limit(10)
            ->get();

        $recentReceipts = TransactionPayments::with(['transaction', 'client'])
            ->whereHas('transaction', fn ($q) => $q->where('type', 'sell')->whereIn('status', $validStatuses))
            ->whereBetween('paid_on', [$startDate, $endDate])
            ->orderByDesc('paid_on')
            ->limit(10)
            ->get();
        $recentSalesReturns = DB::table('transactions as t')
            ->leftJoin('cs_contacts as c', 'c.id', '=', 't.contact_id')
            ->where('t.type', 'sell-return')
            ->whereIn('t.status', $validStatuses)
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->selectRaw('t.id, t.ref_no, t.transaction_date, t.status, t.payment_status, t.final_total, c.name as client_name')
            ->orderByDesc('t.id')
            ->limit(8)
            ->get();
        $recentQuotations = DB::table('transactions as t')
            ->leftJoin('cs_contacts as c', 'c.id', '=', 't.contact_id')
            ->where('t.type', 'quotation')
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->selectRaw('t.id, t.ref_no, t.transaction_date, t.status, t.payment_status, t.final_total, c.name as client_name')
            ->orderByDesc('t.id')
            ->limit(8)
            ->get();
        $recentFavoriteSales = DB::table('transactions as t')
            ->leftJoin('cs_contacts as c', 'c.id', '=', 't.contact_id')
            ->whereIn('t.type', ['sell', 'sell-return', 'quotation'])
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('favorite_bills as f')
                    ->whereColumn('f.transaction_id', 't.id')
                    ->where('f.user_id', Auth::id());
            })
            ->selectRaw('t.id, t.ref_no, t.type, t.transaction_date, t.status, t.payment_status, t.final_total, c.name as client_name')
            ->orderByDesc('t.id')
            ->limit(8)
            ->get();

        return view('sales::sell.dashboard', compact(
            'startDate',
            'endDate',
            'periodSales',
            'salesGrowth',
            'periodInvoices',
            'avgInvoice',
            'activeCustomers',
            'overdueAmount',
            'dueAmount',
            'receiptsStats',
            'couponStats',
            'salesReturnStats',
            'quotationStats',
            'favoritesStats',
            'monthLabels',
            'salesData',
            'collectionData',
            'paymentMethods',
            'topProducts',
            'transactions',
            'recentReceipts',
            'recentSalesReturns',
            'recentQuotations',
            'recentFavoriteSales'
        ));
    }

    public function salesDashboardExportCsv(Request $request)
    {
        $data = $this->buildSalesDashboardExportData($request);
        $rows = [];
        $rows[] = ['KPI', 'Value'];
        $rows[] = ['Period Sales', (string) $data['periodSales']];
        $rows[] = ['Sales Growth %', (string) $data['salesGrowth']];
        $rows[] = ['Invoices Count', (string) $data['periodInvoices']];
        $rows[] = ['Average Invoice', (string) $data['avgInvoice']];
        $rows[] = ['Active Customers', (string) $data['activeCustomers']];
        $rows[] = ['Total Due', (string) $data['dueAmount']];
        $rows[] = ['Overdue Amount', (string) $data['overdueAmount']];
        $rows[] = ['Total Collected', (string) ($data['receiptsStats']->total_collected ?? 0)];
        $rows[] = ['Receipt Count', (string) ($data['receiptsStats']->total_receipts ?? 0)];
        $rows[] = ['Coupon Usages', (string) ($data['couponStats']->coupon_usages ?? 0)];
        $rows[] = ['Coupon Discount Total', (string) ($data['couponStats']->total_coupon_discount ?? 0)];
        $rows[] = ['Sales Returns Count', (string) ($data['salesReturnStats']->total_count ?? 0)];
        $rows[] = ['Sales Returns Amount', (string) ($data['salesReturnStats']->total_amount ?? 0)];
        $rows[] = ['Quotations Count', (string) ($data['quotationStats']->total_count ?? 0)];
        $rows[] = ['Quotations Amount', (string) ($data['quotationStats']->total_amount ?? 0)];
        $rows[] = ['Favorites Count', (string) ($data['favoritesStats']->total_count ?? 0)];
        $rows[] = ['Favorites Amount', (string) ($data['favoritesStats']->total_amount ?? 0)];
        $rows[] = [];
        $rows[] = ['Payment Methods'];
        $rows[] = ['Method', 'Total'];
        foreach ($data['paymentMethods'] as $methodRow) {
            $rows[] = [
                $this->localizedPaymentMethod((string) ($methodRow->method ?? '')),
                (string) ($methodRow->total ?? 0),
            ];
        }
        $rows[] = [];
        $rows[] = ['Top Products'];
        $rows[] = ['Name AR', 'Name EN', 'Qty', 'Sales'];
        foreach ($data['topProducts'] as $p) {
            $rows[] = [$p->name_ar ?? '', $p->name_en ?? '', (string) $p->total_qty, (string) $p->total_sales];
        }
        $rows[] = [];
        $rows[] = ['Recent Transactions'];
        $rows[] = ['Ref', 'Client', 'Type', 'Payment Status', 'Approval Status', 'Date', 'Total', 'Paid', 'Remaining'];
        foreach ($data['transactions'] as $t) {
            $rows[] = [
                $t->ref_no ?? '',
                $t->client_name ?? '',
                $t->type ?? '',
                $this->localizedPaymentStatus((string) ($t->payment_status ?? '')),
                $this->localizedApprovalStatus((string) ($t->status ?? '')),
                $t->transaction_date ?? '',
                (string) $t->final_total,
                (string) $t->paid_amount,
                (string) $t->remaining_amount,
            ];
        }
        $rows[] = [];
        $rows[] = ['Recent Receipts'];
        $rows[] = ['Receipt Ref', 'Client', 'Invoice', 'Amount', 'Paid On'];
        foreach ($data['recentReceipts'] as $r) {
            $rows[] = [$r->payment_ref_no ?? '', $r->client->name ?? '', $r->transaction->ref_no ?? '', (string) $r->amount, (string) $r->paid_on];
        }

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'sales-dashboard-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function salesDashboardExportPdf(Request $request)
    {
        $data = $this->buildSalesDashboardExportData($request);
        $html = view('sales::sell.dashboard_export_pdf', $data)->render();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => storage_path('temp/mpdf'),
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('sales-dashboard-report.pdf', 'D');
    }

    private function buildSalesDashboardExportData(Request $request): array
    {
        $validStatuses = ['approved', 'final'];
        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : now()->startOfMonth()->startOfDay();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfDay();
        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }
        $periodDays = max(1, $startDate->diffInDays($endDate) + 1);
        $prevStart = $startDate->copy()->subDays($periodDays)->startOfDay();
        $prevEnd = $startDate->copy()->subDay()->endOfDay();

        $paymentsSub = DB::table('transaction_payments')
            ->selectRaw('transaction_id, SUM(IF(is_return = 1, -1 * amount, amount)) as paid_total')
            ->groupBy('transaction_id');
        $salesBase = Transaction::query()->where('type', 'sell')->whereIn('status', $validStatuses);
        $periodSales = (clone $salesBase)->whereBetween('transaction_date', [$startDate, $endDate])->sum('final_total');
        $prevSales = (clone $salesBase)->whereBetween('transaction_date', [$prevStart, $prevEnd])->sum('final_total');
        $salesGrowth = $prevSales > 0 ? round((($periodSales - $prevSales) / $prevSales) * 100, 2) : ($periodSales > 0 ? 100 : 0);
        $periodInvoices = (clone $salesBase)->whereBetween('transaction_date', [$startDate, $endDate])->count();
        $avgInvoice = $periodInvoices > 0 ? $periodSales / $periodInvoices : 0;
        $activeCustomers = (clone $salesBase)->whereBetween('transaction_date', [$startDate, $endDate])->distinct('contact_id')->count('contact_id');

        $periodSalesQuery = DB::table('transactions as t')
            ->leftJoinSub($paymentsSub, 'tp', fn ($j) => $j->on('t.id', '=', 'tp.transaction_id'))
            ->where('t.type', 'sell')
            ->whereIn('t.status', $validStatuses)
            ->whereBetween('t.transaction_date', [$startDate, $endDate]);
        $overdueAmount = (clone $periodSalesQuery)->whereDate('t.due_date', '<', now()->toDateString())->selectRaw('SUM(GREATEST(t.final_total - COALESCE(tp.paid_total,0),0)) as overdue')->value('overdue') ?? 0;
        $dueAmount = (clone $periodSalesQuery)->selectRaw('SUM(GREATEST(t.final_total - COALESCE(tp.paid_total,0),0)) as due')->value('due') ?? 0;

        $receiptsStats = TransactionPayments::query()
            ->whereHas('transaction', fn ($q) => $q->where('type', 'sell')->whereIn('status', $validStatuses)->whereBetween('transaction_date', [$startDate, $endDate]))
            ->selectRaw('COUNT(*) as total_receipts, SUM(amount) as total_collected')
            ->first();
        $couponStats = Coupon::query()
            ->leftJoin('sales_coupons_clients as scc', 'sales_coupons.id', '=', 'scc.coupon_id')
            ->leftJoin('transactions as t', 't.id', '=', 'scc.transaction_id')
            ->whereBetween(DB::raw('COALESCE(t.transaction_date, scc.created_at)'), [$startDate, $endDate])
            ->selectRaw('COUNT(DISTINCT sales_coupons.id) as active_coupons, COUNT(scc.transaction_id) as coupon_usages, SUM(COALESCE(t.discount_amount,0)) as total_coupon_discount')
            ->first();
        $salesReturnStats = Transaction::query()
            ->where('type', 'sell-return')
            ->whereIn('status', $validStatuses)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total_count, SUM(final_total) as total_amount')
            ->first();
        $quotationStats = Transaction::query()
            ->where('type', 'quotation')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total_count, SUM(final_total) as total_amount')
            ->first();
        $favoritesStats = Transaction::query()
            ->whereIn('type', ['sell', 'sell-return', 'quotation'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereHas('favorites', fn ($q) => $q->where('user_id', Auth::id()))
            ->selectRaw('COUNT(*) as total_count, SUM(final_total) as total_amount')
            ->first();
        $topProducts = TransactionSellLine::query()
            ->join('transactions as t', 't.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('product_products as p', 'p.id', '=', 'transaction_sell_lines.product_id')
            ->where('t.type', 'sell')->whereIn('t.status', $validStatuses)->where('transaction_sell_lines.is_show', 1)
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->groupBy('transaction_sell_lines.product_id', 'p.name_ar', 'p.name_en')
            ->selectRaw('p.name_ar, p.name_en, SUM(transaction_sell_lines.qyt) as total_qty, SUM(transaction_sell_lines.total_before_vat) as total_sales')
            ->orderByDesc('total_sales')->limit(10)->get();
        $paymentMethods = TransactionPayments::query()
            ->whereHas('transaction', fn ($q) => $q->where('type', 'sell')->whereIn('status', $validStatuses))
            ->whereBetween('paid_on', [$startDate, $endDate])
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();
        $transactions = DB::table('transactions as t')
            ->leftJoin('cs_contacts as c', 'c.id', '=', 't.contact_id')
            ->leftJoinSub($paymentsSub, 'tp', fn ($j) => $j->on('t.id', '=', 'tp.transaction_id'))
            ->whereIn('t.type', ['sell', 'quotation', 'sell-return'])
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->selectRaw('t.id, t.ref_no, t.type, t.transaction_date, t.status, t.payment_status, t.final_total, c.name as client_name, COALESCE(tp.paid_total,0) as paid_amount, GREATEST(t.final_total - COALESCE(tp.paid_total,0),0) as remaining_amount')
            ->orderByDesc('t.id')->limit(10)->get();
        $recentReceipts = TransactionPayments::with(['transaction', 'client'])
            ->whereHas('transaction', fn ($q) => $q->where('type', 'sell')->whereIn('status', $validStatuses))
            ->whereBetween('paid_on', [$startDate, $endDate])->orderByDesc('paid_on')->limit(10)->get();

        return compact(
            'startDate',
            'endDate',
            'periodSales',
            'salesGrowth',
            'periodInvoices',
            'avgInvoice',
            'activeCustomers',
            'overdueAmount',
            'dueAmount',
            'receiptsStats',
            'couponStats',
            'paymentMethods',
            'salesReturnStats',
            'quotationStats',
            'favoritesStats',
            'paymentMethods',
            'topProducts',
            'transactions',
            'recentReceipts'
        );
    }

    private function localizedPaymentMethod(string $method): string
    {
        if (app()->getLocale() !== 'ar') {
            return $method ?: '--';
        }
        $map = [
            'cash' => 'نقدي',
            'card' => 'بطاقة',
            'bank_transfer' => 'تحويل بنكي',
            'bank' => 'بنك',
            'cheque' => 'شيك',
            'check' => 'شيك',
            'credit' => 'آجل',
            'due' => 'آجل',
            'wallet' => 'محفظة',
        ];
        $key = strtolower(trim($method));

        return $map[$key] ?? ($method ?: '--');
    }

    private function localizedPaymentStatus(string $status): string
    {
        $key = strtolower(trim($status));
        if ($key === 'paid') {
            return app()->getLocale() === 'ar' ? 'مدفوع' : 'Paid';
        }
        if (in_array($key, ['partial', 'partial_paid', 'partially_paid'], true)) {
            return app()->getLocale() === 'ar' ? 'جزئي' : 'Partial';
        }

        return app()->getLocale() === 'ar' ? 'غير مدفوع' : 'Unpaid';
    }

    private function localizedApprovalStatus(string $status): string
    {
        $key = strtolower(trim($status));
        if (in_array($key, ['approved', 'final'], true)) {
            return app()->getLocale() === 'ar' ? 'معتمد' : 'Approved';
        }

        return app()->getLocale() === 'ar' ? 'مسودة' : 'Draft';
    }

    public function index(Request $request)
    {
        $transactionsQuery = Transaction::where('type', 'sell');

        if ($request->ajax()) {
            $transactionsQuery
                ->when($request->filled('favorite'), function ($query) {
                    $query->whereHas('favorites', fn ($q) => $q->where('user_id', Auth::id()));
                })
                ->when($request->filled('customer'), fn ($query) => $query->where('contact_id', $request->customer))
                ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->payment_status))
                ->when($request->filled('due_date_range'), function ($query) use ($request) {
                    $dueDateRange = trim($request->due_date_range);
                    $dates = explode(' إلى ', $dueDateRange);
                    if (count($dates) == 2) {
                        $query->whereBetween('due_date', [$dates[0], $dates[1]]);
                    }
                })
                ->when($request->filled('sale_date_range'), function ($query) use ($request) {
                    $saleDateRange = trim($request->sale_date_range);
                    $dates = explode(' إلى ', $saleDateRange);
                    if (count($dates) == 2) {
                        $query->whereBetween('transaction_date', [$dates[0], $dates[1]]);
                    }
                });

            $transactions = $transactionsQuery->orderBy('id', 'desc')->get();

            return Transaction::getSellsTable($transactions);
        }

        $transaction = $transactionsQuery->get();
        $columns = Transaction::getsSellsColumns();

        $quotations = Transaction::where('type', 'quotation')->where('po_status', '<>', 'completed')->get();

        $Latest_event = Actions::where('user_id', Auth::id())->where('type', 'create_sell')->first();

        if (! $Latest_event) {
            $actionUtil = new ActionUtil;
            $Latest_event = $actionUtil->saveOrUpdateAction('create_sell', 'add_sell', 'create-invoice');
        }

        $clients = Contact::where('business_type', 'customer')->get();

        return view('sales::sell.index', compact('columns', 'clients', 'Latest_event', 'transaction', 'quotations'));
    }

    public function favorites(Request $request)
    {
        $transactionsQuery = Transaction::whereIn('type', ['sell', 'sell-return', 'quotation'])
            ->whereHas('favorites', fn ($q) => $q->where('user_id', Auth::id()));

        if ($request->ajax()) {
            $transactionsQuery
                ->when($request->filled('transaction_type'), fn ($query) => $query->where('type', $request->transaction_type))
                ->when($request->filled('customer'), fn ($query) => $query->where('contact_id', $request->customer))
                ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->payment_status))
                ->when($request->filled('due_date_range'), function ($query) use ($request) {
                    $dueDateRange = trim($request->due_date_range);
                    $dates = explode(' إلى ', $dueDateRange);
                    if (count($dates) == 2) {
                        $query->whereBetween('due_date', [$dates[0], $dates[1]]);
                    }
                })
                ->when($request->filled('sale_date_range'), function ($query) use ($request) {
                    $saleDateRange = trim($request->sale_date_range);
                    $dates = explode(' إلى ', $saleDateRange);
                    if (count($dates) == 2) {
                        $query->whereBetween('transaction_date', [$dates[0], $dates[1]]);
                    }
                });

            $transactions = $transactionsQuery->orderBy('id', 'desc')->get();

            return Transaction::getSellsTable($transactions);
        }

        $transaction = $transactionsQuery->get();
        $columns = Transaction::getsSellsColumns();
        $clients = Contact::where('business_type', 'customer')->get();

        return view('sales::sell.favorites', compact('columns', 'clients', 'transaction'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $actionUtil = new ActionUtil;
        $actionUtil->saveOrUpdateAction('create_sell', 'add_sell', 'create-invoice');
        $clients = Contact::where('business_type', 'customer')->get();
        $taxes = Tax::all();
        $payment_terms = SalesUtile::paymentTerms();
        $paymentMethods = SalesUtile::paymentMethods();
        $orderStatuses = SalesUtile::orderStatuses();
        $accounts = AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();
        $establishments = Establishment::where('is_main', 0)->get();
        $countries = Country::all();
        $quotation = false;
        $quotationId = $request->input('quotation_id');
        $transaction = Transaction::find($quotationId);
        if ($quotationId > 0) {

            $actionUtil->saveOrUpdateAction('create_sell', 'convert-to-invoice', '#');
        }

        $settings = Setting::getNotesAndTermsConditions();
        $allowSaleWithoutStock = Auth::user() && Auth::user()->can(Setting::PERMISSION_ALLOW_SALE_WITHOUT_STOCK);
        $invoicePrecheckConfig = $this->buildSalesInvoicePrecheckConfig();

        $products = Product::with(['unitTransfers' => function ($query) {
            $query->whereNull('unit2');
        }])->get();

        $products = Product::productsForSell();

        $Latest_event = Actions::where('user_id', Auth::user()->id)->where('type', 'save_sell')->first();
        if (! $Latest_event) {
            $actionUtil = new ActionUtil;
            $Latest_event = $actionUtil->saveOrUpdateAction('save_sell', 'save_sell', 'save');
        }

        return view('sales::sell.create', compact('clients', 'settings', 'Latest_event', 'transaction', 'quotation', 'taxes', 'establishments', 'countries', 'payment_terms', 'orderStatuses', 'products', 'paymentMethods', 'accounts', 'cost_centers', 'allowSaleWithoutStock', 'invoicePrecheckConfig'));
    }

    private function buildSalesInvoicePrecheckConfig(): array
    {
        $missing = [];
        if (! AccountsRoting::where('type', 'sales_sales')->value('account_id')) {
            $missing[] = __('accounting::lang.sales');
        }
        if (! AccountsRoting::where('type', 'sales_vat_calculation')->value('account_id')) {
            $missing[] = __('accounting::lang.vat');
        }
        if (Setting::isPerpetualInventory()) {
            $inventoryAccountId = AccountingAccount::query()
                ->where('gl_code', '11105')
                ->orWhere('account_category', 'inventory')
                ->value('id');
            $cogsAccountId = AccountingAccount::query()
                ->where('gl_code', '50101')
                ->orWhere('account_category', 'COGS')
                ->orWhere('account_category', 'cost_of_goods_sold')
                ->value('id');
            if (! $inventoryAccountId) {
                $missing[] = __('accounting::lang.inventory');
            }
            if (! $cogsAccountId) {
                $missing[] = __('accounting::lang.cost of goods sold');
            }
        }

        return [
            'missingAccounts' => $missing,
            'messages' => [
                'missingAccountsHeader' => app()->getLocale() === 'ar'
                    ? 'إعدادات الحسابات غير مكتملة، يرجى مراجعة توجيه الحسابات:'
                    : 'Accounting setup is incomplete, please review Accounts Routing:',
                'missingUnit' => app()->getLocale() === 'ar'
                    ? 'يرجى اختيار وحدة لكل صنف قبل الحفظ.'
                    : 'Please select unit for each product before saving.',
            ],
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return $request;

        // try {
        $actionUtil = new ActionUtil;
        $contactUtils = new ContactUtils;
        $accountUtil = new AccountingUtil;
        $actionUtil->saveOrUpdateAction('save_sell', 'save_sell', $request->action);

        $transactionUtil = new TransactionUtils;
        DB::beginTransaction();
        $ref_no = SalesUtile::generateReferenceNumber('sell');

        $invoiced_discount_type = $request->invoice_discount ? $request->invoiced_discount_type : null;
        $toggleCostCenter = Setting::where('key', 'toggleCost_center')->value('value') == 1;
        $toggleStorehouse = Setting::where('key', 'toggleStorehouse')->value('value') == 1;
        $toggleDelegates = Setting::where('key', 'toggleDelegates')->value('value') == 1;

        if (! $toggleCostCenter) {
            $request->merge(['cost_center' => null]);
        }
        if (! $toggleDelegates) {
            $request->merge(['Delegates' => null]);
        }

        $main_establishment = Establishment::notMain()->active()->first();
        if ($toggleStorehouse) {
            if (! $request->filled('storehouse')) {
                throw ValidationException::withMessages([
                    'storehouse' => __('messages.field_is_required', ['field' => __('sales::fields.storehouse')]),
                ]);
            }
            $establishment_id = (int) $request->storehouse;
        } else {
            $establishment_id = (int) ($main_establishment?->id ?? 0);
            $request->merge(['storehouse' => $establishment_id]);
        }
        if ($establishment_id <= 0) {
            throw ValidationException::withMessages([
                'storehouse' => __('messages.field_is_required', ['field' => __('sales::fields.storehouse')]),
            ]);
        }
        if ($main_establishment && (int) $request->storehouse === (int) $main_establishment->id) {
            $establishment_id = (int) $main_establishment->id;
        }
        $termsNotesData = null;
        if (isset($request->toggle_terms_notes)) {
            $termsNotesData = json_encode([
                'terms_en' => request('terms_and_conditions_en'),
                'terms_ar' => request('terms_and_conditions_ar'),
                'note_en' => request('note_en'),
                'note_ar' => request('note_ar'),
            ]);
        }

        $products = json_decode(json_encode($request->products ?? []));
        $couponUsage = null;
        $couponCode = trim((string) $request->input('coupon_code', ''));
        if ($couponCode !== '') {
            try {
                $couponService = app(ApplyCouponService::class);
                $taxableBefore = (float) ($request->totalAfterDiscount ?? $request->totalBeforeVat ?? 0);
                $currentTax = (float) ($request->totalVat ?? 0);
                $couponUsage = $couponService->applyForSale(
                    $couponCode,
                    (int) $request->client_id,
                    (int) $establishment_id,
                    $products,
                    $taxableBefore,
                    $currentTax
                );

                $request->merge([
                    'invoiced_discount_type' => 'fixed',
                    'invoice_discount' => (float) ($request->invoice_discount ?? 0) + (float) $couponUsage['discount_amount'],
                    'totalAfterDiscount' => $couponUsage['taxable_after'],
                    'totalVat' => $couponUsage['tax_amount'],
                    'totalAfterVat' => $couponUsage['final_total'],
                ]);
                if ((float) ($request->paid_amount ?? 0) > (float) $couponUsage['final_total']) {
                    $request->merge(['paid_amount' => $couponUsage['final_total']]);
                }
            } catch (\Throwable $e) {
                DB::rollBack();

                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        $resolvedAccountId = (int) ($request->input('account_id') ?: $request->input('cash_account'));
        if ($resolvedAccountId > 0) {
            $request->merge(['account_id' => $resolvedAccountId]);
        }

        $quotation_id = null;
        if ($request->quotation_id) {
            $quotation_id = $request->quotation_id;
        }
        $transaction = Transaction::create([
            'type' => 'sell',
            'invoice_type' => $request->invoice_type,
            'due_date' => $request->due_date,
            'transaction_date' => $request->transaction_date,
            'contact_id' => $request->client_id,
            'cost_center' => $request->cost_center ?? null,
            'discount_amount' => $request->invoice_discount,
            'discount_type' => $invoiced_discount_type,
            'total_before_tax' => $request->totalBeforeVat,
            'totalAfterDiscount' => $request->totalAfterDiscount,
            'tax_amount' => $request->totalVat,
            'final_total' => $request->totalAfterVat,
            'created_by' => Auth::user()->id,
            'description' => $request->invoice_note,
            'ref_no' => $ref_no,
            'status' => $request->status,
            'notice' => $request->notice,
            'establishment_id' => $establishment_id,
            'settings_terms_notes' => $termsNotesData,

            'parent_id' => $quotation_id,

        ]);

        $mustValidateStock = Setting::mustValidatePerpetualStock(Auth::user());

        foreach ($products as $product) {
            $discount_type = $product->discount ? $product->discount_type : null;

            if ($mustValidateStock) {
                $product_inventorie = DB::table('product_products')
                    ->select(
                        'product_products.id',
                        DB::raw('COALESCE(SUM(product_inventories.qty), 0) as inventory_qty')
                    )
                    ->leftJoin('product_inventories', 'product_products.id', '=', 'product_inventories.product_id')
                    ->where('product_products.id', $product->products_id)
                    ->where('product_inventories.establishment_id', $establishment_id)
                    ->groupBy('product_products.id')
                    ->first();
                $inventory_qty = $product_inventorie->inventory_qty ?? 0;

                if ($inventory_qty < $product->qty) {
                    DB::rollBack();
                    $productModel = Product::select('name_ar', 'name_en')->find($product->products_id);
                    $productName = $productModel?->name_ar ?: ($productModel?->name_en ?: ('#'.$product->products_id));
                    $message = app()->getLocale() === 'ar'
                        ? "لا يمكن إتمام البيع لأن الكمية غير كافية للصنف: {$productName}"
                        : "Sale cannot be completed due to insufficient stock for product: {$productName}";

                    return redirect()->back()->withInput()->with('error', $message);
                }
            }

            TransactionSellLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->products_id,
                'qyt' => $product->qty,
                'unit_id' => $product->unit,
                'unit_price_before_discount' => $product->unit_price,
                'unit_price' => $product->unit_price,
                'discount_type' => $discount_type,
                'discount_amount' => $product->discount,
                'unit_price_inc_tax' => $product->total_after_vat,
                'tax_id' => $product->tax_vat,
                'tax_value' => $product->vat_value,
                'total_before_vat' => $product->total_before_vat,
            ]);

            // $is_recipe_yield = Product::find($product->products_id)->recipe_yield;
            // if ($is_recipe_yield) {

            $recipeProducts = RecipeProduct::with('products')->where('product_id', $product->products_id)->get();

            if ($recipeProducts->isNotEmpty()) {
                foreach ($recipeProducts as $recipeProduct) {
                    $ingredient = $recipeProduct->products;
                    if ($ingredient) {
                        $discount_type = $ingredient->discount ? $ingredient->discount_type : null;
                        $price_with_tax = $ingredient->type == 'ingredint' ? $ingredient->orderPriceWithTax : $ingredient->price_with_tax;

                        TransactionSellLine::create([
                            'transaction_id' => $transaction->id,
                            'product_id' => $ingredient->id,
                            'qyt' => $product->qty * $recipeProduct->quantity,
                            'unit_id' => $recipeProduct->unit_transfer_id,
                            'unit_price_before_discount' => $ingredient->price ?? 0,
                            'unit_price' => $ingredient->price ?? 0,
                            'discount_type' => 'fixd',
                            'discount_amount' => 0,
                            'unit_price_inc_tax' => $price_with_tax,
                            'tax_id' => $ingredient->tax_id,
                            'tax_value' => $price_with_tax - ($ingredient->price ?? 0),
                            'total_before_vat' => $ingredient->price ?? 0,
                            'is_show' => 0,
                        ]);
                    }
                }
            }
        }

        if ($couponUsage && $transaction->status !== 'draft') {
            try {
                app(ApplyCouponService::class)->registerUsage(
                    (int) $couponUsage['coupon']->id,
                    (int) $transaction->contact_id,
                    (int) $transaction->id
                );
            } catch (\Throwable $e) {
                DB::rollBack();

                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        if ($quotation_id) {
            $this->updatePurchaseOrderStatus(
                $quotation_id

            );
        }
        // return [$request->paid_amount,$transaction->final_total == $request->paid_amount,$transaction->final_total];
        if ($request->paid_amount) {
            if ($transaction->final_total == $request->paid_amount) {
                $request['amount'] = $request->paid_amount;
                $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
            } else {
                $this->createPaymentLines($transaction, $request);
            }
        } else {
            $acc_trans_mapping = new AccountingAccTransMapping;
            $ref_number = $accountUtil->generateReferenceNumber('journal_entry');
            $acc_trans_mapping->ref_no = $ref_number;
            $acc_trans_mapping->note = 'مبيعات';
            $acc_trans_mapping->type = 'journal_entry';
            $acc_trans_mapping->created_by = Auth::user()->id;
            $acc_trans_mapping->is_manual = 0;
            $acc_trans_mapping->operation_date = Carbon::parse($transaction->transaction_date ?? now())->format('Y-m-d H:i:s');
            $acc_trans_mapping->save();
            $acc_trans_mapping_id = $acc_trans_mapping->id;

            $sales_sales = AccountsRoting::where('type', 'sales_sales')->first();
            $sales_vat_calculation = AccountsRoting::where('type', 'sales_vat_calculation')->first();

            $client = Contact::find($request->client_id);
            $transactionPayment = new \stdClass;

            $transactionPayment->paid_on = Carbon::parse(now())->format('Y-m-d H:i:s');
            $transactionPayment->account_id = $client->account_id;
            $transactionPayment->created_by = Auth::user()->id;
            $transactionPayment->created_by = Auth::user()->id;
            $transactionPayment->transaction_id = $transaction->id;
            $transactionPayment->id = null;

            $transactionPayment->amount = $transaction->final_total;

            $accountUtil->saveAccountRouteTransaction(
                'debit',
                $transactionPayment,
                $transaction,
                $acc_trans_mapping_id,
                $request
            );

            $transactionPayment->account_id = $sales_sales->account_id;
            $transactionPayment->amount = (float) ($transaction->totalAfterDiscount ?? $transaction->total_after_discount ?? $transaction->total_before_tax);

            $accountUtil->saveAccountRouteTransaction(
                'credit',
                $transactionPayment,
                $transaction,
                $acc_trans_mapping_id,
                $request
            );

            $transactionPayment->account_id = $sales_vat_calculation->account_id;
            $transactionPayment->amount = $transaction->tax_amount;

            $accountUtil->saveAccountRouteTransaction(
                'credit',
                $transactionPayment,
                $transaction,
                $acc_trans_mapping_id,
                $request
            );

            $this->appendPerpetualInventoryImpactEntries($transaction, (int) $acc_trans_mapping_id, $request);

            AutoJournalGuard::assertBalanced((int) $acc_trans_mapping_id);
        }

        $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

        $totalOutstanding = $transactionUtil->contactTotalOutstanding($transaction);

        $msg = __('messages.add_successfully');
        $status = 'success';
        if ($totalOutstanding) {
            $credit_limit = Contact::find($transaction->contact_id)->credit_limit;
            if ($credit_limit && $credit_limit < $totalOutstanding) {
                $msg = __('messages.Added successfully, but the customer exceeded');
                $status = 'error';
            }
        }

        DB::commit();
        if ($request->action == 'save_print') {
            return redirect()->route('transaction-print', $transaction->id)->with($status, $msg);
        } elseif ($request->action == 'save_add') {
            return redirect()->route('create-invoice')->with($status, $msg);
        } else {
            return redirect()->route('invoices')->with($status, $msg);
        }
        // } catch (Exception $e) {
        //     DB::rollBack();
        //     return redirect()->route('invoices')->with('error', __('messages.something_went_wrong'));
        // }
    }

    public function createPaymentLines($transaction, $request)
    {
        // dd($transaction, $request);
        $acc_trans_mapping = new AccountingAccTransMapping;

        $accountUtil = new AccountingUtil;
        $cash_account_id = (int) ($request->input('account_id') ?: $request->input('cash_account'));
        if ($cash_account_id <= 0) {
            throw ValidationException::withMessages([
                'account_id' => __('messages.field_is_required', ['field' => __('accounting::lang.account')]),
            ]);
        }
        $ref_number = $accountUtil->generateReferenceNumber('journal_entry');
        $acc_trans_mapping->ref_no = $ref_number;
        $acc_trans_mapping->note = 'مبيعات';
        $acc_trans_mapping->type = 'journal_entry';
        $acc_trans_mapping->created_by = Auth::user()->id;
        $acc_trans_mapping->is_manual = 0;
        $acc_trans_mapping->operation_date = Carbon::parse($transaction->transaction_date ?? now())->format('Y-m-d H:i:s');
        $acc_trans_mapping->save();
        $acc_trans_mapping_id = $acc_trans_mapping->id;

        $sales_sales = AccountsRoting::where('type', 'sales_sales')->first();
        $sales_vat_calculation = AccountsRoting::where('type', 'sales_vat_calculation')->first();

        $payment_method_id = null;

        if ($request->has('payment_method_id')) {
            $payment_method_id = $request->payment_method_id;
        }
        $paymentOnInput = $request->input('payment_on') ?: $request->input('pament_on');
        $date = Carbon::parse($paymentOnInput ?? now());
        $payment_on = $date->format('Y-m-d H:i:s');
        $transactionUtil = new TransactionUtils;
        $prefix_type = $transaction->type == 'purchase' ? 'purchase_payment' : 'sell_payment';

        $payment_ref_no = $transactionUtil->generateReferenceNumber($prefix_type);

        $client = Contact::find($request->client_id);
        $transactionPayment = TransactionPayments::create([
            'transaction_id' => $transaction->id,
            'payment_type' => $transaction->invoice_type,
            'amount' => $request->paid_amount,
            'method' => 'due',
            'payment_method_id' => $payment_method_id,
            'is_return' => $transaction->type == 'sell-return' ?? 0,
            'note' => $request->additionalNotes,
            'paid_on' => $payment_on,
            'created_by' => Auth::check() ? Auth::user()->id : $request->created_by,
            'payment_for' => $transaction->contact_id,
            'payment_ref_no' => $payment_ref_no,
            'account_id' => $cash_account_id,
        ]);
        $client = Contact::find($transactionPayment->payment_for);

        $transactionPayment->account_id = $client->account_id;

        $transactionPayment->amount = $transaction->final_total;
        // $transactionPayment->amount = $transaction->final_total - $request->paid_amount;

        $accountUtil->saveAccountRouteTransaction(
            'debit',
            $transactionPayment,
            $transaction,
            $acc_trans_mapping_id,
            $request
        );

        $transactionPayment->account_id = $sales_sales->account_id;
        $transactionPayment->amount = (float) ($transaction->totalAfterDiscount ?? $transaction->total_after_discount ?? $transaction->total_before_tax);

        $accountUtil->saveAccountRouteTransaction(
            'credit',
            $transactionPayment,
            $transaction,
            $acc_trans_mapping_id,
            $request
        );

        $transactionPayment->account_id = $sales_vat_calculation->account_id;
        $transactionPayment->amount = $transaction->tax_amount;

        $accountUtil->saveAccountRouteTransaction(
            'credit',
            $transactionPayment,
            $transaction,
            $acc_trans_mapping_id,
            $request
        );

        $this->appendPerpetualInventoryImpactEntries($transaction, (int) $acc_trans_mapping_id, $request);

        $transactionPayment->account_id = $client->account_id;

        // $transactionPayment->amount = $transaction->final_total;
        $transactionPayment->amount = $request->paid_amount;

        $accountUtil->saveAccountRouteTransaction(
            'credit',
            $transactionPayment,
            $transaction,
            $acc_trans_mapping_id,
            $request
        );

        $transactionPayment->account_id = $cash_account_id;
        $transactionPayment->amount = $request->paid_amount; // $transaction->total_before_tax;

        $accountUtil->saveAccountRouteTransaction(
            'debit',
            $transactionPayment,
            $transaction,
            $acc_trans_mapping_id,
            $request
        );

        AutoJournalGuard::assertBalanced((int) $acc_trans_mapping_id);
    }

    private function appendPerpetualInventoryImpactEntries(Transaction $transaction, int $accTransMappingId, Request $request): void
    {
        if (! Setting::isPerpetualInventory()) {
            return;
        }

        $inventoryAccountId = PerpetualInventoryAccountResolver::resolveInventoryAssetAccountId(
            isset($transaction->establishment_id) ? (int) $transaction->establishment_id : null
        );

        $cogsAccountId = AccountingAccount::query()
            ->where('gl_code', '50101')
            ->orWhere('account_category', 'COGS')
            ->orWhere('account_category', 'cost_of_goods_sold')
            ->value('id');

        if (! $inventoryAccountId || ! $cogsAccountId) {
            throw ValidationException::withMessages([
                'inventory_policy' => app()->getLocale() === 'ar'
                    ? 'لا يمكن ترحيل أثر الجرد المستمر محاسبياً. يرجى التأكد من وجود حسابي المخزون وتكلفة البضاعة المباعة.'
                    : 'Perpetual inventory accounting impact cannot be posted. Please configure Inventory and COGS accounts.',
            ]);
        }

        $cogsAmount = (float) (DB::table('transaction_sell_lines as tsl')
            ->join('product_products as p', 'p.id', '=', 'tsl.product_id')
            ->where('tsl.transaction_id', $transaction->id)
            ->sum(DB::raw('COALESCE(tsl.qyt,0) * COALESCE(p.cost,0)')));

        if ($cogsAmount <= 0) {
            return;
        }

        $accountUtil = new AccountingUtil;
        $movement = new \stdClass;
        $movement->paid_on = Carbon::parse($transaction->transaction_date ?? now())->format('Y-m-d H:i:s');
        $movement->created_by = Auth::id();
        $movement->transaction_id = $transaction->id;
        $movement->id = null;

        $movement->account_id = $cogsAccountId;
        $movement->amount = $cogsAmount;
        $accountUtil->saveAccountRouteTransaction('debit', $movement, $transaction, $accTransMappingId, $request);

        $movement->account_id = $inventoryAccountId;
        $movement->amount = $cogsAmount;
        $accountUtil->saveAccountRouteTransaction('credit', $movement, $transaction, $accTransMappingId, $request);
    }

    public function validateInvoiceRequest($request)
    {
        $rules = [
            'products' => ['required', 'array', 'min:1'],
            'products.*.products_id' => ['required'],
        ];

        $messages = [
            'products.required' => 'يجب إرسال المنتجات.',
            'products.array' => 'المنتجات يجب أن تكون قائمة.',
            'products.min' => 'يجب إضافة منتج واحد على الأقل.',
            'products.*.products_id.required' => 'يجب أن يحتوي كل منتج على رقم تعريف.',
        ];

        $validatedData = $request->validate($rules, $messages);

        return $validatedData;
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('sales::show');
    }

    public function updatePurchaseOrderStatus($quotation_id)
    {
        $poTransaction = Transaction::find($quotation_id);

        if (! $poTransaction) {
            return;
        }

        $poLines = TransactionSellLine::where('transaction_id', $quotation_id)->get();

        if ($poLines->isEmpty()) {
            $poTransaction->po_status = 'pending';
            $poTransaction->save();

            return;
        }

        $invoiceIds = Transaction::where('parent_id', $quotation_id)->pluck('id');
        $invoiceLines = TransactionSellLine::whereIn('transaction_id', $invoiceIds)->get();

        $overallStatus = 'completed';
        $productsStatus = [];

        foreach ($poLines as $poLine) {
            $requestedQty = $poLine->qyt;

            $purchasedQty = $invoiceLines
                ->where('product_id', $poLine->product_id)
                ->sum('qyt');

            $remainingQty = max(0, $requestedQty - $purchasedQty);

            if ($purchasedQty >= $requestedQty) {
                $lineStatus = 'completed';
            } elseif ($purchasedQty > 0 && $purchasedQty < $requestedQty) {
                $lineStatus = 'partial';
                $overallStatus = 'partial';
            } else {
                $lineStatus = 'pending';
                if ($overallStatus === 'completed') {
                    $overallStatus = 'partial';
                }
            }

            $poLine->line_status = $lineStatus;
            $poLine->remaining_qty = $remainingQty;
            $poLine->save();

            $productsStatus[] = [
                'product_id' => $poLine->product_id,
                'requested' => $requestedQty,
                'purchased' => $purchasedQty,
                'remaining' => $remainingQty,
                'line_status' => $lineStatus,
            ];
        }

        if ($invoiceIds->isEmpty()) {
            $overallStatus = 'pending';
        }

        $poTransaction->po_status = $overallStatus;
        $poTransaction->save();

        return [
            'po_status' => $overallStatus,
            'products' => $productsStatus,
        ];
    }
}
