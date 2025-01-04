<?php


namespace Modules\Employee\Classes;
use Yajra\DataTables\DataTables;

class EmployeeTable
{

    public static function getEmployeeColumns()
    {
        return [
            ["class" => "text-start min-w-200px px-3", "name" => "name"],
            ["class" => "text-start min-w-200px px-3", "name" => "name_en"],
            ["class" => "text-start min-w-150px px-3", "name" => "phone"],
            ["class" => "text-start min-w-150px text-nowrap px-3", "name" => "employment_start_date"],
            ["class" => "text-start min-w-150px text-nowrap px-3", "name" => "employment_end_date"],
            ["class" => "text-start min-w-100px px-3", "name" => "status"],
        ];
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
                    $emsAccess = $row->ems_access;
                    $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">' . __('employee::fields.actions') . '<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4" data-kt-menu="true">';

                    if (!$row->deleted_at) {
                        if (auth()->user()->hasDashboardPermission('employees.employee.update')) {
                            $actions .= '<div class="menu-item px-3">
                                   <a href="' . url("/employee/{$row->id}/edit") . '" class="menu-link px-3">' . __('employee::fields.edit') . '</a>
                               </div>';
                        }
                        if (auth()->user()->hasDashboardPermission('employees.pos_role.update')) {
                            $actions .= '<div class="menu-item px-3">
                                   <a href="#" class="menu-link px-3 edit-pos-permission-button" data-id="' . $row->id . '">' . __('employee::general.edit_pos_permissions') . '</a>
                               </div>';
                        }
                    }

                    if ($emsAccess && !$row->deleted_at && auth()->user()->hasDashboardPermission('employees.dashboard_role.update')) {
                        $actions .= '<div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3 edit-ems-permission-button" data-id="' . $row->id . '">' . __('employee::general.edit_dashboard_permissions') . '</a>
                                    </div>';
                    }
                    if (auth()->user()->hasDashboardPermission('employees.employee.delete')) {
                        $actions .= '<div class="menu-item px-3">
                        <a class="menu-link px-3 delete-btn" data-id="' . $row->id . '" data-deleted="' . $row->deleted_at . '" data-name="' . ($row->{get_name_by_lang()}) . '">' . ($row->deleted_at ? __('employee::fields.force_delete') : __('employee::fields.delete')) . '</a>
                        </div>';
                    }
                    if ($row->deleted_at && auth()->user()->hasDashboardPermission('employees.employee.update')) {
                        $actions .=
                            '<div class="menu-item px-3">
                                    <a class="menu-link px-3 restore-btn" data-id="' . $row->id . '">' . __('employee::fields.restore') . '</a>
                                </div></div>';

                    } else {
                        if (auth()->user()->hasDashboardPermission('employees.employee.show')) {
                            $actions .= '<div class="menu-item px-3">
                            <a href="' . url("/employee/show/{$row->id}") . '" class="menu-link px-3 show-btn" data-id="' . $row->id . '">' . __('employee::fields.show') . '</a>
                            </div>';
                        }
                    }
                    $actions .= '<div class="menu-item px-3">
                            <a href="#" class="menu-link px-3 print-btn" data-id="' . $row->id . '">' . __('employee::fields.print') . '</a>
                            </div></div>';
                    return $actions;
                }
            )
            ->editColumn('pos_is_active', function ($employee) {
                return $employee->pos_is_active
                    ? '<div class="badge badge-light-success">' . __("employee::fields.active") . '</div>'
                    : '<div class="badge badge-light-danger">' . __("employee::fields.inActive") . '</div>';
            })
            ->rawColumns(['actions', 'pos_is_active', 'id'])
            ->make(true);
    }
}