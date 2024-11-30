<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
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
            $payroll_groups = PayrollGroup::with('establishments');

            return PayrollGroupTable::getPayrollGroupTable($payroll_groups);
        }
        $columns = PayrollGroupTable::getPayrollGroupColumns();
        return view('employee::schedules.payroll-group.index', compact('columns'));
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
        $payrollGroup = PayrollGroup::findOrFail($id);
        $date = $payrollGroup->date;
        if ($payrollGroup->payment_state === 'final') {
            redirect()->back();
        }
        $establishment_ids = implode(',', $payrollGroup->establishments->pluck('id')->toArray());
        $employee_ids = implode(',', $payrollGroup->payrolls->pluck('employee_id')->toArray());

        return to_route('schedules.payrolls.create', ['employee_ids' => $employee_ids, 'establishment_ids' => $establishment_ids, 'date' => $date]);
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
