<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Establishment\Models\Establishment;
use Modules\General\Utils\TransactionUtils;
use Yajra\DataTables\Facades\DataTables;

// use Modules\General\Database\Factories\TransactionFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function client()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }

    public function sell_lines()
    {
        return $this->hasMany(TransactionSellLine::class, 'transaction_id');
    }

    public function purchases_lines()
    {
        return $this->hasMany(TransactionePurchasesLine::class, 'transaction_id');
    }

    public function payment()
    {
        return $this->hasMany(TransactionPayments::class, 'transaction_id');
    }


    public static function getsSellsColumns()
    {
        return [

            ["class" => "text-start min-w-150px ", "name" => "ref_no"],
            ["class" => "text-start min-w-150px  ", "name" => "client"],
            ["class" => "text-start min-w-150px", "name" => "transaction_date"],
            ["class" => "text-start min-w-150px ", "name" => "due_date"],
            ["class" => "text-start min-w-80px ", "name" => "payment_status"],
            // ["class" => "text-start min-w-150px", "name" => "total_before_vat"],
            // ["class" => "text-start min-w-150px ", "name" => "vat_value"],
            // ["class" => "text-start min-w-150px  ", "name" => "discount"],
            ["class" => "text-start min-w-100px  ", "name" => "invoice_amount"],
            ["class" => "text-start min-w-100px  ", "name" => "piad_amount"],
            ["class" => "text-start min-w-100px  ", "name" => "remaining_amount"],
        ];
    }

    public static function getsQuotationColumns()
    {
        return [


            ["class" => "text-start min-w-150px ", "name" => "ref_no"],
            ["class" => "text-start min-w-150px  ", "name" => "client"],
            ["class" => "text-start min-w-150px", "name" => "issue_date"],
            ["class" => "text-start min-w-150px ", "name" => "Expiry Date"],
            // ["class" => "text-start min-w-80px ", "name" => "payment_status"],
            ["class" => "text-start min-w-150px", "name" => "total_before_vat"],
            // ["class" => "text-start min-w-150px ", "name" => "vat_value"],
            // ["class" => "text-start min-w-150px  ", "name" => "discount"],
            ["class" => "text-start min-w-150px  ", "name" => "amount"],
        ];
    }


    public static function getsPurchasesColumns()
    {
        return [

            ["class" => "text-start min-w-150px ", "name" => "ref_no"],
            ["class" => "text-start min-w-150px  ", "name" => "client"],
            ["class" => "text-start min-w-150px", "name" => "transaction_date"],
            ["class" => "text-start min-w-150px ", "name" => "due_date"],
            ["class" => "text-start min-w-80px ", "name" => "payment_status"],
            // ["class" => "text-start min-w-150px", "name" => "total_before_vat"],
            // ["class" => "text-start min-w-150px ", "name" => "vat_value"],
            // ["class" => "text-start min-w-150px  ", "name" => "discount"],
            ["class" => "text-start min-w-100px  ", "name" => "invoice_amount"],
            ["class" => "text-start min-w-100px  ", "name" => "piad_amount"],
            ["class" => "text-start min-w-100px  ", "name" => "remaining_amount"],
        ];
    }

    public static function getSellsTable($transactions)
    {

        return DataTables::of($transactions)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                     {$row->id}
                            </div>";
            })
            ->editColumn('ref_no', function ($row) {
                return $row->ref_no;
            })

            ->editColumn('client', function ($row) {
                return  $row->client->name ?? '--';
            })
            ->editColumn('transaction_date', function ($row) {
                return  $row->transaction_date ?? '--';
            })
            ->editColumn('due_date', function ($row) {
                return  $row->due_date;
            })
            ->editColumn('total_before_tax', function ($row) {
                return  $row->total_before_tax ?? '0.00';
            })
            ->editColumn('paid_amount', function ($row) {
                $transactionUtil = new TransactionUtils();

                $paid_amount = $transactionUtil->getTotalPaid($row->id);
                return number_format($paid_amount, 2);
            })
            ->editColumn('remaining_amount', function ($row) {
                $transactionUtil = new TransactionUtils();

                $paid_amount = $transactionUtil->getTotalPaid($row->id);
                $amount = $row->final_total - $paid_amount;
                if ($amount < 0) {
                    $amount = 0;
                }
                return number_format($amount, 2);
            })
            ->editColumn('final_total', function ($row) {
                return  number_format($row->final_total, 2) ?? '0.00';
            })
            ->editColumn('payment_status', function ($row) {
                if ($row->payment_status == 'paid') {
                    return    '<span class="badge badge-light-info px-3 py-3 fs-base">

               ' . __('general::lang.paid') . ' </span>';
                } else if ($row->payment_status == 'due') {
                    return    '<span class="badge badge-light-danger px-3 py-3 fs-base">

               ' . __('general::lang.due') . ' </span>';
                } else if ($row->payment_status == 'partial') {
                    return    '<span class="badge badge-light-success px-3 py-3 fs-base">

           ' . __('general::lang.partial') . ' </span>';
                }
            })



            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">' . __('employee::fields.actions') . '<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">';


                    $actions .= '<div class="menu-item px-3">
                    <a href="' . url("/transaction-show/{$row->id}") . '" class="menu-link px-3">' . __('employee::fields.show') . '</a>
                </div>';

                    $actions .= '<div class="menu-item px-3">
                <a href="' . url("/transaction-print/{$row->id}") . '" class="menu-link px-3">' . __('general.print') . '</a>
            </div>';


                    if ($row->type != 'quotation' && $row->type != 'purchases-order') {
                        if ($row->payment_status == 'due' || $row->payment_status == 'partial') {
                            $actions .= '<div class="menu-item px-3">
                    <a href="' . url("/transaction-show-payments/{$row->id}") . '" class="menu-link px-3">' . __('general::lang.add_payment') . '</a>
                </div>';
                        } else {

                            $actions .= '<div class="menu-item px-3">
                    <a href="' . url("/transaction-show-payments/{$row->id}") . '" class="menu-link px-3">' . __('general::lang.show_payment') . '</a>
                </div>';
                        }
                    }



                    // $status = $row->status == 'active' ? __('messages.deactivate') : __('messages.activate');

                    // $actions .= '<div class="menu-item px-3">
                    //     <a href="' . url("/client-update-status/{$row->id}") . '" class="menu-link px-3">' . $status . '</a>
                    // </div>';


                    //     $actions .= '<div class="menu-item px-3">
                    //     <a href="' . url("/client-destroy/{$row->id}") . '" class="menu-link px-3">' . __('employee::fields.delete') . '</a>
                    // </div>';

                    // $actions .= '<div class="menu-item px-3">
                    //                 <a class="menu-link px-3 delete-btn" href="' . url("/client-destroy/{$row->id}") . '" data-id="' . $row->id . '"  data-ref_no="' . $row->name . '">'. __('employee::fields.delete') . '</a>
                    //             </div>';


                    return $actions;
                }
            )

            ->rawColumns(['actions', 'payment_status', 'remaining_amount', 'paid_amount', 'client', 'id'])
            ->make(true);
    }
}