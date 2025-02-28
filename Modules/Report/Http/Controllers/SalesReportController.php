<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\General\Models\Transaction;
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

            // if (! empty($variation_id)) {
            //     $query->where('transaction_sell_lines.variation_id', $variation_id);
            // }
            // $start_date = $request->get('start_date');
            // $end_date = $request->get('end_date');
            // if (! empty($start_date) && ! empty($end_date)) {
            //     $query->where('t.transaction_date', '>=', $start_date)
            //         ->where('t.transaction_date', '<=', $end_date);
            // }



            // $customer_id = $request->get('customer_id', null);
            // if (! empty($customer_id)) {
            //     $query->where('t.contact_id', $customer_id);
            // }

            // $customer_group_id = $request->get('customer_group_id', null);
            // if (! empty($customer_group_id)) {
            //     $query->leftjoin('customer_groups AS CG', 'c.customer_group_id', '=', 'CG.id')
            //         ->where('CG.id', $customer_group_id);
            // }

            // $category_id = $request->get('category_id', null);
            // if (! empty($category_id)) {
            //     $query->where('p.category_id', $category_id);
            // }

            // $brand_id = $request->get('brand_id', null);
            // if (! empty($brand_id)) {
            //     $query->where('p.brand_id', $brand_id);
            // }

          return  $transactionUtile->getProductSalesReport($query);
        }

        // $business_locations = BusinessLocation::forDropdown($business_id);
        // $customers = Contact::customersDropdown($business_id);
        // $categories = Category::forDropdown($business_id, 'product');
        // $brands = Brands::forDropdown($business_id);
        // $customer_group = CustomerGroup::forDropdown($business_id, false, true);

        $columns = $transactionUtile->getsProductSalesColumns();
        return view('report::sales.indexProductSalesReport')
            ->with(compact(
                'columns'
            ));
    }
}