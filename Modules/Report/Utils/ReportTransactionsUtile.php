<?php

namespace Modules\Report\Utils;


use Illuminate\Support\Facades\DB;
use Modules\General\Http\Controllers\TransactionController;
use Modules\Purchases\Http\Controllers\PurchasesController;
use Yajra\DataTables\Facades\DataTables;

class ReportTransactionsUtile
{

    public function getSalesSummary()
    {
        return DB::table('transactions as t')
            ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
            ->where('t.type', 'sell')
            ->where('t.status', 'approved')
            ->selectRaw("
            SUM(t.final_total) AS total_sales,
            SUM(t.discount_amount) AS total_discounts,
            SUM(t.tax_amount) AS total_taxes,
            SUM(tp.amount) AS total_payments,
            (SUM(t.final_total) - SUM(t.discount_amount)) AS net_sales,
            SUM(CASE WHEN tp.payment_type = 'due' THEN tp.amount ELSE 0 END) AS total_dues
        ")
            ->first();
    }

    public static function getsProductSalesColumns()
    {
        return [
            ["class" => "text-start min-w-150px", "name" => "product_name"],
            ["class" => "text-start min-w-120px", "name" => "category"],
            ["class" => "text-start min-w-150px", "name" => "subcategory"],
            ["class" => "text-start min-w-100px", "name" => "price"],
            ["class" => "text-start min-w-100px", "name" => "SKU"],
            ["class" => "text-start min-w-150px", "name" => "customer"],
            ["class" => "text-start min-w-150px", "name" => "ref_no"],
            ["class" => "text-start min-w-150px", "name" => "transaction_date"],
            ["class" => "text-start min-w-100px", "name" => "unit_price"],
            ["class" => "text-start min-w-150px", "name" => "unit_sale_price"],
            ["class" => "text-start min-w-100px", "name" => "sell_qty"],
            ["class" => "text-start min-w-100px", "name" => "discount_amount"],
            ["class" => "text-start min-w-100px", "name" => "tax_value"],
            ["class" => "text-start min-w-120px", "name" => "subtotal"],
        ];
    }

    public static function getsProductPurchasesColumns()
    {
        return [
            ["class" => "text-start min-w-150px", "name" => "product_name"],
            ["class" => "text-start min-w-100px", "name" => "price"],
            ["class" => "text-start min-w-100px", "name" => "SKU"],
            ["class" => "text-start min-w-150px", "name" => "supplier"],
            ["class" => "text-start min-w-150px", "name" => "ref_no"],
            ["class" => "text-start min-w-150px", "name" => "transaction_date"],
            ["class" => "text-start min-w-100px", "name" => "unit_price"],
            ["class" => "text-start min-w-150px", "name" => "unit_sale_price"],
            ["class" => "text-start min-w-100px", "name" => "sell_qty"],
            ["class" => "text-start min-w-100px", "name" => "discount_amount"],
            ["class" => "text-start min-w-100px", "name" => "tax_value"],
            ["class" => "text-start min-w-120px", "name" => "subtotal"],
        ];
    }

    public function salesPaymentReportColumns()
    {
        return [
            ["class" => "text-start min-w-150px", "name" => "reference_number"],
            ["class" => "text-start min-w-150px", "name" => "customer"],
            ["class" => "text-start min-w-150px", "name" => "payment_date"],
            ["class" => "text-start min-w-150px", "name" => "paid_amount"],
            ["class" => "text-start min-w-150px", "name" => "payment_method"],
            ["class" => "text-start min-w-150px", "name" => "sales"],

        ];
    }

    public function purchasePaymentReportColumns()
    {
        return [
            ["class" => "text-start min-w-150px", "name" => "reference_number"],
            ["class" => "text-start min-w-150px", "name" => "supplier"],
            ["class" => "text-start min-w-150px", "name" => "payment_date"],
            ["class" => "text-start min-w-150px", "name" => "paid_amount"],
            ["class" => "text-start min-w-150px", "name" => "payment_method"],
            ["class" => "text-start min-w-150px", "name" => "purchases"],

        ];
    }

    public static function getProductSalesReport($transactions)
    {
        return DataTables::of($transactions)
            ->editColumn('product_name', function ($row) {
                return app()->getLocale() == 'ar' ? $row->product_name_ar : $row->product_name_en;
            })
            ->editColumn('category', function ($row) {
                return optional($row->category)->{'name_' . app()->getLocale()} ?? '--';
            })
            ->editColumn('subcategory', function ($row) {
                return optional($row->subcategory)->{'name_' . app()->getLocale()} ?? '--';
            })
            ->editColumn('price', function ($row) {
                return number_format($row->product_price, 2);
            })
            ->editColumn('SKU', function ($row) {
                return $row->product_SKU ?? '--';
            })
            ->editColumn('customer', function ($row) {
                return $row->customer ?? '--';
            })
            ->editColumn('transaction_date', function ($row) {
                return $row->transaction_date ?? '--';
            })->editColumn('ref_no', function ($row) {
                return $row->ref_no ?? '--';
            })
            ->editColumn('unit_price', function ($row) {
                return number_format($row->unit_price, 2);
            })
            ->editColumn('unit_sale_price', function ($row) {
                return number_format($row->unit_sale_price, 2);
            })
            ->editColumn('sell_qty', function ($row) {
                return $row->sell_qty . '  ' . $row->unit;
            })
            ->editColumn('discount_amount', function ($row) {
                return $row->discount_amount ? number_format($row->discount_amount, 2) : __('report::fields.no_discount');
            })
            ->editColumn('tax_value', function ($row) {
                return number_format($row->tax_value, 2);
            })
            ->editColumn('subtotal', function ($row) {
                return number_format($row->subtotal, 2);
            })
            ->editColumn('actions', function ($row) {
                return "--";
            })
            ->rawColumns(['product_name', 'actions', 'discount_amount'])
            ->make(true);
    }

    public static function getProductPurchasesReport($transactions)
    {
        return DataTables::of($transactions)
            ->editColumn('product_name', function ($row) {
                return app()->getLocale() == 'ar' ? $row->product_name_ar : $row->product_name_en;
            })
            ->editColumn('price', function ($row) {
                return number_format($row->product_price, 2);
            })
            ->editColumn('SKU', function ($row) {
                return $row->product_SKU ?? '--';
            })
            ->editColumn('customer', function ($row) {
                return $row->customer ?? '--';
            })
            ->editColumn('transaction_date', function ($row) {
                return $row->transaction_date ?? '--';
            })->editColumn('ref_no', function ($row) {
                return $row->ref_no ?? '--';
            })
            ->editColumn('unit_price', function ($row) {
                return number_format($row->unit_price, 2);
            })
            ->editColumn('unit_sale_price', function ($row) {
                return number_format($row->unit_sale_price, 2);
            })
            ->editColumn('sell_qty', function ($row) {
                return $row->sell_qty . '  ' . $row->unit;
            })
            ->editColumn('discount_amount', function ($row) {
                return $row->discount_amount ? number_format($row->discount_amount, 2) : __('report::fields.no_discount');
            })
            ->editColumn('tax_value', function ($row) {
                return number_format($row->tax_value, 2);
            })
            ->editColumn('subtotal', function ($row) {
                return number_format($row->subtotal, 2);
            })
            ->editColumn('actions', function ($row) {
                return "--";
            })
            ->rawColumns(['product_name', 'actions', 'discount_amount'])
            ->make(true);
    }

    public function purchasePaymentReportTable($query)
    {

        return Datatables::of($query)
            ->editColumn('ref_no', function ($row) {
                if (! empty($row->ref_no)) {
                    return '<a data-href="' . action([TransactionController::class, 'show'], [$row->transaction_id])
                        . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->ref_no . '</a>';
                } else {
                    return '';
                }
            })

            ->editColumn('supplier', function ($row) {

                return $row->supplier;
            })
            ->editColumn('paid_on', function ($row) {

                return $row->paid_on;
            })
            ->editColumn('payment_ref_no', function ($row) {

                return $row->payment_ref_no;
            })




            ->editColumn('method', function ($row) {

                return __('sales::lang.' . $row->method);;
            })
            ->editColumn('amount', function ($row) {
                return '<span class="paid-amount" data-orig-value="' . $row->amount . '">' .
                    number_format($row->amount, 2) . '</span>';
            })

            ->editColumn('actions', function ($row) {
                return "--";
            })
            ->rawColumns(['ref_no', 'amount', 'method', 'action', 'supplier'])
            ->make(true);
    }
}
