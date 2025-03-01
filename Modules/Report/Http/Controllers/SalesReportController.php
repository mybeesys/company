<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionPayments;
use Modules\General\Models\TransactionSellLine;
use Modules\Report\Utils\ReportTransactionsUtile;

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
                ->where('t.status', 'final')
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


}