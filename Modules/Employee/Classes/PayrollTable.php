<?php


namespace Modules\Employee\Classes;
use Cache;
use Carbon\Carbon;
use Modules\Employee\Services\PayrollService;
use Yajra\DataTables\DataTables;

class PayrollTable
{

    public function __construct(private PayrollService $payrollService)
    {
    }

    public static function getIndexPayrollColumns()
    {
        return [
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "employee"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "payroll_group_name"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "date"],
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "regular_worked_hours"],
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "overtime_hours"],
            ["class" => "text-start min-w-125px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "total_hours"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "total_worked_days"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "basic_total_wage"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "wage_due_before_tax"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "taxes_withheld"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "allowances"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "deductions"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "total_wage_before_tax"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "total_wage"],
        ];
    }

    public function getCreatePayrollTable($date, array $employeeIds, array $establishmentIds)
    {

        $establishmentChanges = request('establishment_changes', []);

        $employees = $this->payrollService->fetchEmployees($employeeIds, $establishmentIds);
        
        $carbonMonth = Carbon::createFromFormat('Y-m', $date);
        $payrollData = $employees->map(function ($employee) use ($carbonMonth, $date, $establishmentChanges) {
            if (isset($establishmentChanges[$employee->id])) {
                $employee->establishment_id = $establishmentChanges[$employee->id];
            }
            $employeePayrollData = $this->payrollService->calculateEmployeePayroll($employee, $carbonMonth);
            Cache::forever("payroll_table_{$date}_{$employee->id}", $employeePayrollData);
            return $employeePayrollData;
        });

        return DataTables::of($payrollData)
            ->rawColumns($payrollData->isNotEmpty() ? array_keys($payrollData->first()) : [])
            ->make(true);
    }

    public static function getIndexPayrollTable($payrolls)
    {
        return DataTables::of($payrolls)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                 {$row->id} 
                        </div>";
            })
            ->addColumn('employee', function ($row) {
                return $row?->employee?->{get_name_by_lang()};
            })
            ->addColumn('date', function ($row) {
                return $row?->payrollGroup?->date;
            })
            ->addColumn('payroll_group_name', function ($row) {
                return $row?->payrollGroup?->name;
            })
            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '
                    <div class="text-center text-nowrap ">   
                <a href="#" class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px me-1 edit-btn" data-id="' . $row->id . '" >
					<i class="ki-outline ki-printer fs-2"></i>
				</a>                
                </div>';
                    return $actions;
                }
            )
            ->rawColumns(['actions', 'id', 'employee'])
            ->make(true);
    }
}