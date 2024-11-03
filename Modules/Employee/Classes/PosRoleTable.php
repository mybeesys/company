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
                    $actions = '
                    <div class="text-center d-flex"> 
                <a class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px delete-btn me-1" data-id="' . $row->id . '">
					<i class="ki-outline ki-trash fs-3"></i>
				</a>      
                <a href="' . url("/pos-role/{$row->id}/edit") . '" class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px me-1 edit-btn" data-id="' . $row->id . '" >
					<i class="ki-outline ki-pencil fs-2"></i>
				</a>                
                <a href="' . url("/pos-role/show/{$row->id}") . '" class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px" data-id="' . $row->id . '">
					<i class="ki-outline ki-eye fs-3"></i>
				</a>
                </div>';
                    return $actions;
                }
            )
            ->rawColumns(['actions', 'id'])
            ->make(true);
    }
}