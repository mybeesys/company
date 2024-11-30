<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Employee\Http\Requests\StoreAllowanceRequest;
use Modules\Employee\Http\Requests\StoreDeductionRequest;
use Modules\Employee\Models\Employee;
use Modules\Employee\Services\AdjustmentAction;

class PayrollAdjustmentController extends Controller
{

    public function storeAllowance(StoreAllowanceRequest $request)
    {
        $allowances_repeater = $request->allowance_repeater;
        $employee = Employee::find($request->employee_id);
        AdjustmentAction::processPayrollAdjustment($allowances_repeater, $employee, $request->date, 'allowance');
        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    public function storeDeduction(StoreDeductionRequest $request)
    {
        $deductions_repeater = $request->deduction_repeater;
        $employee = Employee::find($request->employee_id);
        
        AdjustmentAction::processPayrollAdjustment($deductions_repeater, $employee, $request->date, 'deduction');
        return response()->json(['message' => __('employee::responses.operation_success')]);
    }
}
