<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Yajra\DataTables\Facades\DataTables;

// use Modules\General\Database\Factories\PaymentMethodFactory;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function getsPaymentMethodsColumns()
    {
        return [

            ["class" => "text-start min-w-150px ", "name" => "name_ar"],
            ["class" => "text-start min-w-150px ", "name" => "name_en"],
            ["class" => "text-start min-w-150px  ", "name" => "description_ar"],
            ["class" => "text-start min-w-150px  ", "name" => "description_en"],

        ];
    }


    public static function getPaymentMethodsTable($paymentMethods)
    {
        return DataTables::of($paymentMethods)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                     {$row->id}
                            </div>";
            })
            ->editColumn('active', function ($row) {
                if ($row->active) {
                    return    '<span class="badge badge-light-success px-3 py-3 fs-base">

               ' . __('messages.active') . ' </span>';
                } else  {
                    return    '<span class="badge badge-light-danger px-3 py-3 fs-base">

               ' . __('messages.in_active') . ' </span>';
                }
            })


            ->addColumn(
                'actions',
                function ($row) {
                    if ($row->name == 'ضريبة القيمة المضافة (15.0%)' || $row->name == 'الضريبة الصفرية (0.0%)' || $row->name == 'معفاة من الضريبة (0.0%)') {
                        return    '<span class="badge badge-light-success px-3 py-3 fs-base">

                        ' . __('general::lang.default tax') . ' </span>';
                    } else {
                        $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">' . __('employee::fields.actions') . '<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">';


                                $actions .= '<div class="menu-item px-3">
                            <a href="#"
                            class="menu-link px-3 open-tax-modal"
                            id="open_tax_modal"
                            data-name="' . $row->name . '"
                            data-amount="' . $row->amount . '">
                            ' . __('messages.edit') . '
                            </a>
                        </div>
                        ';



                        $actions .= '<div class="menu-item px-3">
                <a href="' . url("/delete-tax/{$row->id}") . '" class="menu-link px-3">' . __('employee::fields.delete') . '</a>
            </div>';
                        return $actions;
                    }
                }
            )

            ->rawColumns(['actions','active', 'id'])
            ->make(true);
    }
}