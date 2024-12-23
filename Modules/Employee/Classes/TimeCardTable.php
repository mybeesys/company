<?php

namespace Modules\Employee\Classes;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

class TimeCardTable
{
    public static function getTimecardColumns()
    {
        return [
            ["class" => "text-start align-middle px-3", "name" => "employee"],
            ["class" => "text-start align-middle px-3", "name" => "inTime"],
            ["class" => "text-start align-middle min-w-100px px-3", "name" => "outTime"],
            ["class" => "text-start align-middle min-w-250px px-3", "name" => "total_hours"],
            ["class" => "text-start align-middle min-w-250px px-3", "name" => "overtime_hours"],
            ["class" => "text-start align-middle min-w-250px px-3", "name" => "date"],
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
            ->addColumn('employee', function($row){
                return session('locale') === 'ar' ? $row->employee->name : $row->employee->name_en;
            })
            ->editColumn('clock_in_time', function ($row){
                return Carbon::parse($row->clock_in_time)->format('H:i');
            })
            ->editColumn('clock_out_time', function ($row){
                return Carbon::parse($row->clock_out_time)->format('H:i');
            })
            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '
                    <div class="text-center"> 
                <a class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px delete-btn me-1" data-id="' . $row->id . '">
					<i class="ki-outline ki-trash fs-3"></i>
				</a>      
                <a href="' . url("/schedule/timecard/{$row->id}/edit") . '" class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px me-1 edit-btn" data-id="' . $row->id . '" >
					<i class="ki-outline ki-pencil fs-2"></i>
				</a>                
                </div>';
                    return $actions;
                }
            )
            ->rawColumns(['actions', 'id', 'employee'])
            ->make(true);
    }

}