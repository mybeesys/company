<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Employee\Classes\PayrollTable;
use Modules\Employee\Http\Requests\StoreAllowanceRequest;
use Modules\Employee\Http\Requests\StoreDeductionRequest;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Payroll;
use Modules\Employee\Models\PayrollAdjustment;
use Modules\Employee\Models\PayrollAdjustmentType;
use Modules\Employee\Models\Role;
use Modules\Employee\Services\PayrollService;
use Modules\Employee\Services\ShiftService;
use Modules\Employee\Services\TimeSheetRuleService;
use Modules\Employee\Services\WageCalculationService;
use Modules\Establishment\Models\Establishment;
use Validator;

class PayrollController extends Controller
{


    public function __construct(protected PayrollTable $payrollTable)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $payrolls = Payroll::with('employee');

            return PayrollTable::getIndexPayrollTable($payrolls);
        }
        $establishments = Establishment::select('name', 'id')->get();
        $employees = Employee::where('pos_is_active', true)->select('name', 'name_en', 'id')->get();
        $columns = PayrollTable::getIndexPayrollColumns();
        return view('employee::schedules.payroll.index', compact('columns', 'establishments', 'employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $applied_deduction = true;
        $employeeIds = $request->query('employee_ids') ? explode(',', $request->query('employee_ids')) : null;
        $establishmentIds = $request->query('establishment_ids') ? explode(',', $request->query('establishment_ids')) : null;
        $validator = Validator::make([
            'employee_ids' => $employeeIds,
            'establishment_ids' => $establishmentIds,
            'date' => $request->query('date'),
        ], [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:emp_employees,id'],
            'establishment_ids' => ['required', 'array', 'min:1'],
            'establishment_ids.*' => ['integer', 'integer', 'exists:establishment_establishments,id'],
            'date' => ['required', 'date', 'date_format:Y-m'],
        ]);
        if ($validator->fails()) {
            return to_route('schedules.payrolls.index')->with('error', $validator->errors()->first());
        }
        $date = $request->query('date');

        if ($request->ajax()) {
            return $this->payrollTable->getCreatePayrollTable($date, $employeeIds, $establishmentIds);
        }

        $allowances_types = PayrollAdjustmentType::where('type', 'allowance')->get();
        $deductions_types = PayrollAdjustmentType::where('type', 'deduction')->get();
        $establishments = Establishment::whereIn('id', $establishmentIds)->get();
        $roles = Role::all();
        $columns = PayrollTable::getCreatePayrollColumns();
        return view('employee::schedules.payroll.create', compact('allowances_types', 'deductions_types', 'establishments', 'roles', 'columns', 'date'));
    }

    public function processPayrollAdjustment($adjustment_repeater, Employee $employee, string $date, $type)
    {
        if ($adjustment_repeater) {
            foreach ($adjustment_repeater as $adjustment) {
                if (isset($adjustment["{$type}_id"])) {
                    $ids[] = $adjustment["{$type}_id"];
                    PayrollAdjustment::where('id', $adjustment["{$type}_id"])->update([
                        'adjustment_type_id' => $adjustment['adjustment_type'],
                        'amount' => $adjustment['amount'],
                        'amount_type' => $adjustment['amount_type'],
                    ]);
                } else {
                    $id = PayrollAdjustment::create([
                        'employee_id' => $employee->id,
                        'adjustment_type_id' => $adjustment['adjustment_type'],
                        'amount' => $adjustment['amount'],
                        'amount_type' => $adjustment['amount_type'],
                        'apply_once' => true,
                        'applicable_date' => "{$date}-01",
                        'type' => $type
                    ])->id;
                    $ids[] = $id;
                }
            }
            if ($type == 'allowance') {
                $general_allowances = $employee->allowances()->always()->whereNotIn('id', $ids)->get();
                foreach ($general_allowances as $allowance) {
                    $allowance->update([
                        'applicable_date' => Carbon::createFromFormat('Y-m', $date)->addMonth()->startOfMonth(),
                    ]);
                }
                $employee->allowances()->once()->whereNotIn('id', $ids)->where('applicable_date', "{$date}-01")->delete();
            } else {
                $employee->deductions()->whereNotIn('id', $ids)->where('applicable_date', "{$date}-01")->once()->delete();
            }

        } else {
            if ($type == 'allowance') {
                $general_allowances = $employee->allowances()->always()->get();
                $employee->allowances()->where('applicable_date', "{$date}-01")->once()->delete();
                foreach ($general_allowances as $allowance) {
                    $allowance->update([
                        'applicable_date' => Carbon::createFromFormat('Y-m', $date)->addMonth()->startOfMonth(),
                    ]);
                }
            } else {
                $employee->deductions()->where('applicable_date', "{$date}-01")->once()->delete();
            }
        }
    }

    public function storeAllowance(StoreAllowanceRequest $request)
    {
        $allowances_repeater = $request->allowance_repeater;
        $employee = Employee::find($request->employee_id);
        $this->processPayrollAdjustment($allowances_repeater, $employee, $request->date, 'allowance');
        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    public function storeDeduction(StoreDeductionRequest $request)
    {
        $deductions_repeater = $request->deduction_repeater;
        $employee = Employee::find($request->employee_id);
        $this->processPayrollAdjustment($deductions_repeater, $employee, $request->date, 'deduction');
        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('employee::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('employee::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
