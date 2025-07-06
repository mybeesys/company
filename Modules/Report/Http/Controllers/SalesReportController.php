<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\General\Http\Controllers\TransactionController;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionPayments;
use Modules\General\Models\TransactionSellLine;
use Modules\Report\Utils\ReportTransactionsUtile;
use Modules\Report\Utils\TransactionUtile;
use Yajra\DataTables\Facades\DataTables;

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
                    't.transaction_date as transaction_date',
                    'transaction_sell_lines.unit_price_before_discount as unit_price',
                    'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                    DB::raw('(transaction_sell_lines.qyt) as sell_qty'),
                    'transaction_sell_lines.discount_type as discount_type',
                    'transaction_sell_lines.discount_amount as discount_amount',
                    'transaction_sell_lines.tax_value',
                    'taxes.name as tax',
                    'u.unit1  as unit',
                    DB::raw('((transaction_sell_lines.qyt) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
                )->get();

            return  $transactionUtile->getProductSalesReport($query);
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
                    't.transaction_date as transaction_date',
                    'transactione_purchases_lines.unit_price_before_discount as unit_price',
                    'transactione_purchases_lines.unit_price_inc_tax as unit_sale_price',
                    DB::raw('(transactione_purchases_lines.qyt) as sell_qty'),
                    'transactione_purchases_lines.discount_type as discount_type',
                    'transactione_purchases_lines.discount_amount as discount_amount',
                    'transactione_purchases_lines.tax_value',
                    'taxes.name as tax',
                    'u.unit1  as unit',
                    DB::raw('((transactione_purchases_lines.qyt) * transactione_purchases_lines.unit_price_inc_tax) as subtotal')
                )->get();
            return  $transactionUtile->getProductPurchasesReport($query);
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

            $query = TransactionPayments::leftjoin('transactions as t', function ($join) {
                $join->on('transaction_payments.transaction_id', '=', 't.id')
                    ->whereIn('t.type', ['purchases']);
            })
                ->where(function ($q) {
                    $q->whereRaw("(transaction_payments.transaction_id IS NOT NULL AND t.type IN ('purchases'))")
                        ->orWhereRaw("EXISTS(SELECT * FROM transaction_payments as tp JOIN transactions ON tp.transaction_id = transactions.id WHERE transactions.type IN ('purchases'))");
                })

                ->select(
                    DB::raw("IF(transaction_payments.transaction_id IS NULL,
                                (SELECT c.name FROM transactions as ts
                                JOIN cs_contacts as c ON ts.contact_id=c.id),
                                (SELECT CONCAT(COALESCE(c.name, '')) FROM transactions as ts JOIN
                                    cs_contacts as c ON ts.contact_id=c.id
                                    WHERE ts.id=t.id
                                )
                            ) as supplier"),
                    'transaction_payments.amount',
                    'method',
                    'paid_on',
                    'transaction_payments.payment_ref_no',
                    't.ref_no',
                    't.id as transaction_id',
                    // 'transaction_no',
                    'transaction_payments.id as DT_RowId'
                )
                ->get();


            return $transactionUtile->purchasePaymentReportTable($query);
        }

        $columns = $transactionUtile->purchasePaymentReportColumns();
        return view('report::sales.purchase_payment_report')
            ->with(compact('columns'));
    }

    public function salesPaymentReport(Request $request)
    {


        $transactionUtile = new ReportTransactionsUtile();


        if ($request->ajax()) {

            $query = TransactionPayments::leftjoin('transactions as t', function ($join) {
                $join->on('transaction_payments.transaction_id', '=', 't.id')
                    ->whereIn('t.type', ['sell']);
            })
                ->where(function ($q) {
                    $q->whereRaw("(transaction_payments.transaction_id IS NOT NULL AND t.type IN ('sell'))")
                        ->orWhereRaw("EXISTS(SELECT * FROM transaction_payments as tp JOIN transactions ON tp.transaction_id = transactions.id WHERE transactions.type IN ('sell'))");
                })

                ->select(
                    DB::raw("IF(transaction_payments.transaction_id IS NULL,
                                (SELECT c.name FROM transactions as ts
                                JOIN cs_contacts as c ON ts.contact_id=c.id),
                                (SELECT CONCAT(COALESCE(c.name, '')) FROM transactions as ts JOIN
                                    cs_contacts as c ON ts.contact_id=c.id
                                    WHERE ts.id=t.id
                                )
                            ) as supplier"),
                    'transaction_payments.amount',
                    'method',
                    'paid_on',
                    'transaction_payments.payment_ref_no',
                    't.ref_no',
                    't.id as transaction_id',
                    // 'transaction_no',
                    'transaction_payments.id as DT_RowId'
                )
                ->get();


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
                if ($row->discount_type == 'percentage') {
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
                ->leftJoin('transactione_purchases_lines as pl', 't.id', '=', 'pl.transaction_id')
                ->leftJoin('transaction_sell_lines as sl', 't.id', '=', 'sl.transaction_id')
                ->leftJoin('product_products as p', function ($join) {
                    $join->on('pl.product_id', '=', 'p.id')
                        ->orOn('sl.product_id', '=', 'p.id');
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
                    't.type as type'
                )
                ->where(function ($query) {
                    $query->where(function ($subQuery) {
                        $subQuery->whereIn('t.type', ['purchases', 'WASTE', 'PREP', 'sell', 'purchases-return', 'sell-return'])
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
                })
                ->get();

            return $transactionUtile->productInventoryReportTable($query);
        }

        $columns = $transactionUtile->productInventoryReportColumns();
        return view('report::sales.product_inventory_report')
            ->with(compact('columns'));
    }
}
