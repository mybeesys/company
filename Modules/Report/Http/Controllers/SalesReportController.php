<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Establishment\Models\Establishment;
use Modules\General\Http\Controllers\TransactionController;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionPayments;
use Modules\General\Models\TransactionSellLine;
use Modules\Report\Utils\ReportTransactionsUtile;
use Modules\Report\Utils\TransactionUtile;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Employee\Models\Employee;
use Modules\Establishment\Models\EstPos;
use Modules\General\Models\CashRegister;
use Modules\General\Models\PaymentMethod;
use Modules\Product\Models\Product;

class SalesReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $transactionUtile = new ReportTransactionsUtile();
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
                'message' => 'Error fetching branches: ' . $e->getMessage(),
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
                'message' => 'Error fetching suppliers: ' . $e->getMessage(),
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
                'message' => 'Error fetching suppliers: ' . $e->getMessage(),
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
                'message' => 'Error fetching devices: ' . $e->getMessage(),
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
                'message' => 'Error fetching products: ' . $e->getMessage(),
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
                'sales_count' => $counts
            ]
        ]);
    }

    public function getproductSellReport(Request $request)
    {
        $transactionUtile = new ReportTransactionsUtile();


        if ($request->ajax()) {
            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )->join('cs_contacts as c', 't.contact_id', '=', 'c.id')
                ->join('product_products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
                ->leftjoin('taxes', 'transaction_sell_lines.tax_id', '=', 'taxes.id')
                ->leftjoin('est_establishments as e', 't.establishment_id', '=', 'e.id')
                ->leftjoin('product_unit_transfer as u', 'transaction_sell_lines.unit_id', '=', 'u.id')
                ->where('t.type', 'sell')
                ->where('t.status', 'approved')
                ->select(
                    'p.name_ar as product_name_ar',
                    'p.name_en as product_name_en',
                    'p.category_id  as product_category_id ',
                    'p.subcategory_id  as product_subcategory_id ',
                    'p.price as product_price',
                    'p.SKU as product_SKU',
                    'c.name as customer',
                    't.id as transaction_id',
                    't.ref_no',
                    't.created_at',
                    't.transaction_date as transaction_date',
                    'transaction_sell_lines.unit_price_before_discount as unit_price',
                    'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                    DB::raw('(transaction_sell_lines.qyt) as sell_qty'),
                    'transaction_sell_lines.discount_type as discount_type',
                    'transaction_sell_lines.discount_amount as discount_amount',
                    'transaction_sell_lines.tax_value',
                    'taxes.name as tax',
                    'u.unit1  as unit',
                    DB::raw('((transaction_sell_lines.qyt) * transaction_sell_lines.unit_price_inc_tax) as subtotal'),
                    DB::raw("CASE
                WHEN '" . app()->getLocale() . "' = 'ar' THEN e.name
                ELSE e.name_en
              END as establishment_name"),
                );
            if ($request->has('branch_id')) {
                $branchIds = collect($request->input('branch_id'))->filter()->values()->toArray();
                if (!empty($branchIds)) {
                    $query->whereIn('t.establishment_id', $branchIds);
                }
            }

            if ($request->has('customer_id')) {
                $supplierIds = collect($request->input('customer_id'))->filter()->values()->toArray();
                if (!empty($supplierIds)) {
                    $query->whereIn('t.contact_id', $supplierIds);
                }
            }

            if ($request->has('product_id')) {
                $paymentMethods = collect($request->input('product_id'))->filter()->values()->toArray();
                if (!empty($paymentMethods)) {
                    $query->whereIn('p.id', $paymentMethods);
                }
            }

            if (!empty($request->input('sale_date_range'))) {
                $dateRange = explode(' - ', $request->input('sale_date_range'));
                if (count($dateRange) === 2) {
                    $from = date('Y-m-d', strtotime($dateRange[0]));
                    $to = date('Y-m-d', strtotime($dateRange[1]));
                    $query->whereBetween('t.transaction_date', [$from, $to]);
                }
            }

            $results = $query->orderBy('created_at', 'desc')->get();

            return  $transactionUtile->getProductSalesReport($results);
        }

        $columns = $transactionUtile->getsProductSalesColumns();
        return view('report::sales.indexProductSalesReport')
            ->with(compact(
                'columns'
            ));
    }

    public function getproductPurchaseReport(Request $request)
    {

        $transactionUtile = new ReportTransactionsUtile();


        if ($request->ajax()) {

            $query = TransactionePurchasesLine::join(
                'transactions as t',
                'transactione_purchases_lines.transaction_id',
                '=',
                't.id'
            )

                ->join('cs_contacts as c', 't.contact_id', '=', 'c.id')
                ->join('product_products as p', 'transactione_purchases_lines.product_id', '=', 'p.id')
                ->leftjoin('taxes', 'transactione_purchases_lines.tax_id', '=', 'taxes.id')
                ->leftjoin('est_establishments as e', 't.establishment_id', '=', 'e.id')
                ->leftjoin('product_unit_transfer as u', 'transactione_purchases_lines.unit_id', '=', 'u.id')
                ->where('t.type', 'purchases')
                ->select(
                    'p.name_ar as product_name_ar',
                    'p.name_en as product_name_en',
                    'p.category_id  as product_category_id ',
                    'p.subcategory_id  as product_subcategory_id ',
                    'p.price as product_price',
                    'p.SKU as product_SKU',
                    'c.name as customer',
                    't.id as transaction_id',
                    't.ref_no',
                    't.created_at',
                    't.transaction_date as transaction_date',
                    'transactione_purchases_lines.unit_price_before_discount as unit_price',
                    'transactione_purchases_lines.unit_price_inc_tax as unit_sale_price',
                    DB::raw('(transactione_purchases_lines.qyt) as sell_qty'),
                    'transactione_purchases_lines.discount_type as discount_type',
                    'transactione_purchases_lines.discount_amount as discount_amount',
                    'transactione_purchases_lines.tax_value',
                    'taxes.name as tax',
                    'u.unit1  as unit',
                    DB::raw('((transactione_purchases_lines.qyt) * transactione_purchases_lines.unit_price_inc_tax) as subtotal'),
                    DB::raw("CASE
                WHEN '" . app()->getLocale() . "' = 'ar' THEN e.name
                ELSE e.name_en
              END as establishment_name"),
                );
            if ($request->has('branch_id')) {
                $branchIds = collect($request->input('branch_id'))->filter()->values()->toArray();
                if (!empty($branchIds)) {
                    $query->whereIn('t.establishment_id', $branchIds);
                }
            }

            if ($request->has('supplier_id')) {
                $supplierIds = collect($request->input('supplier_id'))->filter()->values()->toArray();
                if (!empty($supplierIds)) {
                    $query->whereIn('t.contact_id', $supplierIds);
                }
            }

            if ($request->has('product_id')) {
                $paymentMethods = collect($request->input('product_id'))->filter()->values()->toArray();
                if (!empty($paymentMethods)) {
                    $query->whereIn('p.id', $paymentMethods);
                }
            }

            if (!empty($request->input('sale_date_range'))) {
                $dateRange = explode(' - ', $request->input('sale_date_range'));
                if (count($dateRange) === 2) {
                    $from = date('Y-m-d', strtotime($dateRange[0]));
                    $to = date('Y-m-d', strtotime($dateRange[1]));
                    $query->whereBetween('t.transaction_date', [$from, $to]);
                }
            }

            $results = $query->orderBy('created_at', 'desc')->get();
            return  $transactionUtile->getProductPurchasesReport($results);
        }

        $columns = $transactionUtile->getsProductPurchasesColumns();
        return view('report::sales.product-purchase-report')
            ->with(compact(
                'columns'
            ));
    }


    public function purchasePaymentReport(Request $request)
    {
        $transactionUtile = new ReportTransactionsUtile();

        if ($request->ajax()) {

            $query = TransactionPayments::leftjoin('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')

                ->leftjoin('est_establishments as e', 't.establishment_id', '=', 'e.id')
                ->leftjoin('est_pos as d', 't.device_id', '=', 'd.id')
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
                    /*'u.name as cashier',*/
                    DB::raw("CASE
                WHEN '" . app()->getLocale() . "' = 'ar' THEN e.name
                ELSE e.name_en
              END as establishment_name"),
                    'd.name as device_name',
                    'transaction_payments.amount',
                    'transaction_payments.method',
                    'transaction_payments.paid_on',
                    'transaction_payments.payment_ref_no',
                    't.id as transaction_id',
                    't.final_total',
                    't.ref_no',
                    't.created_at',
                    't.payment_status',
                    't.id as transaction_id',
                    'transaction_payments.id as DT_RowId'
                );
            if ($request->has('branch_id')) {
                $branchIds = collect($request->input('branch_id'))->filter()->values()->toArray();
                if (!empty($branchIds)) {
                    $query->whereIn('t.establishment_id', $branchIds);
                }
            }
            if ($request->has('device_id')) {
                $branchIds = collect($request->input('device_id'))->filter()->values()->toArray();
                if (!empty($branchIds)) {
                    $query->whereIn('t.device_id', $branchIds);
                }
            }
            if ($request->has('cashier_id')) {
                $query->where('t.created_by', $request->input('cashier_id'));
            }

            if ($request->has('supplier_id')) {
                $supplierIds = collect($request->input('supplier_id'))->filter()->values()->toArray();
                if (!empty($supplierIds)) {
                    $query->whereIn('t.contact_id', $supplierIds);
                }
            }

            if ($request->has('payment_method')) {
                $paymentMethods = collect($request->input('payment_method'))->filter()->values()->toArray();
                if (!empty($paymentMethods)) {
                    $query->whereIn('transaction_payments.method', $paymentMethods);
                }
            }

            if ($request->has('payment_status')) {
                $paymentStatuses = collect($request->input('payment_status'))->filter()->values()->toArray();
                if (!empty($paymentStatuses)) {
                    $query->whereIn('t.payment_status', $paymentStatuses);
                }
            }

            if (!empty($request->input('payment_date_range'))) {
                $dateRange = explode(' - ', $request->input('payment_date_range'));
                if (count($dateRange) === 2) {
                    $from = date('Y-m-d', strtotime($dateRange[0]));
                    $to = date('Y-m-d', strtotime($dateRange[1]));
                    $query->whereBetween('transaction_payments.paid_on', [$from, $to]);
                }
            }

            $results = $query->orderBy('created_at', 'desc')->get();

            return $transactionUtile->purchasePaymentReportTable($results);
        }

        $columns = $transactionUtile->purchasePaymentReportColumns();
        return view('report::sales.purchase_payment_report')
            ->with(compact('columns'));
    }

    public function salesPaymentReport(Request $request)
    {


        $transactionUtile = new ReportTransactionsUtile();


        if ($request->ajax()) {

            $query =
                $query = TransactionPayments::leftjoin('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')

                ->leftjoin('est_establishments as e', 't.establishment_id', '=', 'e.id')
                ->leftjoin('est_pos as d', 't.device_id', '=', 'd.id')
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
                WHEN '" . app()->getLocale() . "' = 'ar' THEN e.name
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
                    // 'transaction_no',
                    'transaction_payments.id as DT_RowId'
                );
            if ($request->has('branch_id')) {
                $branchIds = collect($request->input('branch_id'))->filter()->values()->toArray();
                if (!empty($branchIds)) {
                    $query->whereIn('t.establishment_id', $branchIds);
                }
            }
            if ($request->has('device_id')) {
                $branchIds = collect($request->input('device_id'))->filter()->values()->toArray();
                if (!empty($branchIds)) {
                    $query->whereIn('t.device_id', $branchIds);
                }
            }
            if ($request->has('cashier_id')) {
                $query->where('t.created_by', $request->input('cashier_id'));
            }

            if ($request->has('customer_id')) {
                $supplierIds = collect($request->input('customer_id'))->filter()->values()->toArray();
                if (!empty($supplierIds)) {
                    $query->whereIn('t.contact_id', $supplierIds);
                }
            }

            if ($request->has('payment_method')) {
                $paymentMethods = collect($request->input('payment_method'))->filter()->values()->toArray();
                if (!empty($paymentMethods)) {
                    $query->whereIn('transaction_payments.method', $paymentMethods);
                }
            }

            if ($request->has('payment_status')) {
                $paymentStatuses = collect($request->input('payment_status'))->filter()->values()->toArray();
                if (!empty($paymentStatuses)) {
                    $query->whereIn('t.payment_status', $paymentStatuses);
                }
            }

            if (!empty($request->input('payment_date_range'))) {
                $dateRange = explode(' - ', $request->input('payment_date_range'));
                if (count($dateRange) === 2) {
                    $from = date('Y-m-d', strtotime($dateRange[0]));
                    $to = date('Y-m-d', strtotime($dateRange[1]));
                    $query->whereBetween('transaction_payments.paid_on', [$from, $to]);
                }
            }

            $results = $query->orderBy('created_at', 'desc')->get();


            return $transactionUtile->purchasePaymentReportTable($query);
        }

        $columns = $transactionUtile->salesPaymentReportColumns();
        return view('report::sales.sell_payment_report')
            ->with(compact('columns'));
    }

    public function getProfitLoss(Request $request)
    {
        $transactionUtile = new TransactionUtile();

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
        $transactionUtile = new TransactionUtile();

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $location_id = $request->get('location_id');

            // return $request->date_range;
            $dueDateRange = trim($request->date_range);
            $dates = explode(' إلى ', $dueDateRange);
            if ($request->date_range) {
                $start_date =   $dates[0];
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
                <td>' . __('report::general.total_purchase_inc_tax') . '</td>
                <td>' . ($purchase_details['total_purchase_inc_tax'] ?? 0) . '</td>
            </tr>
            <tr>
                <td>' . __('report::general.total_purchase_return') . '</td>
                <td>' . ($total_purchase_return_inc_tax ?? 0) . '</td>
            </tr>
            <tr>
                <td>' . __('report::general.purchase_due') . '</td>
                <td>' . ($purchase_details['purchase_due'] ?? 0) . '</td>
            </tr>';


            $sales_data = '
            <tr>
                <td>' . __('report::general.total_sell_inc_tax') . '</td>
                <td>' . ($sell_details['total_sell_inc_tax'] ?? 0) . '</td>
            </tr>
            <tr>
                <td>' . __('report::general.total_sell_return') . '</td>
                <td>' . ($total_sell_return_inc_tax ?? 0) . '</td>
            </tr>
            <tr>
                <td>' . __('report::general.invoice_due') . '</td>
                <td>' . ($sell_details['invoice_due'] ?? 0) . '</td>
            </tr>';


            $difference = [
                'total' => $sell_details['total_sell_inc_tax'] - $total_sell_return_inc_tax - ($purchase_details['total_purchase_inc_tax'] - $total_purchase_return_inc_tax),
                'due' => $sell_details['invoice_due'] - $purchase_details['purchase_due'],
            ];

            return response()->json([
                'purchase_data' => $purchase_data,
                'sales_data' => $sales_data,
                'difference' => $difference
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
        $query->addSelect(DB::raw("
            SUM(
                (transaction_sell_lines.qyt - COALESCE(TPL.qyt, 0)) *
                (transaction_sell_lines.unit_price_inc_tax - COALESCE(TPL.unit_price_inc_tax, 0))
            ) AS gross_profit
        "));

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
                if (!isset($profits[$day])) {
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

                return    $profit = $row->gross_profit - $discount;
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
                return '<a href="' . action([TransactionController::class, 'show'], [$row->transaction_id])
                    . '"  data-container=".view_modal" class="btn-modal">' . $row->ref_no . '</a>';
            });
        }

        return $datatable->rawColumns(['gross_profit', 'category', 'customer', 'ref_no'])
            ->make(true);
    }
    public function productInventoryReport(Request $request)
    {
        $transactionUtile = new ReportTransactionsUtile();

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
                    DB::raw("CASE
                        WHEN sl.id IS NOT NULL THEN sl.qyt
                        ELSE pl.qyt
                    END as quantity"),
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
                if (!empty($branchIds)) {
                    $query->whereIn('t.establishment_id', $branchIds);
                }
            }
            if ($request->has('product_id')) {
                $products = collect($request->input('product_id'))->filter()->values()->toArray();
                if (!empty($products)) {
                    $query->whereIn('p.id', $products);
                }
            }
            if ($request->has('process_type')) {
                $processType = collect($request->input('process_type'))->filter()->values()->toArray();
                if (!empty($processType)) {
                    $query->whereIn('t.type', $processType);
                }
            }

            if (!empty($request->input('inventory_date_range'))) {
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
        $transactionUtile = new ReportTransactionsUtile();

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
                    WHEN '" . app()->getLocale() . "' = 'ar' THEN e.name
                    ELSE e.name_en
                END as establishment_name"),
                    DB::raw("
                    (
                        SELECT COALESCE(
                            MAX(
                                CASE
                                    WHEN pu_disp_sub.unit2 IS NOT NULL AND '" . app()->getLocale() . "' = 'ar' THEN pu_disp_sub.unit1
                                    WHEN pu_disp_sub.unit2 IS NOT NULL AND '" . app()->getLocale() . "' = 'en' THEN pu_disp_sub.unit1
                                    ELSE NULL
                                END
                            ),
                            MAX(
                                CASE
                                    WHEN '" . app()->getLocale() . "' = 'ar' THEN pu_disp_sub.unit1
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
                    DB::raw("NULL as counted_quantity"),
                    //     DB::raw("NULL as quantity_on_inventory"),
                    DB::raw("
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
")
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
                if (!empty($branchIds)) {
                    $query->whereIn('t.establishment_id', $branchIds);
                }
            }
            if ($request->has('product_id')) {
                $products = collect($request->input('product_id'))->filter()->values()->toArray();
                if (!empty($products)) {
                    $query->whereIn('p.id', $products);
                }
            }
            if ($request->has('process_type')) {
                $processType = collect($request->input('process_type'))->filter()->values()->toArray();
                if (!empty($processType)) {
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
        $transactionUtile = new ReportTransactionsUtile();
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
                    DB::raw("CASE
                    WHEN sl.id IS NOT NULL THEN sl.qyt
                    ELSE pl.qyt
                END as quantity"),
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
                if (!empty($branchIds)) {
                    $query->whereIn('t.establishment_id', $branchIds);
                }
            }

            if ($request->has('process_type')) {
                $processType = collect($request->input('process_type'))->filter()->values()->toArray();
                if (!empty($processType)) {
                    $query->whereIn('t.type', $processType);
                }
            }

            if (!empty($request->input('inventory_date_range'))) {
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
        if (!$productId) {
            return response()->json(["error" => "No products found"], 404);
        }

        $currentProduct = Product::find($productId);
        $previousProduct = Product::where('id', '<', $productId)->orderBy('id', 'desc')->first();
        $nextProduct = Product::where('id', '>', $productId)->orderBy('id', 'asc')->first();

        $main_establishment = Establishment::notMain()->active()->first();
        $establishmentId = $request->branch_id ?: $main_establishment->id;

        $from = $request->from ? date('Y-m-d', strtotime($request->from)) : '1900-01-01';
        $to   = $request->to   ? date('Y-m-d', strtotime($request->to))   : now();

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
            ->whereHas('transaction', fn($q) => $q->where('establishment_id', $establishmentId)->whereBetween('transaction_date', [$from, $to]))
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
            ->whereHas('transaction', fn($q) => $q->where('establishment_id', $establishmentId)->whereBetween('transaction_date', [$from, $to]))
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

        usort($movements, fn($a, $b) => strtotime($b['transaction_date']) <=> strtotime($a['transaction_date']));

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
                    return '<span data-orig-value="' . $row['total_card_payment'] . '">' . $row['total_card_payment'] . '</span>';
                })
                ->editColumn('total_cheque_payment', function ($row) {
                    return '<span data-orig-value="' . $row['total_cheque_payment'] . '">' . $row['total_cheque_payment'] . '</span>';
                })
                ->editColumn('total_cash_payment', function ($row) {
                    return '<span data-orig-value="' . $row['total_cash_payment'] . '">' . $row['total_cash_payment'] . '</span>';
                })
                ->editColumn('total_bank_transfer_payment', function ($row) {
                    return '<span data-orig-value="' . $row['total_bank_transfer_payment'] . '">' . $row['total_bank_transfer_payment'] . '</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return $row['created_at'] ?? '';
                })
                ->addColumn('total', function ($row) {
                    $total = $row['total_cash_payment'] + $row['total_cheque_payment'] + $row['total_card_payment'] + $row['total_bank_transfer_payment'];
                    return '<span data-orig-value="' . $total . '">' . $total . '</span>';
                })
                ->addColumn('action', function ($row) {
                    // return '';
                    return '<a type="button" href="' . action([SalesReportController::class, 'show'], [$row['id']]) . '" class="btn btn-info " >عرض</a>';
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

            DB::raw("MIN(bl.name) as location_name"),

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
                'cash_registers.created_at'
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
            ->groupBy('cash_registers.id', 'cash_registers.created_at', 'cash_registers.closed_at', 'cash_registers.user_id', 'cash_registers.closing_note', 'cash_registers.establishment_id',  'u.name', 'u.name_en', 'u.email', 'bl.name')
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
}
