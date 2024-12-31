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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name_lang' => 'required|in:name_en,name',
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:allowance,deduction'
        ]);
        $type = $request->type ?? 'allowance';
        $allowanceType = PayrollAdjustmentType::create([
            $request->name_lang => $request->name,
            'type' => $type
        ]);

        return response()->json([
            'id' => $allowanceType->id,
            'message' => __('employee::responses.created_successfully', ['name' => __("employee::fields.new_{$type}_type")])
        ]);
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
