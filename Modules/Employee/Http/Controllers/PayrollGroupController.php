<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Modules\Employee\Classes\PayrollGroupTable;
use Modules\Employee\Models\PayrollGroup;

class PayrollGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $payroll_groups = PayrollGroup::all();

            return PayrollGroupTable::getPayrollGroupTable($payroll_groups);
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $payrollGroup = PayrollGroup::findOrFail($id);
        $date = $payrollGroup->date;
        if ($payrollGroup->payment_state === 'final') {
            redirect()->back();
        }
        $establishment_ids = implode(',', $payrollGroup->employees->pluck('establishment_id')->toArray());
        $employee_ids = implode(',', $payrollGroup->payrolls->pluck('employee_id')->toArray());

        return to_route('schedules.payrolls.create', ['employee_ids' => $employee_ids, 'establishment_ids' => $establishment_ids, 'date' => $date]);
    }

    public function confirmPayrollGroup(PayrollGroup $payrollGroup)
    {
        $updated = $payrollGroup->update(['state' => 'final']);
        if ($updated) {
            return response()->json(['message' => __('employee::responses.payroll_group_confirmed')]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function destroy(PayrollGroup $payrollGroup)
    {
        return DB::transaction(function () use ($payrollGroup) {
            $payrollGroup->payrolls()->each(function ($payroll) {
                $payroll->adjustments()->once()->delete();
            });

            $payrollGroup->payrolls()->delete();
            $delete = $payrollGroup->delete();
            if ($delete) {
                return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('employee::fields.payroll_group')])]);
            } else {
                return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
            }
        });
    }
}
