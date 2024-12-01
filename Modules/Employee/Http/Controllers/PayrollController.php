<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Cache;
use DB;
use Illuminate\Http\Request;
use Log;
use Modules\Employee\Classes\PayrollTable;
use Modules\Employee\Http\Requests\CreatePayrollRequest;
use Modules\Employee\Http\Requests\StorePayrollRequest;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Payroll;
use Modules\Employee\Models\PayrollAdjustmentType;
use Modules\Employee\Models\PayrollGroup;
use Modules\Employee\Models\Role;
use Modules\Employee\Services\PayrollAction;
use Modules\Establishment\Models\Establishment;

class PayrollController extends Controller
{


    public function __construct(protected PayrollTable $payrollTable, protected PayrollAction $payrollAction)
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

    public function create(CreatePayrollRequest $request)
    {
        $employeeIds = collect($request->validated('employee_ids'));
        $establishmentIds = $request->validated('establishment_ids');
        $date = $request->validated('date');

        $lockKey = 'payroll_creation_lock_' . $date . '_(' . implode('-', $establishmentIds) . ')';
        $lock = Cache::lock($lockKey, 30);

        if ($request->ajax()) {
            return $this->payrollTable->getCreatePayrollTable($date, $employeeIds->toArray(), $establishmentIds);
        }

        if (!$lock->get()) {
            return to_route('schedules.payrolls.index')
                ->with('error', __('employee::responses.payroll_creation_in_progress'));
        }

        $payroll_group = PayrollGroup::with(['establishments', 'payrolls'])
            ->whereHas('establishments', fn($query) => $query->whereIn('id', $establishmentIds))
            ->where('date', $date)
            ->first();

        if ($payroll_group?->state === 'draft') {
            $payroll_group_id = $payroll_group->id;
        } else {
            if ($payroll_group) {
                $ids_to_remove = $payroll_group->payrolls->pluck('employee_id');
                $employeeIds = $employeeIds->diff($ids_to_remove);
            }
            $payroll_group_id = null;
        }

        $allowances_types = PayrollAdjustmentType::where('type', 'allowance')->get();
        $deductions_types = PayrollAdjustmentType::where('type', 'deduction')->get();
        $establishments = Establishment::whereIn('id', $establishmentIds)->pluck('name')->toArray();
        $roles = Role::all();
        $columns = PayrollTable::getCreatePayrollColumns();

        return view('employee::schedules.payroll.create', compact(
            'allowances_types',
            'deductions_types',
            'establishments',
            'roles',
            'columns',
            'date',
            'payroll_group'
        ));
    }


    public function extendLock(Request $request)
    {
        $lockKey = $request->input('lockKey');
        $lock = Cache::lock($lockKey, 20);

        if ($lock->get()) {
            return response()->json(['status' => 'lock extended'], 200);
        }

        return response()->json(['status' => 'lock not acquired'], 400);
    }


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

            DB::transaction(
                function () use ($employeeIds, $date, $request, $establishmentIds, $payroll_group_id) {
                    $this->payrollAction->storePayroll($employeeIds, $date, $request, $establishmentIds, $payroll_group_id);
                }
            );

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
}
