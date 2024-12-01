<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Cache;
use Modules\Employee\Http\Requests\StoreAllowanceRequest;
use Modules\Employee\Http\Requests\StoreDeductionRequest;
use Modules\Employee\Models\Employee;
use Modules\Employee\Services\AdjustmentAction;

class PayrollAdjustmentController extends Controller
{

    public function storeAllowance(StoreAllowanceRequest $request)
    {
        $allowances_repeater = $request->allowance_repeater;
        // $employee = Employee::find($request->employee_id);

        // dd($request->date, "allowance_{$employee_id}_{$request->date}");
        Cache::forever("allowance_{$request->employee_id}_{$request->date}-01", $allowances_repeater);

        // AdjustmentAction::processPayrollAdjustment($allowances_repeater, $employee, $request->date, 'allowance');
        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    public function storeDeduction(StoreDeductionRequest $request)
    {
        $deductions_repeater = $request->deduction_repeater;
        // $employee = Employee::find($request->employee_id);
        Cache::forever("deduction_{$request->employee_id}_{$request->date}-01", $deductions_repeater);

        // AdjustmentAction::processPayrollAdjustment($deductions_repeater, $employee, $request->date, 'deduction');
        return response()->json(['message' => __('employee::responses.operation_success')]);
    }
}
