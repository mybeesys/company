<?php


namespace Modules\Accounting\classes;

use Yajra\DataTables\Facades\DataTables;

class CostCenterTable
{

    public static function getAccTransMappingColumns()
    {
        return [

            ["class" => "text-start min-w-250px px-3", "name" => "name"],
            ["class" => "text-start min-w-200px px-3", "name" => "type"],
            ["class" => "text-start min-w-200px px-3", "name" => "ref_no"],
            ["class" => "text-start min-w-200px text-nowrap px-3", "name" => "created_by"],
            ["class" => "text-start min-w-150px text-nowrap px-3", "name" => "note"],

        ];
    }

    public static function getAccTransMappingTable($acc_trans_mapping)
    {
        return DataTables::of($acc_trans_mapping)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                     {$row->id}
                            </div>";
            })
            ->editColumn('created_by', function ($row) {
                return $row->added_by->name;
            })

            ->editColumn('type', function ($row) {
                return __('accounting::lang.' . $row->type);
            })

            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">' . __('employee::fields.actions') . '<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">';

                     $actions .= '<div class="menu-item px-3">
                            <a href="' . url("/journal-entry-edit/{$row->id}") . '" class="menu-link px-3">' . __('employee::fields.edit') . '</a>
                        </div>';

                        $actions .= '<div class="menu-item px-3">
                        <a href="' . url("/journal-entry-duplication/{$row->id}") . '" class="menu-link px-3">' . __('accounting::fields.duplication') . '</a>
                    </div>';

                    $actions .= '<div class="menu-item px-3">
                    <a href="' . url("/journal-entry-print/{$row->id}") . '" class="menu-link px-3">' . __('accounting::fields.print') . '</a>
                </div>';

                    $actions .= '<div class="menu-item px-3">
                                    <a class="menu-link px-3 delete-btn" href="' . url("/journal-entry-destroy/{$row->id}") . '" data-id="' . $row->id . '"  data-ref_no="' . $row->ref_no . '">'. __('employee::fields.delete') . '</a>
                                </div>';


                    // $row->deleted_at ? $actions .=
                    //     '<div class="menu-item px-3">
                    //             <a class="menu-link px-3 restore-btn" data-id="' . $row->id . '">' . __('employee::fields.restore') . '</a>
                    //         </div></div>' : $actions .= '<div class="menu-item px-3">
                    //             <a href="' . url("/employee/show/{$row->id}") . '" class="menu-link px-3 show-btn" data-id="' . $row->id . '">' . __('employee::fields.show') . '</a>
                    //         </div></div>';
                    return $actions;
                }
            )

            ->rawColumns(['actions', 'id'])
            ->make(true);
    }
}