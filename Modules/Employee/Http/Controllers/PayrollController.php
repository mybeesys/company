<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Cache;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Log;
use Modules\Employee\Classes\PayrollTable;
use Modules\Employee\Http\Requests\CreatePayrollRequest;
use Modules\Employee\Http\Requests\StoreAllowanceRequest;
use Modules\Employee\Http\Requests\StoreDeductionRequest;
use Modules\Employee\Http\Requests\StorePayrollRequest;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Payroll;
use Modules\Employee\Models\PayrollAdjustment;
use Modules\Employee\Models\PayrollAdjustmentType;
use Modules\Employee\Models\PayrollGroup;
use Modules\Employee\Models\Role;
use Modules\Establishment\Models\Establishment;

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
            $payrolls = Payroll::with('employee', 'payrollGroup');

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
    public function create(CreatePayrollRequest $request)
    {
        $employeeIds = $request->validated('employee_ids');
        $establishmentIds = $request->validated('establishment_ids');
        $date = $request->validated('date');


        $payroll_group = PayrollGroup::with(['establishments', 'payrolls'])->whereHas('establishments', fn($query) => $query->whereIn('id', $establishmentIds))
            ->where('date', $date)->first();

        if ($payroll_group?->state === 'draft') {
            $payroll_group_id = $payroll_group->id;
        } else {
            if ($payroll_group) {
                $ids_to_remove = $payroll_group->payrolls->pluck('employee_id');
                $employeeIds = collect($employeeIds);
                $employeeIds = $employeeIds->diff($ids_to_remove);
            }
            $payroll_group_id = null;
        }

        if ($request->ajax()) {
            return $this->payrollTable->getCreatePayrollTable($date, $employeeIds, $establishmentIds);
        }

        $allowances_types = PayrollAdjustmentType::where('type', 'allowance')->get();
        $deductions_types = PayrollAdjustmentType::where('type', 'deduction')->get();
        $establishments = Establishment::whereIn('id', $establishmentIds)->pluck('name')->toArray();
        $roles = Role::all();
        $columns = PayrollTable::getCreatePayrollColumns();

        return view('employee::schedules.payroll.create', compact('allowances_types', 'deductions_types', 'establishments', 'roles', 'columns', 'date', 'payroll_group'));

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
    public function store(StorePayrollRequest $request)
    {
        $employeeIds = $request->validated('employee_ids');
        $establishmentIds = $request->validated('establishment_ids');
        $date = $request->validated('date');
        $payroll_group_id = $request->validated('payroll_group_id');

        $lockKey = 'payroll_lock_' . $date . '_' . implode('-', $employeeIds);

        $lock = Cache::lock($lockKey, 60);

        try {
            if (!$lock->get()) {
                return to_route('schedules.payrolls.index')->with('error', __('employee::responses.duplicate_payroll'));
            }

            $payrollDataExists = collect($employeeIds)->every(function ($employeeId) use ($date) {
                return Cache::has("payroll_table_{$date}_" . $employeeId);
            });

            if (!$payrollDataExists) {
                $lock->release();
                return to_route('schedules.payrolls.index')->with('error', __('employee::responses.duplicate_payroll'));
            }

            DB::transaction(function () use ($employeeIds, $date, $request, $establishmentIds, $payroll_group_id) {

                $payroll_group = PayrollGroup::updateOrCreate(['id' => $payroll_group_id], [
                    'name' => $request->validated('payroll_group_name'),
                    'date' => $request->validated('date'),
                    'state' => $request->validated('payroll_group_state'),
                    'payment_status' => 'due',
                    'gross_total' => 0
                ]);
                $payroll_group_id = $payroll_group->id;
                $gross_total = 0;
                $net_total = 0;
                foreach ($employeeIds as $employeeId) {
                    $payrollData = Cache::get("payroll_table_{$date}_{$employeeId}");

                    $payroll = Payroll::updateOrCreate(['employee_id' => $employeeId, 'payroll_group_id' => $payroll_group_id], [
                        'regular_worked_hours' => $payrollData['regular_worked_hours'],
                        'overtime_hours' => $payrollData['overtime_hours'],
                        'total_hours' => $payrollData['total_hours'],
                        'total_worked_days' => $payrollData['total_worked_days'],
                        'basic_total_wage' => $payrollData['basic_total_wage'],
                        'wage_due_before_tax' => $payrollData['wage_due_before_tax'],
                        'allowances' => $payrollData['allowances'],
                        'deductions' => $payrollData['deductions'],
                        'total_wage_before_tax' => $payrollData['total_wage_before_tax'],
                        'total_wage' => $payrollData['total_wage'],
                    ]);
                    $net_total += $payrollData['total_wage'];
                    $gross_total += $payrollData['total_wage_before_tax'];
                }

                $payroll_group->update([
                    'net_total' => $net_total,
                    'gross_total' => $gross_total
                ]);
                $allowances_ids = $payrollData['allowances_ids'];
                $deductions_ids = $payrollData['deductions_ids'];

                $payroll->adjustments()->sync(array_merge($allowances_ids, $deductions_ids));

                $payroll_group->establishments()->sync($establishmentIds);
                collect($employeeIds)->each(function ($employeeId) use ($date) {
                    Cache::forget("payroll_table_{$date}" . $employeeId);
                });
            });

            return redirect()->route('schedules.payrolls.index')->with('success', __('employee::responses.payroll_created_successfully'));

        } catch (\Exception $e) {
            if (isset($lock)) {
                $lock->release();
            }
            Log::error('Payroll creation failed: ' . $e->getMessage());
            return back()->with('error', __('employee::responses.something_wrong_happened'));
        } finally {
            if (isset($lock)) {
                $lock->release();
            }
        }
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
