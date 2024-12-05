<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ClientsAndSuppliers\Models\Contact;
use Yajra\DataTables\Facades\DataTables;

// use Modules\General\Database\Factories\TransactionFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function client(){
        return $this->belongsTo(Contact::class,'contact_id');
    }
    public static function getsSellsColumns()
    {
        return [

            ["class" => "text-start min-w-150px ", "name" => "ref_no"],
            ["class" => "text-start min-w-150px  ", "name" => "client"],
            ["class" => "text-start min-w-150px", "name" => "transaction_date"],
            ["class" => "text-start min-w-150px ", "name" => "due_date"],
            ["class" => "text-start min-w-150px", "name" => "total_before_vat"],
            // ["class" => "text-start min-w-150px ", "name" => "vat_value"],
            // ["class" => "text-start min-w-150px  ", "name" => "discount"],
            ["class" => "text-start min-w-150px  ", "name" => "amount"],
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
            // ->editColumn('tax_amount', function ($row) {
            //     return  $row->tax_amount ?? '0.00';
            // })
            // ->editColumn('discount_amount', function ($row) {
            //     return  $row->discount_amount ?? '0.00';
            // })
            ->editColumn('final_total', function ($row) {
                return  $row->final_total ?? '0.00';
            })



            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">' . __('employee::fields.actions') . '<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">';

                    // $actions .= '<div class="menu-item px-3">
                    //         <a href="' . url("/client-show/{$row->id}") . '" class="menu-link px-3">' . __('employee::fields.show') . '</a>
                    //     </div>';

                    // $actions .= '<div class="menu-item px-3">
                    //     <a href="' . url("/client-edit/{$row->id}") . '" class="menu-link px-3">' . __('messages.edit') . '</a>
                    // </div>';


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

            ->rawColumns(['actions', 'client', 'id'])
            ->make(true);
    }

}