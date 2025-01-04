<?php


namespace Modules\Employee\Classes;
use Yajra\DataTables\DataTables;

class PosRoleTable
{

    public static function getRoleColumns()
    {
        return [
            ["class" => "text-start px-3", "name" => "name"],
            ["class" => "text-start min-w-100px px-3", "name" => "department"],
            ["class" => "text-start min-w-250px px-3", "name" => "rank"],
            ["class" => "text-start min-w-100px px-3", "name" => "status"],
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
                    $actions = '<div class="justify-content-center d-flex">';
                    if (auth()->user()->hasDashboardPermission('employees.pos_role.delete')) {
                        $actions .= '
                            <a class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px delete-btn me-1" data-id="' . $row->id . '">
                                <i class="ki-outline ki-trash fs-3"></i>
                            </a>';
                    }
                    if (auth()->user()->hasDashboardPermission('employees.pos_role.update')) {

                        $actions .= '
                            <a href="' . url("/pos-role/{$row->id}/edit") . '" class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px me-1 edit-btn" data-id="' . $row->id . '" >
                                <i class="ki-outline ki-pencil fs-2"></i>
                            </a>';
                    }
                    if (auth()->user()->hasDashboardPermission('employees.pos_role.show')) {
                        $actions .= '                
                            <a href="' . url("/pos-role/show/{$row->id}") . '" class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px" data-id="' . $row->id . '">
                                <i class="ki-outline ki-eye fs-3"></i>
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
            ->rawColumns(['actions', 'id', 'is_active'])
            ->make(true);
    }
}