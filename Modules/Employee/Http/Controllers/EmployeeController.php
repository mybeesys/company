<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Log;
use Modules\Employee\Classes\EmployeeTable;
use Modules\Employee\Http\Requests\StoreEmployeeRequest;
use Modules\Employee\Http\Requests\UpdateEmployeeRequest;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\PayrollAdjustmentType;
use Modules\Employee\Models\Permission;
use Modules\Employee\Models\Role;
use Modules\Employee\Services\DashboardRoleService;
use Modules\Employee\Services\EmployeeActions;
use Modules\Establishment\Models\Establishment;

class EmployeeController extends Controller
{
    protected $establishments;
    protected $dashboardRoles;
    protected $posRoles;
    protected $allowances_types;

    public function __construct()
    {
        $this->establishments = Establishment::all()->select('id', 'name');
        $this->dashboardRoles = Role::where('type', 'ems')->get(['id', 'name']);
        $this->posRoles = Role::where('type', 'pos')->get(['id', 'name']);
        $this->allowances_types = PayrollAdjustmentType::allowance()->get();
    }

    function generatePin()
    {
        $number = mt_rand(10000, 99999);
        if (Employee::where('pin', $number)->exists()) {
            return $this->generatePin();
        }
        return response()->json(['data' => $number]);
    }

    public function createLiveValidation(StoreEmployeeRequest $request)
    {
    }

    public function updateLiveValidation(UpdateEmployeeRequest $request)
    {
    }

    private function getEmployeeViewData(int $id): array
    {
        return [
            'employee' => Employee::with(['allowances' => fn($query) => $query->always(), 'dashboardRoles', 'posRoles'])->findOrFail($id),
            'posRoles' => $this->posRoles,
            'dashboardRoles' => $this->dashboardRoles,
            'establishments' => $this->establishments,
            'allowances_types' => $this->allowances_types
        ];
    }

    public function getEmployeeEstablishments($id)
    {
        $establishments = Employee::with('wageEstablishments')->findOrFail($id)->wageEstablishments->pluck('id')->unique();
        return response()->json(['data' => $establishments]);
    }

    public function index(Request $request)
    {
        $employees = Employee::with('permissions:id,name')->
            select('id', 'name', 'name_en', 'phone_number', 'employment_start_date', 'employment_end_date', 'pos_is_active', 'ems_access', 'deleted_at');
        if ($request->ajax()) {

            if ($request->has('deleted_records') && !empty($request->deleted_records)) {
                $request->deleted_records == 'only_deleted_records'
                    ? $employees->onlyTrashed()
                    : ($request->deleted_records == 'with_deleted_records' ? $employees->withTrashed() : null);
            }
            return EmployeeTable::getEmployeeTable($employees);
        }
        $employees = $employees->get();
        $columns = EmployeeTable::getEmployeeColumns();
        $permissions = Permission::where('type', 'pos')->orderByRaw('FIELD(name, "select_all_permissions") DESC')->get(['id', 'name', 'name_ar', 'description', 'description_ar']);

        $modules = DashboardRoleService::getModulesPermissions();

        return view('employee::employee.index', compact('columns', 'permissions', 'employees', 'modules'));
    }

    public function create()
    {
        return view(
            'employee::employee.create',
            ['posRoles' => $this->posRoles, 'dashboardRoles' => $this->dashboardRoles, 'establishments' => $this->establishments, 'allowances_types' => $this->allowances_types]
        );
    }

    public function store(StoreEmployeeRequest $request)
    {
        return DB::transaction(function () use ($request) {
            try {
                $filteredRequest = $request->safe()->collect()->filter(function ($item) {
                    return isset($item);
                });
                $storeEmployee = new EmployeeActions($filteredRequest);
                $storeEmployee->store();
                return to_route('employees.index')->with('success', __('employee::responses.created_successfully', ['name' => __('employee::fields.employee')]));
            } catch (\Throwable $e) {
                Log::error('Employee creation failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->back()->with('error', __('employee::responses.something_wrong_happened'));
            }
        });
    }

    public function show(int $id)
    {
        return view('employee::employee.show', $this->getEmployeeViewData($id));
    }

    public function edit(int $id)
    {
        return view('employee::employee.edit', $this->getEmployeeViewData($id));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        return DB::transaction(function () use ($request, $employee) {
            try {
                $filteredRequest = $request->safe()->collect()->filter(function ($item) {
                    return isset($item);
                });
                $updateEmployee = new EmployeeActions($filteredRequest);
                $updateEmployee->update($employee);
                return to_route('employees.index')->with('success', __('employee::responses.updated_successfully', ['name' => __('employee::fields.employee')]));
            } catch (\Throwable $e) {
                Log::error('Employee updating failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->back()->with('error', __('employee::responses.something_wrong_happened'));
            }
        });
    }

    public function restore($id)
    {
        $restore = Employee::where('id', $id)->restore();
        if ($restore) {
            return response()->json(['message' => __('employee::responses.employee_restored_successfully')]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function softDelete(Employee $employee)
    {
        $delete = $employee->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('employee::fields.employee')])]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function forceDelete($id)
    {
        $delete = Employee::where('id', $id)->forceDelete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('employee::fields.employee')])]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
