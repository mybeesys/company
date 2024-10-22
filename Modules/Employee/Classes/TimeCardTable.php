<?php

namespace Modules\Employee\Classes;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

class TimeCardTable
{
    public static function getTimecardColumns()
    {
        return [
            ["class" => "text-start px-3", "name" => "inTime"],
            ["class" => "text-start min-w-100px px-3", "name" => "outTime"],
            ["class" => "text-start min-w-250px px-3", "name" => "total_hours"],
            ["class" => "text-start min-w-250px px-3", "name" => "overtime_hours"],
        ];
    }

    public static function getTimecardTable($roles)
    {
        return DataTables::of($roles)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                 {$row->id} 
                        </div>";
            })
            ->editColumn('clockInTime', function ($row){
                return Carbon::parse($row->clockInTime)->format('H:i');
            })
            ->editColumn('clockOutTime', function ($row){
                return Carbon::parse($row->clockOutTime)->format('H:i');
            })
            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '
                    <div class="text-center"> 
                <a class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px delete-btn me-1" data-id="' . $row->id . '">
					<i class="ki-outline ki-trash fs-3"></i>
				</a>      
                <a href="' . url("/timecard/{$row->id}/edit") . '" class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px me-1 edit-btn" data-id="' . $row->id . '" >
					<i class="ki-outline ki-pencil fs-2"></i>
				</a>                
                </div>';
                    return $actions;
                }
            )
            ->rawColumns(['actions', 'id'])
            ->make(true);
    }

}