<?php


namespace Modules\Employee\Classes;
use Yajra\DataTables\DataTables;

class AdjustmentTypeTable
{
    public static function getAdjustmentTypeColumns()
    {
        return [
            ["class" => "text-start px-3", "name" => "name"],
            ["class" => "text-start px-3", "name" => "name_en"],
            ["class" => "text-start px-3", "name" => "type"],
        ];
    }

    public static function getAdjustmentTypeTable($dashboardRoles)
    {
        return DataTables::of($dashboardRoles)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                             {$row->id} 
                    </div>";
            })
            ->editColumn('type', function ($row) {
                return $row->type === 'allowance' ? __('employee::fields.allowance') : __('employee::fields.deduction');
            })
            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '<div class="justify-content-center d-flex">';
                    if (auth()->user()->hasDashboardPermission('employees.allowance_deduction.delete')) {
                        $actions .= '
                            <a class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px adjustment-type-delete-btn me-1" data-id="' . $row->id . '">
                                <i class="ki-outline ki-trash fs-3"></i>
                            </a>';
                    }
                    if (auth()->user()->hasDashboardPermission('employees.allowance_deduction.update')) {
                        $actions .= '
                            <a href="' . url("/adjustment/{$row->id}/edit") . '" class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px me-1 edit-btn" data-id="' . $row->id . '" >
                                <i class="ki-outline ki-pencil fs-2"></i>
                            </a>';
                    }
                    $actions .= '</div>';
                    return $actions;
                }
            )
            ->rawColumns(['actions', 'id'])
            ->make(true);
    }
}