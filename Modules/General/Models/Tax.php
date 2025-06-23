<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Yajra\DataTables\Facades\DataTables;

// use Modules\General\Database\Factories\TaxFactory;

class Tax extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function getsTaxesColumns()
    {
        return [

            ["class" => "text-start min-w-150px ", "name" => "tax_name"],
            ["class" => "text-start min-w-150px ", "name" => "tax_name_en"],
            ["class" => "text-start min-w-150px  ", "name" => "tax_amount"],
            ["class" => "text-start min-w-150px  ", "name" => "create_group_tax"],
            ["class" => "text-start min-w-150px  ", "name" => "minimum_limit"],
            ["class" => "text-start min-w-75px  ", "name" => "minimum_limit"],

        ];
    }


    public static function getTaxesTable($taxes)
    {
        return DataTables::of($taxes)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                     {$row->id}
                            </div>";
            })
            ->editColumn('name', function ($row) {
                return $row->name;
            })

            ->editColumn('name_en', function ($row) {
                return $row->name_en;
            })

            ->editColumn('amount', function ($row) {
                return  $row->amount ?? '--';
            })

            ->editColumn('is_tax_group', function ($row) {
                return  $row->is_tax_group ? __('general::fields.create_group_tax') : '--';
            })



            ->editColumn('sub_taxes', function ($row) {
                return  $row->sub_taxes ?? '--';
            })

            ->addColumn('minimum_limit', function ($row) {
                return $row->minimum_limit;
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

            ->rawColumns(['actions', 'id'])
            ->make(true);
    }


    public function sub_taxes()
    {
        return $this->belongsToMany(Tax::class, 'group_sub_taxes', 'group_tax_id', 'tax_id');
    }
}