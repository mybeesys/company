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
            ["class" => "text-start min-w-150px", "name" => "establishment_name"],
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
            ["class" => "text-start min-w-150px", "name" => "establishment_name"],
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
            ["class" => "text-start min-w-150px", "name" => "establishment_name"],
            ["class" => "text-start min-w-150px", "name" => "device_name"],
            ["class" => "text-start min-w-150px", "name" => "customer"],
            ["class" => "text-start min-w-150px", "name" => "payment_date"],
            ["class" => "text-start min-w-150px", "name" => "final_total"],
            ["class" => "text-start min-w-150px", "name" => "paid_amount"],
            ["class" => "text-start min-w-150px", "name" => "remaining_amount"],
            ["class" => "text-start min-w-150px", "name" => "payment_method"],
            ["class" => "text-start min-w-150px", "name" => "payment_status"],
            ["class" => "text-start min-w-150px", "name" => "sales"],

        ];
    }


    public function productInventoryReportColumns()
    {
        return [
            ["class" => "text-start min-w-150px", "name" => "product_name"],
            ["class" => "text-start min-w-150px", "name" => "establishment_name"],
            ["class" => "text-start min-w-150px", "name" => "transfer_in_out"],
            ["class" => "text-start min-w-150px", "name" => "type"],
            ["class" => "text-start min-w-150px", "name" => "quantity"],
            ["class" => "text-start min-w-150px", "name" => "entity"],
            ["class" => "text-start min-w-150px", "name" => "transfer_date"],

        ];
    }
    public function productInventorySummaryColumns()
    {
        return [
            ["class" => "text-start min-w-150px", "name" => "sku"],
            ["class" => "text-start min-w-150px", "name" => "product_name"],
            ["class" => "text-start min-w-150px", "name" => "establishment_name"],
            ["class" => "text-start min-w-150px", "name" => "opening_inventory"],
            ["class" => "text-start min-w-150px", "name" => "purchased_quantity"],
            ["class" => "text-start min-w-150px", "name" => "sales_quantity"],
            ["class" => "text-start min-w-150px", "name" => "waste"],
            ["class" => "text-start min-w-150px", "name" => "purchase_returns"],
            ["class" => "text-start min-w-150px", "name" => "transferred_quantity"],
            ["class" => "text-start min-w-150px", "name" => "production_quantity"],
            ["class" => "text-start min-w-150px", "name" => "counted_quantity"],
            ["class" => "text-start min-w-150px", "name" => "quantity_on_inventory"],

        ];
    }
    public function purchasePaymentReportColumns()
    {
        return [
            ["class" => "text-start min-w-150px", "name" => "reference_number"],
            ["class" => "text-start min-w-150px", "name" => "establishment_name"],
            ["class" => "text-start min-w-150px", "name" => "device_name"],
            ["class" => "text-start min-w-150px", "name" => "supplier"],
            ["class" => "text-start min-w-150px", "name" => "payment_date"],
            ["class" => "text-start min-w-150px", "name" => "final_total"],
            ["class" => "text-start min-w-150px", "name" => "paid_amount"],
            ["class" => "text-start min-w-150px", "name" => "remaining_amount"],
            ["class" => "text-start min-w-150px", "name" => "payment_method"],
            ["class" => "text-start min-w-150px", "name" => "payment_status"],
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
            ->editColumn('establishment_name', function ($row) {
                return $row->establishment_name ?? '---';
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
            ->editColumn('establishment_name', function ($row) {
                return $row->establishment_name ?? '---';
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
                    $url = url('transaction-show/' . $row->transaction_id);

                    return '<a href="' . $url . '">' . $row->ref_no . '</a>';
                } else {
                    return '';
                }
            })
            ->editColumn('establishment_name', function ($row) {
                return  $row->establishment_name;
            })
            ->editColumn('device_name', function ($row) {
                return  $row->device_name;
            })
            ->editColumn('supplier', function ($row) {

                return $row->supplier;
            })
            ->editColumn('final_total', function ($row) {

                return $row->final_total;
            })
            ->editColumn('paid_on', function ($row) {

                return $row->paid_on;
            })
            ->addColumn('remaining_amount', function ($row) {

                $remaining = $row->final_total - $row->amount;
                if ($remaining > 0) {
                    $html = '<span class="badge bg-danger">' . number_format($remaining, 2) . '</span>';
                } else {
                    $html = number_format($remaining, 2);
                }

                return $html;
            })
            ->editColumn('payment_ref_no', function ($row) {

                return $row->payment_ref_no;
            })

            ->editColumn('payment_status', function ($row) {
                $color = $row->payment_status == 'paid' ? 'green' : 'red';
                return '<span style="color: ' . $color . ';">' . __('report::purchase.' . $row->payment_status) . '</span>';
            })

            ->editColumn('method', function ($row) {
                $color = $row->method == 'due' ? 'blue' : 'orange';
                return '<span style="color: ' . $color . ';">' . __('sales::lang.' . $row->method) . '</span>';
            })
            ->editColumn('amount', function ($row) {
                return '<span class="paid-amount" data-orig-value="' . $row->amount . '">' .
                    number_format($row->amount, 2) . '</span>';
            })

            ->editColumn('actions', function ($row) {
                return "--";
            })
            ->rawColumns(['ref_no', 'remaining_amount', 'final_total', 'establishment_name', 'device_name', 'amount', 'payment_status', 'method', 'action', 'supplier'])
            ->make(true);
    }
    public function productInventoryReportTable($query)
    {
        return Datatables::of($query)
            ->editColumn('product_name', function ($row) {
                return $row->product_name;
            })
            ->editColumn('establishment_name', function ($row) {
                return  $row->establishment_name;
            })
            ->editColumn('transfer_in_out', function ($row) {
                $icon = $row->transfer_in_out === '-' ? '🔽' : '➕';
                $title = app()->getLocale() === 'ar' ?
                    ($row->transfer_in_out === '-' ? 'خارج من المخزون' : 'داخل إلى المخزون') : ($row->transfer_in_out === '-' ? 'Out of stock' : 'In stock');

                return "<span  title='{$title}'>{$icon} {$title}</span>";
                return "<span >{$icon} {$title}</span>";
            })
            ->editColumn('transfer_date', function ($row) {
                return \Carbon\Carbon::parse($row->transfer_date)->format('Y-m-d');
            })
            ->editColumn('type', function ($row) {
                $locale = app()->getLocale();
                $typeMap = [
                    'WASTE' => [
                        'ar' => 'إتلاف',
                        'en' => 'Waste',
                        'icon' => 'fas fa-trash',
                        'color' => 'red'
                    ],
                    'TRANSFER' => [
                        'ar' => 'تحويل',
                        'en' => 'Transfer',
                        'icon' => 'fas fa-exchange-alt',
                        'color' => 'green'
                    ],
                    'purchases' => [
                        'ar' => 'شراء',
                        'en' => 'Purchase',
                        'icon' => 'fas fa-shopping-cart',
                        'color' => 'orange'
                    ],
                    'PREP' => [
                        'ar' => 'تحضير',
                        'en' => 'Prepare',
                        'icon' => 'fas fa-utensils',
                        'color' => '#FFD700'
                    ],
                    'sell' => [
                        'ar' => 'بيع',
                        'en' => 'Sale',
                        'icon' => 'fas fa-cash-register',
                        'color' => 'blue'
                    ],
                    'purchases-return' => [
                        'ar' => 'إرجاع مشتريات',
                        'en' => 'Purchase Return',
                        'icon' => 'fas fa-undo',
                        'color' => 'purple'
                    ],
                    'sell-return' => [
                        'ar' => 'إرجاع مبيعات',
                        'en' => 'Sale Return',
                        'icon' => 'fas fa-undo',
                        'color' => '#8B4513'
                    ]
                ];

                if (array_key_exists($row->type, $typeMap)) {
                    $typeData = $typeMap[$row->type];
                    $text = $locale === 'ar' ? $typeData['ar'] : $typeData['en'];
                    return "<span title='{$text}'><i class='{$typeData['icon']}' style='color: {$typeData['color']};'></i> {$text}</span>";
                }

                return $row->type;
            })

            ->editColumn('quantity', function ($row) {
                return $row->quantity . '  ' . $row->unit;;
            })
            ->editColumn('entity', function ($row) {
                return  $row->entity;
            })
            ->editColumn('actions', function ($row) {
                return "--";
            })
            ->rawColumns(['transfer_in_out', 'product_name', 'establishment_name', 'entity', 'type', 'quantity', 'transfer_date', 'actions'])
            ->make(true);
    }
    public function productInventorySummaryTable($query)
    {
        return Datatables::of($query)
            ->editColumn('sku', function ($row) {
                return $row->sku;
            })
            ->editColumn('product_name', function ($row) {
                return $row->product_name;
            })
            ->editColumn('establishment_name', function ($row) {
                return $row->establishment_name;
            })
            ->editColumn('opening_inventory', function ($row) {
                return ($row->opening_inventory !== NULL) ? $row->opening_inventory . ' ' . ($row->base_unit_name ?? '') : '---';
            })
            ->editColumn('purchased_quantity', function ($row) {
                return ($row->purchased_quantity ?? '---') . ' ' . ($row->base_unit_name ?? '');
            })
            ->editColumn('sales_quantity', function ($row) {
                return ($row->sales_quantity ?? '---') . ' ' . ($row->base_unit_name ?? '');
            })
            ->editColumn('waste', function ($row) {
                return ($row->waste ?? '---') . ' ' . ($row->base_unit_name ?? '');
            })
            ->editColumn('purchase_returns', function ($row) {
                return ($row->purchase_returns ?? '---') . ' ' . ($row->base_unit_name ?? '');
            })
            ->editColumn('transferred_quantity', function ($row) {
                return ($row->transferred_quantity ?? '---') . ' ' . ($row->base_unit_name ?? '');
            })->editColumn('production_quantity', function ($row) {
                return ($row->production_quantity ?? '---') . ' ' . ($row->base_unit_name ?? '');
            })
            ->editColumn('counted_quantity', function ($row) {
                return $row->counted_quantity ?? '---';
            })
            ->editColumn('quantity_on_inventory', function ($row) {
                return ($row->quantity_on_inventory !== NULL) ? $row->quantity_on_inventory . ' ' . ($row->base_unit_name ?? '') : '---';
            })
            ->editColumn('actions', function ($row) {
                return '<a href="' . route('inventory.record', [
                    'product_id' => $row->product_id,
                    'establishment_id' => $row->establishment_id,
                    'opening_inventory' => ($row->opening_inventory !== NULL) ? $row->opening_inventory . ' ' . ($row->base_unit_name ?? '') : '---',
                    'purchased_quantity' => ($row->purchased_quantity ?? '---') . ' ' . ($row->base_unit_name ?? ''),
                    'sales_quantity' => ($row->sales_quantity ?? '---') . ' ' . ($row->base_unit_name ?? ''),
                    'waste' => ($row->waste ?? '---') . ' ' . ($row->base_unit_name ?? ''),
                    'purchase_returns' => ($row->purchase_returns ?? '---') . ' ' . ($row->base_unit_name ?? ''),
                    'transferred_quantity' => ($row->transferred_quantity ?? '---') . ' ' . ($row->base_unit_name ?? ''),
                    'production_quantity' => ($row->production_quantity ?? '---') . ' ' . ($row->base_unit_name ?? ''),
                    'counted_quantity' => $row->counted_quantity,
                    'quantity_on_inventory' => ($row->quantity_on_inventory !== NULL) ? $row->quantity_on_inventory . ' ' . ($row->base_unit_name ?? '') : '---'
                ]) . '" class="btn btn-primary">' . __('menuItemLang.product-inventory-record') . '</a>';
            })

            ->rawColumns(['sku', 'product_name', 'establishment_name', 'opening_inventory', 'purchased_quantity', 'sales_quantity', 'waste', 'purchase_returns', 'transferred_quantity', 'production_quantity', 'counted_quantity', 'quantity_on_inventory', 'actions'])
            ->make(true);
    }
}
