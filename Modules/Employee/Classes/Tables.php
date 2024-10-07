<?php


namespace Modules\Employee\Classes;
use Yajra\DataTables\DataTables;

class Tables
{

    public static function getRoleColumns()
    {
        return [
            ["class" => "", "name" => "name"],
            ["class" => "min-w-100px", "name" => "department"],
            ["class" => "min-w-250px", "name" => "rank"],
        ];
    }
    public static function getEmployeeColumns()
    {
        return [
            ["class" => "text-start min-w-200px", "name" => "name"],
            ["class" => "text-start min-w-200px", "name" => "name_en"],
            ["class" => "text-start min-w-150px", "name" => "phone"],
            ["class" => "text-start min-w-150px text-nowrap", "name" => "employment_start_date"],
            ["class" => "text-start min-w-150px text-nowrap", "name" => "employment_end_date"],
            ["class" => "text-start min-w-100px", "name" => "status"],
        ];
    }

    public static function getRoleTable($roles)
    {
        return DataTables::of($roles)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                 {$row->id} 
                        </div>";
            })
            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">' . __('employee::fields.actions') . '<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
                    <div class="menu-item px-3">
                        <a href="#" data-bs-toggle="modal" data-id="' . $row->id . '" data-name="' . $row->name . '" data-rank="' . $row->rank . '" data-department="' . $row->department . '" data-bs-target="#kt_modal_edit_role" class="menu-link px-3 edit-btn">' . __('employee::fields.edit') . '</a>
                    </div>
                    <div class="menu-item px-3">
                        <a class="menu-link px-3 delete-btn" data-id="' . $row->id . '" data-deleted="' . $row->deleted_at . '" data-name="' . $row->firstName . '">' . ($row->deleted_at ? __('employee::fields.force_delete') : __('employee::fields.delete')) . '</a>
                    </div>
                    <div class="menu-item px-3">
                        <a href="' . url("/role/show/{$row->id}") . '" class="menu-link px-3 show-btn" data-id="' . $row->id . '">' . __('employee::fields.show') . '</a>
                    </div>
                </div>';
                    return $actions;
                }
            )
            ->editColumn('isActive', function ($employee) {
                return $employee->isActive
                    ? '<div class="badge badge-light-success">' . __("employee::fields.active") . '</div>'
                    : '<div class="badge badge-light-danger">' . __("employee::fields.inActive") . '</div>';
            })
            ->rawColumns(['actions', 'isActive', 'id'])
            ->make(true);
    }

    public static function getEmployeeTable($employees)
    {
        return DataTables::of($employees)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                     {$row->id} 
                            </div>";
            })
            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">' . __('employee::fields.actions') . '<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">';

                    $row->deleted_at ?? $actions .= '<div class="menu-item px-3">
                            <a href="' . url("/employee/{$row->id}/edit") . '" class="menu-link px-3">' . __('employee::fields.edit') . '</a>
                        </div>';

                    $actions .= '<div class="menu-item px-3">
                                    <a class="menu-link px-3 delete-btn" data-id="' . $row->id . '" data-deleted="' . $row->deleted_at . '" data-name="' . $row->firstName . '">' . ($row->deleted_at ? __('employee::fields.force_delete') : __('employee::fields.delete')) . '</a>
                                </div>';

                    $row->deleted_at ? $actions .=
                        '<div class="menu-item px-3">
                                <a class="menu-link px-3 restore-btn" data-id="' . $row->id . '">' . __('employee::fields.restore') . '</a>
                            </div></div>' : $actions .= '<div class="menu-item px-3">
                                <a href="' . url("/employee/show/{$row->id}") . '" class="menu-link px-3 show-btn" data-id="' . $row->id . '">' . __('employee::fields.show') . '</a>
                            </div></div>';
                    return $actions;
                }
            )
            ->editColumn('isActive', function ($employee) {
                return $employee->isActive
                    ? '<div class="badge badge-light-success">' . __("employee::fields.active") . '</div>'
                    : '<div class="badge badge-light-danger">' . __("employee::fields.inActive") . '</div>';
            })
            ->rawColumns(['actions', 'isActive', 'id'])
            ->make(true);
    }
}