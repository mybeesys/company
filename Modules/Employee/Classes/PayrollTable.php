<?php


namespace Modules\Employee\Classes;
use Cache;
use Carbon\Carbon;
use Modules\Employee\Models\PayrollAdjustment;
use Modules\Employee\Models\PayrollAdjustmentType;
use Modules\Employee\Services\PayrollService;
use Yajra\DataTables\DataTables;

class PayrollTable
{

    public function __construct(private PayrollService $payrollService)
    {
    }

    public static function getIndexPayrollColumns()
    {
        $baseColumns = [
            ["class" => "text-start min-w-50px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "id", "group" => "main"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "employee", "group" => "main"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "payroll_group_name", "group" => "main"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "date", "group" => "main"],
            ["class" => "text-start min-w-75px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "regular_worked_hours", "group" => "main"],
            ["class" => "text-start min-w-75px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "overtime_hours", "group" => "main"],
            ["class" => "text-start min-w-125px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "total_hours", "group" => "main"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "total_worked_days", "group" => "main"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "basic_total_wage", "group" => "main"],
        ];

        $adjustment = PayrollAdjustment::with('adjustmentType')->get()->groupBy('type');
        foreach ($adjustment->get('allowance', []) as $allowance) {
            $baseColumns[] = [
                "class" => "text-start min-w-150px px-3 py-1 border align-middle text-gray-800 fs-6",
                "name" => "allowance_{$allowance->adjustment_type_id}",
                "translated_name" => $allowance->adjustmentType->{get_name_by_lang()},
                "group" => "allowances"
            ];
        }

        $baseColumns[] = [
            "class" => "text-start min-w-150px px-3 py-1 border align-middle text-gray-800 fs-6",
            "name" => "allowances",
            "translated_name" => __('employee::fields.total_allowances'),
            "group" => "allowances"
        ];
        foreach ($adjustment->get('deduction', []) as $deduction) {
            $baseColumns[] = [
                "class" => "text-start min-w-150px px-3 py-1 border align-middle text-gray-800 fs-6",
                "name" => "deduction_{$deduction->adjustment_type_id}",
                "translated_name" => $deduction->adjustmentType->{get_name_by_lang()},
                "group" => "deductions"
            ];
        }

        $baseColumns[] = [
            "class" => "text-start min-w-150px px-3 py-1 border align-middle text-gray-800 fs-6",
            "name" => "deductions",
            "translated_name" => __('employee::fields.total_deductions'),
            "group" => "deductions"
        ];

        $baseColumns[] = [
            "class" => "text-start min-w-150px px-3 py-1 border align-middle text-gray-800 fs-6",
            "name" => "total_wage_due",
            "translated_name" => __('employee::fields.total_wage_due'),
            "group" => "main"
        ];

        $baseColumns[] = [
            "class" => "text-center align-middle min-w-125px border",
            "name" => "actions",
            "translated_name" => __('employee::fields.actions'),
            "group" => "main"
        ];
        return array_values(collect($baseColumns)->unique('name')->toArray());
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
        $adjustment_types = PayrollAdjustmentType::whereHas('adjustments')->withTrashed()->get(['id', 'type'])->groupBy('type');
        
        $datatable = DataTables::of($payrolls)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>{$row->id}</div>";
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
            ->editColumn('regular_worked_hours', function ($row) {
                return $row->regular_worked_hours;
            })
            ->editColumn('overtime_hours', function ($row) {
                return $row->overtime_hours;
            })
            ->editColumn('total_hours', function ($row) {
                return $row->total_hours;
            })
            ->editColumn('total_worked_days', function ($row) {
                return $row->total_worked_days;
            })
            ->editColumn('basic_total_wage', function ($row) {
                return $row->basic_total_wage;
            });
        
        // Add dynamic columns for each allowance type
        foreach ($adjustment_types->get('allowance', []) as $allowance_type) {
            $columnName = 'allowance_' . $allowance_type->id;
            $datatable->addColumn($columnName, function ($row) {
                return $row->allowances()?->sum('amount') ?? 0;
            });
        }

        $datatable->addColumn('allowances', function ($row) {
            return $row->allowances;
        });
        // Add dynamic columns for each deduction type
        foreach ($adjustment_types->get('deduction', []) as $deduction_type) {
            $columnName = 'deduction_' . $deduction_type->id;
            $datatable->addColumn($columnName, function ($row) {
                return $row->deductions()?->sum('amount') ?? 0;
            });
        }

        $datatable->addColumn('deductions', function ($row) {
            return $row->deductions;
        });

        // Add total_wage_due column
        $datatable->addColumn('total_wage_due', function ($row) {
            return $row->total_wage;  // Assuming this is the field name in your database
        });

        $datatable->addColumn('actions', function ($row) {
            return '
            <div class="text-center text-nowrap ">   
                <a href="#" class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px me-1 payroll-print-btn" data-id="' . $row->id . '" >
                    <i class="ki-outline ki-printer fs-2"></i>
                </a>                
            </div>';
        })
            ->rawColumns(['actions', 'id', 'employee']);

        return $datatable->make(true);
    }
}