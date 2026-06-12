<?php

namespace Modules\Purchases\Http\Controllers;

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
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodGatekeeper;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Accounting\Utils\AutoJournalGuard;
use Modules\Accounting\Utils\PerpetualInventoryAccountResolver;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Actions;
use Modules\General\Models\Country;
use Modules\General\Models\Setting;
use Modules\General\Models\Tax;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionPayments;
use Modules\General\Utils\ActionUtil;
use Modules\General\Utils\TransactionUtils;
use Modules\Product\Models\Product;
use Modules\Product\Models\UnitTransfer;
use Modules\Sales\Utils\SalesUtile;
use Mpdf\Mpdf;

class PurchasesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function purchaseDashbord(Request $request)
    {
        $data = $this->buildPurchaseDashboardData($request);

        return view('purchases::purchases.dashboard', $data);
    }

    public function purchaseDashboardExportCsv(Request $request)
    {
        $data = $this->buildPurchaseDashboardData($request);
        $rows = [];
        $rows[] = ['KPI', 'Value'];
        $rows[] = ['Period Purchases', (string) $data['periodPurchases']];
        $rows[] = ['Purchases Growth %', (string) $data['purchasesGrowth']];
        $rows[] = ['Invoices Count', (string) $data['periodInvoices']];
        $rows[] = ['Average Invoice', (string) $data['avgInvoice']];
        $rows[] = ['Active Suppliers', (string) $data['activeSuppliers']];
        $rows[] = ['Total Due', (string) $data['dueAmount']];
        $rows[] = ['Overdue Amount', (string) $data['overdueAmount']];
        $rows[] = ['Total Paid', (string) ($data['paymentsStats']->total_paid ?? 0)];
        $rows[] = ['Payment Vouchers', (string) ($data['paymentsStats']->total_payments ?? 0)];
        $rows[] = ['Purchase Returns Count', (string) ($data['purchaseReturnStats']->total_count ?? 0)];
        $rows[] = ['Purchase Returns Amount', (string) ($data['purchaseReturnStats']->total_amount ?? 0)];
        $rows[] = ['Purchase Orders Count', (string) ($data['purchaseOrderStats']->total_count ?? 0)];
        $rows[] = ['Purchase Orders Amount', (string) ($data['purchaseOrderStats']->total_amount ?? 0)];
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
        $rows[] = ['Top Purchased Products'];
        $rows[] = ['Name AR', 'Name EN', 'Qty', 'Amount'];
        foreach ($data['topProducts'] as $p) {
            $rows[] = [$p->name_ar ?? '', $p->name_en ?? '', (string) $p->total_qty, (string) $p->total_amount];
        }
        $rows[] = [];
        $rows[] = ['Recent Transactions'];
        $rows[] = ['Ref', 'Supplier', 'Type', 'Payment Status', 'Approval Status', 'Date', 'Total', 'Paid', 'Remaining'];
        foreach ($data['transactions'] as $t) {
            $rows[] = [
                $t->ref_no ?? '',
                $t->supplier_name ?? '',
                $t->type ?? '',
                $this->localizedPaymentStatus((string) ($t->payment_status ?? '')),
                $this->localizedApprovalStatus((string) ($t->status ?? '')),
                $t->transaction_date ?? '',
                (string) $t->final_total,
                (string) $t->paid_amount,
                (string) $t->remaining_amount,
            ];
        }

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'purchase-dashboard-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function purchaseDashboardExportPdf(Request $request)
    {
        $data = $this->buildPurchaseDashboardData($request);
        $html = view('purchases::purchases.dashboard_export_pdf', $data)->render();
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

        return $mpdf->Output('purchase-dashboard-report.pdf', 'D');
    }

    private function buildPurchaseDashboardData(Request $request): array
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
        $purchaseBase = Transaction::query()->where('type', 'purchases')->whereIn('status', $validStatuses);

        $periodPurchases = (clone $purchaseBase)->whereBetween('transaction_date', [$startDate, $endDate])->sum('final_total');
        $prevPurchases = (clone $purchaseBase)->whereBetween('transaction_date', [$prevStart, $prevEnd])->sum('final_total');
        $purchasesGrowth = $prevPurchases > 0 ? round((($periodPurchases - $prevPurchases) / $prevPurchases) * 100, 2) : ($periodPurchases > 0 ? 100 : 0);
        $periodInvoices = (clone $purchaseBase)->whereBetween('transaction_date', [$startDate, $endDate])->count();
        $avgInvoice = $periodInvoices > 0 ? $periodPurchases / $periodInvoices : 0;
        $activeSuppliers = (clone $purchaseBase)->whereBetween('transaction_date', [$startDate, $endDate])->distinct('contact_id')->count('contact_id');

        $periodPurchaseQuery = DB::table('transactions as t')
            ->leftJoinSub($paymentsSub, 'tp', fn ($j) => $j->on('t.id', '=', 'tp.transaction_id'))
            ->where('t.type', 'purchases')
            ->whereIn('t.status', $validStatuses)
            ->whereBetween('t.transaction_date', [$startDate, $endDate]);
        $overdueAmount = (clone $periodPurchaseQuery)->whereDate('t.due_date', '<', now()->toDateString())->selectRaw('SUM(GREATEST(t.final_total - COALESCE(tp.paid_total,0),0)) as overdue')->value('overdue') ?? 0;
        $dueAmount = (clone $periodPurchaseQuery)->selectRaw('SUM(GREATEST(t.final_total - COALESCE(tp.paid_total,0),0)) as due')->value('due') ?? 0;

        $paymentsStats = TransactionPayments::query()
            ->whereHas('transaction', fn ($q) => $q->where('type', 'purchases')->whereIn('status', $validStatuses)->whereBetween('transaction_date', [$startDate, $endDate]))
            ->selectRaw('COUNT(*) as total_payments, SUM(amount) as total_paid')
            ->first();
        $purchaseReturnStats = Transaction::query()
            ->where('type', 'purchases-return')
            ->whereIn('status', $validStatuses)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total_count, SUM(final_total) as total_amount')
            ->first();
        $purchaseOrderStats = Transaction::query()
            ->where('type', 'purchases-order')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total_count, SUM(final_total) as total_amount')
            ->first();
        $favoritesStats = Transaction::query()
            ->whereIn('type', ['purchases', 'purchases-return', 'purchases-order'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereHas('favorites', fn ($q) => $q->where('user_id', Auth::id()))
            ->selectRaw('COUNT(*) as total_count, SUM(final_total) as total_amount')
            ->first();

        $months = collect(range(5, 0))
            ->map(fn ($offset) => now()->subMonths($offset)->format('Y-m'))
            ->push(now()->format('Y-m'))
            ->values();
        $purchaseMonthly = (clone $purchaseBase)
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month_key, SUM(final_total) as total")
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('month_key')
            ->pluck('total', 'month_key');
        $paymentsMonthly = TransactionPayments::query()
            ->whereHas('transaction', fn ($q) => $q->where('type', 'purchases')->whereIn('status', $validStatuses))
            ->selectRaw("DATE_FORMAT(paid_on, '%Y-%m') as month_key, SUM(amount) as total")
            ->whereBetween('paid_on', [$startDate, $endDate])
            ->groupBy('month_key')
            ->pluck('total', 'month_key');
        $monthLabels = [];
        $purchaseData = [];
        $paymentData = [];
        foreach ($months as $month) {
            $monthLabels[] = Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y');
            $purchaseData[] = (float) ($purchaseMonthly[$month] ?? 0);
            $paymentData[] = (float) ($paymentsMonthly[$month] ?? 0);
        }

        $paymentMethods = TransactionPayments::query()
            ->whereHas('transaction', fn ($q) => $q->where('type', 'purchases')->whereIn('status', $validStatuses))
            ->whereBetween('paid_on', [$startDate, $endDate])
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        $topProducts = TransactionePurchasesLine::query()
            ->join('transactions as t', 't.id', '=', 'transactione_purchases_lines.transaction_id')
            ->join('product_products as p', 'p.id', '=', 'transactione_purchases_lines.product_id')
            ->where('t.type', 'purchases')
            ->whereIn('t.status', $validStatuses)
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->groupBy('transactione_purchases_lines.product_id', 'p.name_ar', 'p.name_en')
            ->selectRaw('p.name_ar, p.name_en, SUM(transactione_purchases_lines.qyt) as total_qty, SUM(transactione_purchases_lines.total_before_vat) as total_amount')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        $transactions = DB::table('transactions as t')
            ->leftJoin('cs_contacts as c', 'c.id', '=', 't.contact_id')
            ->leftJoinSub($paymentsSub, 'tp', fn ($j) => $j->on('t.id', '=', 'tp.transaction_id'))
            ->whereIn('t.type', ['purchases', 'purchases-order', 'purchases-return'])
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->selectRaw('t.id, t.ref_no, t.type, t.transaction_date, t.status, t.payment_status, t.final_total, c.name as supplier_name, COALESCE(tp.paid_total,0) as paid_amount, GREATEST(t.final_total - COALESCE(tp.paid_total,0),0) as remaining_amount')
            ->orderByDesc('t.id')
            ->limit(10)
            ->get();

        $recentPayments = TransactionPayments::with(['transaction', 'client'])
            ->whereHas('transaction', fn ($q) => $q->where('type', 'purchases')->whereIn('status', $validStatuses))
            ->whereBetween('paid_on', [$startDate, $endDate])
            ->orderByDesc('paid_on')
            ->limit(10)
            ->get();
        $recentPurchaseReturns = DB::table('transactions as t')
            ->leftJoin('cs_contacts as c', 'c.id', '=', 't.contact_id')
            ->where('t.type', 'purchases-return')
            ->whereIn('t.status', $validStatuses)
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->selectRaw('t.id, t.ref_no, t.transaction_date, t.status, t.payment_status, t.final_total, c.name as supplier_name')
            ->orderByDesc('t.id')
            ->limit(8)
            ->get();
        $recentPurchaseOrders = DB::table('transactions as t')
            ->leftJoin('cs_contacts as c', 'c.id', '=', 't.contact_id')
            ->where('t.type', 'purchases-order')
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->selectRaw('t.id, t.ref_no, t.transaction_date, t.status, t.payment_status, t.final_total, c.name as supplier_name')
            ->orderByDesc('t.id')
            ->limit(8)
            ->get();
        $recentFavoritePurchases = DB::table('transactions as t')
            ->leftJoin('cs_contacts as c', 'c.id', '=', 't.contact_id')
            ->whereIn('t.type', ['purchases', 'purchases-return', 'purchases-order'])
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('favorite_bills as f')
                    ->whereColumn('f.transaction_id', 't.id')
                    ->where('f.user_id', Auth::id());
            })
            ->selectRaw('t.id, t.ref_no, t.type, t.transaction_date, t.status, t.payment_status, t.final_total, c.name as supplier_name')
            ->orderByDesc('t.id')
            ->limit(8)
            ->get();

        return compact(
            'startDate',
            'endDate',
            'periodPurchases',
            'purchasesGrowth',
            'periodInvoices',
            'avgInvoice',
            'activeSuppliers',
            'overdueAmount',
            'dueAmount',
            'paymentsStats',
            'purchaseReturnStats',
            'purchaseOrderStats',
            'favoritesStats',
            'monthLabels',
            'purchaseData',
            'paymentData',
            'paymentMethods',
            'topProducts',
            'transactions',
            'recentPayments',
            'recentPurchaseReturns',
            'recentPurchaseOrders',
            'recentFavoritePurchases'
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

        $transactionsQuery = Transaction::where('type', 'purchases');

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

        $columns = Transaction::getsPurchasesColumns();
        $poes = Transaction::where('type', 'purchases-order')->where('po_status', '<>', 'completed')->get();

        $Latest_event = Actions::where('user_id', Auth::user()->id)->where('type', 'create_po')->first();
        if (! $Latest_event) {
            $actionUtil = new ActionUtil;
            $Latest_event = $actionUtil->saveOrUpdateAction('create_po', 'add_sell', 'create-purchases-invoice');
        }
        $clients = Contact::where('business_type', 'supplier')->get();
        $page = 'purchases';

        return view('purchases::purchases.index', compact('columns', 'page', 'clients', 'Latest_event', 'poes', 'transaction'));
    }

    public function favorites(Request $request)
    {
        $transactionsQuery = Transaction::whereIn('type', ['purchases', 'purchases-return', 'purchases-order'])
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
        $columns = Transaction::getsPurchasesColumns();
        $clients = Contact::where('business_type', 'supplier')->get();
        $page = 'purchases';

        return view('purchases::purchases.favorites', compact('columns', 'clients', 'transaction', 'page'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $actionUtil = new ActionUtil;
        $actionUtil->saveOrUpdateAction('create_po', 'add_sell', 'create-purchases-invoice');

        $clients = Contact::where('business_type', 'supplier')->get();
        // $taxes = Tax::all();
        $payment_terms = SalesUtile::paymentTerms();
        $paymentMethods = SalesUtile::paymentMethods();
        $orderStatuses = SalesUtile::orderStatuses();
        $accounts = AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();
        $establishments = Establishment::where('is_main', 0)->get();
        $countries = Country::all();
        $transaction = null;
        $isPurchaseOrderForm = false;
        $convertingFromPo = false;
        $isDuplicate = false;

        $duplicateFrom = (int) $request->input('duplicate_from', 0);
        $poInputId = (int) $request->input('po_id', 0);

        $purchaseLineRelations = [
            'purchases_lines' => fn ($q) => $q->orderBy('id'),
            'purchases_lines.product.unitTransfers' => fn ($q) => $q->whereNull('unit2'),
        ];

        if ($duplicateFrom > 0) {
            $src = Transaction::with($purchaseLineRelations)->find($duplicateFrom);
            if ($src && $src->type === 'purchases') {
                $transaction = $src;
                $isDuplicate = true;
            }
        } elseif ($poInputId > 0) {
            $transaction = Transaction::with($purchaseLineRelations)->find($poInputId);
            if ($transaction && $transaction->type === 'purchases-order') {
                $convertingFromPo = true;
            }
        }

        $taxes = Tax::all();
        $settings = Setting::getNotesAndTermsConditions();
        $invoicePrecheckConfig = $this->buildPurchasesInvoicePrecheckConfig();

        $products = Product::where('active', 1)->take(25)->get();
        $Latest_event = Actions::where('user_id', Auth::user()->id)->where('type', 'save_purchases')->first();
        if (! $Latest_event) {
            $actionUtil = new ActionUtil;
            $Latest_event = $actionUtil->saveOrUpdateAction('save_purchases', 'save_purchases', 'save');
        }

        return view('purchases::purchases.create', compact('clients', 'settings', 'Latest_event', 'establishments', 'isPurchaseOrderForm', 'convertingFromPo', 'taxes', 'transaction', 'countries', 'payment_terms', 'orderStatuses', 'products', 'paymentMethods', 'accounts', 'cost_centers', 'invoicePrecheckConfig', 'isDuplicate'));
    }

    private function buildPurchasesInvoicePrecheckConfig(): array
    {
        $missing = [];
        $purchasesAccountId = AccountsRoting::where('type', 'purchases_purchase')->value('account_id');
        if (! $purchasesAccountId) {
            $missing[] = __('accounting::lang.purchase');
        }
        if (! AccountsRoting::where('type', 'purchases_purchase_return')->value('account_id')) {
            $missing[] = __('accounting::lang.purchase_return');
        }
        if (! AccountsRoting::where('type', 'purchases_vat_calculation')->value('account_id')) {
            $missing[] = app()->getLocale() === 'ar' ? 'حساب ضريبة المشتريات' : 'Purchases VAT account';
        }
        if (! AccountsRoting::where('type', 'purchases_earned_discount')->value('account_id')) {
            $missing[] = __('accounting::lang.earned_discount');
        }
        if (Setting::isPerpetualInventory()) {
            $inventoryAccountId = AccountingAccount::query()
                ->where('gl_code', '1105')
                ->orWhere('account_category', 'inventory')
                ->value('id');
            if (! $inventoryAccountId && ! $purchasesAccountId) {
                $missing[] = app()->getLocale() === 'ar' ? 'حساب المخزون' : 'Inventory account';
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
        $actionUtil = new ActionUtil;
        $actionUtil->saveOrUpdateAction('save_purchases', 'save_purchases', $request->action);
        $accountUtil = new AccountingUtil;

        // try {
        $transactionUtil = new TransactionUtils;
        // return $request;
        DB::beginTransaction();
        $ref_no = SalesUtile::generateReferenceNumber('purchases');

        $invoiced_discount_type = $request->invoice_discount ? $request->invoiced_discount_type : null;
        $main_establishment = Establishment::notMain()->active()->first();

        $establishment_id = $request->storehouse;
        if ($request->storehouse == $main_establishment->id) {
            $establishment_id = $main_establishment->id;
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
        $po_id = null;
        if ($request->po_id) {
            $po_id = $request->po_id;
        }
        $transaction = Transaction::create([
            'type' => 'purchases',
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
            'parent_id' => $po_id,
        ]);

        $products = json_decode(json_encode($request->products));

        foreach ($products as $product) {
            $discount_type = $product->discount ? $product->discount_type : null;
            $resolvedUnitId = $this->resolvePurchaseUnitId((int) $product->products_id, $product->unit ?? null);
            TransactionePurchasesLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->products_id,
                'qyt' => $product->qty,
                'unit_id' => $resolvedUnitId,
                'unit_price_before_discount' => $product->unit_price,
                'unit_price' => $product->unit_price,
                'discount_type' => $discount_type,
                'discount_amount' => $product->discount,
                'unit_price_inc_tax' => $product->total_after_vat,
                'tax_id' => $product->tax_vat,
                'tax_value' => $product->vat_value,
                'total_before_vat' => $product->total_before_vat,
            ]);
        }

        if ($po_id) {
            $this->updatePurchaseOrderStatus($po_id);
        }

        // dd($result);

        app(\Modules\Inventory\Services\InventoryCostingService::class)->processTransaction($transaction);

        if ($request->paid_amount) {
            $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
        } else {
            FiscalPeriodGatekeeper::assertPostable($transaction->transaction_date ?? now());

            $acc_trans_mapping = new AccountingAccTransMapping;
            $ref_number = $accountUtil->generateReferenceNumber('journal_entry');
            $acc_trans_mapping->ref_no = $ref_number;
            $acc_trans_mapping->note = 'مشتريات';
            $acc_trans_mapping->type = 'journal_entry';
            $acc_trans_mapping->created_by = Auth::user()->id;
            $acc_trans_mapping->is_manual = 0;
            $acc_trans_mapping->operation_date = Carbon::parse($transaction->transaction_date ?? now())->format('Y-m-d H:i:s');
            $acc_trans_mapping->save();
            $acc_trans_mapping_id = $acc_trans_mapping->id;

            $purchases_purchase = AccountsRoting::where('type', 'purchases_purchase')->first();
            $purchases_vat_calculation = AccountsRoting::where('type', 'purchases_vat_calculation')->first();
            $inventoryAssetAccountId = PerpetualInventoryAccountResolver::resolveInventoryAssetAccountId(
                isset($transaction->establishment_id) ? (int) $transaction->establishment_id : null
            );
            $purchasesTargetAccountId = Setting::isPerpetualInventory()
                ? ($inventoryAssetAccountId ?? $purchases_purchase?->account_id)
                : ($purchases_purchase?->account_id);
            if (! $purchasesTargetAccountId) {
                throw ValidationException::withMessages([
                    'accounting' => app()->getLocale() === 'ar'
                        ? 'مسار حساب المشتريات/المخزون غير مضبوط. يرجى ضبط توجيه الحسابات للمشتريات.'
                        : 'Purchases/Inventory account routing is not configured. Please configure Accounts Routing.',
                ]);
            }
            if (! $purchases_vat_calculation || ! $purchases_vat_calculation->account_id) {
                throw ValidationException::withMessages([
                    'accounting' => app()->getLocale() === 'ar'
                        ? 'حساب ضريبة المشتريات غير مضبوط في توجيه الحسابات.'
                        : 'Purchases VAT account routing is not configured.',
                ]);
            }

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
                'credit',
                $transactionPayment,
                $transaction,
                $acc_trans_mapping_id,
                $request
            );

            $transactionPayment->account_id = $purchasesTargetAccountId;
            $transactionPayment->amount = $transaction->total_before_tax;

            $accountUtil->saveAccountRouteTransaction(
                'debit',
                $transactionPayment,
                $transaction,
                $acc_trans_mapping_id,
                $request
            );

            $transactionPayment->account_id = $purchases_vat_calculation->account_id;
            $transactionPayment->amount = $transaction->tax_amount;

            $accountUtil->saveAccountRouteTransaction(
                'debit',
                $transactionPayment,
                $transaction,
                $acc_trans_mapping_id,
                $request
            );

            AutoJournalGuard::assertBalanced((int) $acc_trans_mapping_id);
        }

        $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

        DB::commit();
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

        if ($request->action == 'save_print') {
            return redirect()->route('transaction-print', $transaction->id)->with($status, $msg);
        } elseif ($request->action == 'save_add') {
            return redirect()->route('create-purchases-invoice')->with($status, $msg);
        } else {
            return redirect()->route('purchase-invoices')->with($status, $msg);
        }
        // } catch (Exception $e) {
        //     DB::rollBack();
        //     return redirect()->route('purchase-invoices')->with('error', __('messages.something_went_wrong'));
        // }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('purchases::show');
    }

    public function updatePurchaseOrderStatus($po_id)
    {
        $poTransaction = Transaction::find($po_id);

        if (! $poTransaction) {
            return;
        }

        $poLines = TransactionePurchasesLine::where('transaction_id', $po_id)->get();

        if ($poLines->isEmpty()) {
            $poTransaction->po_status = 'pending';
            $poTransaction->save();

            return;
        }

        $invoiceIds = Transaction::where('parent_id', $po_id)->pluck('id');
        $invoiceLines = TransactionePurchasesLine::whereIn('transaction_id', $invoiceIds)->get();

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

    private function resolvePurchaseUnitId(int $productId, $rawUnit): ?int
    {
        if (is_null($rawUnit) || $rawUnit === '') {
            $defaultTransferId = UnitTransfer::query()
                ->where('product_id', $productId)
                ->orderByDesc('default')
                ->value('id');
            if ($defaultTransferId) {
                return (int) $defaultTransferId;
            }

            throw ValidationException::withMessages([
                'products' => app()->getLocale() === 'ar'
                    ? "لم يتم اختيار وحدة للصنف #{$productId} ولا يوجد وحدة افتراضية معرفة له."
                    : "Unit is missing for product #{$productId} and no default unit is configured.",
            ]);
        }

        if (is_numeric($rawUnit)) {
            return (int) $rawUnit;
        }

        $rawUnit = trim((string) $rawUnit);
        $unitTransferId = UnitTransfer::query()
            ->where('product_id', $productId)
            ->where(function ($q) use ($rawUnit) {
                $q->where('unit1', $rawUnit)->orWhere('unit2', $rawUnit);
            })
            ->orderByDesc('default')
            ->value('id');

        if ($unitTransferId) {
            return (int) $unitTransferId;
        }

        throw ValidationException::withMessages([
            'products' => app()->getLocale() === 'ar'
                ? "تعذر تحديد وحدة الصنف #{$productId}. يرجى اختيار وحدة صحيحة من القائمة."
                : "Unable to resolve unit for product #{$productId}. Please choose a valid unit from list.",
        ]);
    }
}
