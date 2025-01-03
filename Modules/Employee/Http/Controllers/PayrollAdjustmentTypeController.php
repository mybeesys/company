<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Classes\AdjustmentTypeTable;
use Modules\Employee\Models\PayrollAdjustmentType;

class PayrollAdjustmentTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $adjustments_types = PayrollAdjustmentType::select('id', 'name', 'name_en', 'type');
            return AdjustmentTypeTable::getAdjustmentTypeTable($adjustments_types);
        }
    }

    public function getAdjustmentsTypes(Request $request)
    {
        $request->validate([
            'type' => 'required|in:allowance,deduction'
        ]);
        $adjustments_types = PayrollAdjustmentType::where('type', $request->type)->select('id', 'name', 'name_en')->get()->toArray();
        return response()->json([
            'data' => $adjustments_types
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id' => 'nullable|exists:emp_payroll_adjustment_types,id',
            'name_lang' => 'required_without:name_en|in:name_en,name',
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'type' => 'nullable|in:allowance,deduction',
            'adjustment_type_type' => 'nullable|in:allowance,deduction'
        ]);

        $type = $request->adjustment_type_type ?? $request->type ?? 'allowance';

        $allowanceType = PayrollAdjustmentType::updateOrCreate(['id' => $request->id], [
            $request->name_lang ?? 'name' => $request->name,
            'name_en' => $request->name_en,
            'type' => $type
        ]);

        return response()->json([
            'id' => $allowanceType->id,
            'message' => __('employee::responses.created_successfully', ['name' => __("employee::fields.new_{$type}_type")])
        ]);
    }

    public function destroy(PayrollAdjustmentType $adjustmentType)
    {
        try {
            $adjustmentType->delete();
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('employee::general.this_element')])]);
        } catch (\Throwable $e) {
            \Log::error('adjustment type deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
