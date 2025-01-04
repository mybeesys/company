<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Cache;
use Illuminate\Http\Request;
use Modules\Employee\Classes\AdjustmentTable;
use Modules\Employee\Classes\AdjustmentTypeTable;
use Modules\Employee\Http\Requests\StoreAdjustmentRequest;
use Modules\Employee\Http\Requests\StoreAllowanceRequest;
use Modules\Employee\Http\Requests\StoreDeductionRequest;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\PayrollAdjustment;
use Modules\Employee\Models\PayrollAdjustmentType;

class PayrollAdjustmentController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $adjustments = PayrollAdjustment::with('employee', 'adjustmentType')->select('id', 'employee_id', 'adjustment_type_id', 'type', 'amount', 'amount_type', 'description', 'description_en', 'applicable_date', 'apply_once');
            return AdjustmentTable::getAdjustmentTable($adjustments);
        }
        $adjustments_columns = AdjustmentTable::getAdjustmentColumns();
        $adjustments_types_columns = AdjustmentTypeTable::getAdjustmentTypeColumns();

        $employees = Employee::active()->select('id', 'name', 'name_en')->get();
        return view('employee::adjustment.index', compact('adjustments_columns', 'adjustments_types_columns', 'employees'));
    }

    public function store(StoreAdjustmentRequest $request)
    {
        try {
            PayrollAdjustment::updateOrCreate(['id' => $request->validated('id')], $request->safe()->merge([
                'applicable_date' => $request->validated('applicable_date') . "-01",
                'adjustment_type_id' => $request->validated('adjustment_type')
            ])->all());
            return response()->json(['message' => __('employee::responses.operation_success')]);
        } catch (\Throwable $e) {
            \Log::error('adjustment creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function storeAllowanceCache(StoreAllowanceRequest $request)
    {
        $allowances_repeater = $request->allowance_repeater;
        foreach ($allowances_repeater as &$allowance) {
            $adjustment_type_name = PayrollAdjustmentType::find($allowance['adjustment_type'])->name;
            $allowance['adjustment_type_name'] = $adjustment_type_name;
        }
        Cache::forever("allowance_{$request->employee_id}_{$request->date}-01", $allowances_repeater);

        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    public function storeDeductionCache(StoreDeductionRequest $request)
    {
        $deductions_repeater = $request->deduction_repeater;

        foreach ($deductions_repeater as &$deduction) {
            $adjustment_type_name = PayrollAdjustmentType::find($deduction['adjustment_type'])->name;
            $deduction['adjustment_type_name'] = $adjustment_type_name;
        }
        Cache::forever("deduction_{$request->employee_id}_{$request->date}-01", $deductions_repeater);

        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    public function destroy(PayrollAdjustment $adjustment)
    {
        try {
            $adjustment->delete();
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('employee::general.this_element')])]);
        } catch (\Throwable $e) {
            \Log::error('adjustment deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
