<?php


namespace Modules\Employee\Classes;
use Yajra\DataTables\DataTables;

class AdjustmentTable
{
    public static function getAdjustmentColumns()
    {
        return [
            ["class" => "text-start px-3", "name" => "adjustment_type_name"],
            ["class" => "text-start min-w-250px px-3", "name" => "employee"],
            ["class" => "text-start min-w-100px px-3", "name" => "type"],
            ["class" => "text-start min-w-100px px-3", "name" => "amount"],
            ["class" => "text-start min-w-100px px-3", "name" => "amount_type"],
            ["class" => "text-start min-w-100px px-3", "name" => "applicable_date"],
            ["class" => "text-start min-w-100px px-3", "name" => "apply_once"],
        ];
    }

    public static function getAdjustmentTable($adjustments)
    {
        return DataTables::of($adjustments)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                             {$row->id} 
                    </div>";
            })
            ->addColumn('adjustment_type_name', function ($row) {
                return $row->adjustmentType->translatedName;
            })
            ->addColumn('employee', function ($row) {
                return $row->employee->{get_name_by_lang()};
            })
            ->editColumn('apply_once', function ($employee) {
                return $employee->apply_once
                    ? '<div class="badge badge-light-success">True</div>'
                    : '<div class="badge badge-light-danger">False</div>';
            })
            ->editColumn('type', function ($row) {
                return $row->type === 'allowance' ? __('employee::fields.allowance') : __('employee::fields.deduction');
            })
            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '<div class="justify-content-center d-flex">';
                    if (auth()->user()->hasDashboardPermission('employees.allowance_deduction.delete') && !$row->deleted_at) {
                        $actions .= '
                            <a class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px delete-btn me-1" data-id="' . $row->id . '">
                                <i class="ki-outline ki-trash fs-3"></i>
                            </a>';
                    }
                    if (auth()->user()->hasDashboardPermission('employees.allowance_deduction.update') && !$row->deleted_at) {

                        $actions .= '
                            <a class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px me-1 edit-btn" 
                                data-adjustment-type="' . $row->adjustment_type_id . '"
                                data-id="' . $row->id . '"
                                data-employee-id="' . $row->employee_id . '"
                                data-type="' . $row->type . '"
                                data-amount="' . $row->amount . '"
                                data-amount-type="' . $row->amount_type . '"
                                data-applicable-date="' . $row->applicable_date . '"
                                data-apply-once="' . $row->apply_once . '"
                            >
                                <i class="ki-outline ki-pencil fs-2"></i>
                            </a>';
                    }
                    $actions .= '</div>';
                    return $actions;
                }
            )
            ->editColumn('is_active', function ($row) {
                return $row->is_active
                    ? '<div class="badge badge-light-success">' . __("employee::fields.active") . '</div>'
                    : '<div class="badge badge-light-danger">' . __("employee::fields.inActive") . '</div>';
            })
            ->rawColumns(['actions', 'apply_once', 'id'])
            ->make(true);
    }
}