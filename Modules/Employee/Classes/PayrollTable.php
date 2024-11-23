<?php


namespace Modules\Employee\Classes;
use Carbon\Carbon;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Schedule;
use Modules\Employee\Models\TimeSheetRule;
use Modules\Employee\Services\ShiftFilters;
use Yajra\DataTables\DataTables;

class PayrollTable
{
    public static function getPayrollColumns()
    {
        return [
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "id"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "employee"],
            // ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "date"],
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "gross_wage"],
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "net_wage"],
            ["class" => "text-start min-w-125px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "taxes_withheld"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "tips"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "allowances"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "deductions"],
        ];
    }

    public static function getPayrollTable($roles)
    {
        return DataTables::of($roles)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                 {$row->id} 
                        </div>";
            })
            ->addColumn('employee', function ($row) {
                return session()->get('locale') === 'ar' ? $row->employee->name : $row->employee->name_en;
            })
            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '
                    <div class="text-center"> 
                <a class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px delete-btn me-1" data-id="' . $row->id . '">
					<i class="ki-outline ki-trash fs-3"></i>
				</a>      
                <a href="' . url("/schedule/payroll/{$row->id}/edit") . '" class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px me-1 edit-btn" data-id="' . $row->id . '" >
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