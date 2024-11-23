<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Classes\PayrollTable;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Payroll;
use Modules\Employee\Models\PayrollAdjustmentType;
use Modules\Establishment\Models\Establishment;
use Validator;

class PayrollController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $timecards = Payroll::with('employee');

            return PayrollTable::getPayrollTable($timecards);
        }
        $establishments = Establishment::select('name', 'id')->get();
        $employees = Employee::where('pos_is_active', true)->select('name', 'name_en', 'id')->get();
        $columns = PayrollTable::getPayrollColumns();
        return view('employee::schedules.payroll.index', compact('columns', 'establishments', 'employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $employeeIds = $request->query('employee_ids') ? explode(',', $request->query('employee_ids')) : null;

        $validator = Validator::make([
            'employee_ids' => $employeeIds,
            'establishment' => $request->query('establishment'),
            'date' => $request->query('date'),
        ], [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:emp_employees,id'],
            'establishment' => ['required', 'integer', 'exists:establishment_establishments,id'],
            'date' => ['required', 'date', 'date_format:Y-m'],
        ]);

        if ($validator->fails()) {
            return to_route('schedules.payrolls.index')
                ->with('error', $validator->errors()->first());
        }
        $allowances_types = PayrollAdjustmentType::where('type', 'allowance')->get();
        $deductions_types = PayrollAdjustmentType::where('type', 'deduction')->get();
        $establishment = Establishment::find($request->query('establishment'))->name;
        $employees = Employee::with('allowances')->whereIn('id', $employeeIds)->get();
        $date = $request->query('date');
        return view('employee::schedules.payroll.create', compact('allowances_types', 'deductions_types', 'employees', 'establishment', 'date'));
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
