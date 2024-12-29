<?php


namespace Modules\Employee\Classes;
use Cache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Employee\Models\PayrollAdjustment;
use Modules\Employee\Services\PayrollService;
use Yajra\DataTables\DataTables;

class PayrollTable
{

    public function __construct(private PayrollService $payrollService)
    {
    }

    // public static function getIndexPayrollColumns()
    // {
    //     $baseColumns = [
    //         ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "employee", "translated_name" => __('employee::fields.employee')],
    //         ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "payroll_group_name", "translated_name" => __('employee::fields.payroll_group_name')],
    //         ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "date", "translated_name" => __('employee::fields.date')],
    //         ["class" => "text-start min-w-75px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "regular_worked_hours", "translated_name" => __('employee::fields.regular_worked_hours')],
    //         ["class" => "text-start min-w-75px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "overtime_hours", "translated_name" => __('employee::fields.overtime_hours')],
    //         ["class" => "text-start min-w-125px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "total_hours", "translated_name" => __('employee::fields.total_hours')],
    //         ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "total_worked_days", "translated_name" => __('employee::fields.total_worked_days')],
    //         ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "basic_total_wage", "translated_name" => __('employee::fields.basic_total_wage')],
    //     ];

    //     $adjustmentTypes = PayrollAdjustment::with('adjustmentType')->get()->groupBy('type');
    //     foreach ($adjustmentTypes->get('allowance', []) as $allowance) {
    //         $baseColumns[] = [
    //             "class" => "text-start min-w-150px px-3 py-1 border align-middle text-gray-800 fs-6",
    //             "name" => "allowance_{$allowance->id}",
    //             "translated_name" => $allowance->adjustmentType->{get_name_by_lang()},
    //         ];
    //     }

    //     $baseColumns[] = [
    //         "class" => "text-start min-w-150px px-3 py-1 border align-middle text-gray-800 fs-6",
    //         "name" => "allowances",
    //         "translated_name" => __('employee::fields.total_allowances'),
    //     ];

    //     // Add columns for each deduction type
    //     foreach ($adjustmentTypes->get('deduction', []) as $deduction) {
    //         $baseColumns[] = [
    //             "class" => "text-start min-w-150px px-3 py-1 border align-middle text-gray-800 fs-6",
    //             "name" => "deduction_{$deduction->id}",
    //             "translated_name" => $allowance->adjustmentType->{get_name_by_lang()},
    //         ];
    //     }

    //     $baseColumns[] = [
    //         "class" => "text-start min-w-150px px-3 py-1 border align-middle text-gray-800 fs-6",
    //         "name" => "deductions",
    //         "translated_name" => __('employee::fields.total_deductions'),
    //     ];

    //     // Add remaining columns
    //     $baseColumns = array_merge($baseColumns, [
    //         ["class" => "text-start min-w-150px px-3 py-1 border align-middle text-gray-800 fs-6", "name" => "total_wage_due"],
    //     ]);

    //     return $baseColumns;
    // }

    public static function getIndexPayrollColumns()
    {
        $baseColumns = [
            ["class" => "text-start min-w-50px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "id", "translated_name" => __('employee::fields.id'), "group" => "main"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "employee", "translated_name" => __('employee::fields.employee'), "group" => "main"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "payroll_group_name", "translated_name" => __('employee::fields.payroll_group_name'), "group" => "main"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "date", "translated_name" => __('employee::fields.date'), "group" => "main"],
            ["class" => "text-start min-w-75px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "regular_worked_hours", "translated_name" => __('employee::fields.regular_worked_hours'), "group" => "main"],
            ["class" => "text-start min-w-75px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "overtime_hours", "translated_name" => __('employee::fields.overtime_hours'), "group" => "main"],
            ["class" => "text-start min-w-125px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "total_hours", "translated_name" => __('employee::fields.total_hours'), "group" => "main"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "total_worked_days", "translated_name" => __('employee::fields.total_worked_days'), "group" => "main"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6", "name" => "basic_total_wage", "translated_name" => __('employee::fields.basic_total_wage'), "group" => "main"],
        ];

        $adjustmentTypes = PayrollAdjustment::with('adjustmentType')->get()->groupBy('type');
        foreach ($adjustmentTypes->get('allowance', []) as $allowance) {
            $baseColumns[] = [
                "class" => "text-start min-w-150px px-3 py-1 border align-middle text-gray-800 fs-6",
                "name" => "allowance_{$allowance->id}",
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

        foreach ($adjustmentTypes->get('deduction', []) as $deduction) {
            $baseColumns[] = [
                "class" => "text-start min-w-150px px-3 py-1 border align-middle text-gray-800 fs-6",
                "name" => "deduction_{$deduction->id}",
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

        return $baseColumns;
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
        // Get all unique adjustment types
        $adjustmentTypes = PayrollAdjustment::select('id', 'description', 'type')
            ->get()
            ->groupBy('type');

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
        foreach ($adjustmentTypes->get('allowance', []) as $allowance) {
            $columnName = 'allowance_' . $allowance->id;
            $datatable->addColumn($columnName, function ($row) use ($allowance) {
                return $row->adjustments->where('id', $allowance->id)->first()?->amount ?? 0;
            });
        }

        $datatable->addColumn('allowances', function ($row) {
            return $row->allowances;
        });

        // Add dynamic columns for each deduction type
        foreach ($adjustmentTypes->get('deduction', []) as $deduction) {
            $columnName = 'deduction_' . $deduction->id;
            $datatable->addColumn($columnName, function ($row) use ($deduction) {
                return $row->adjustments->where('id', $deduction->id)->first()?->amount ?? 0;
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
                <a href="#" class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px me-1 edit-btn" data-id="' . $row->id . '" >
                    <i class="ki-outline ki-printer fs-2"></i>
                </a>                
            </div>';
        })
            ->rawColumns(['actions', 'id', 'employee']);

        return $datatable->make(true);
    }
}