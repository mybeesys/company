<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Employee\Classes\EmployeeTable;
use Modules\Employee\Http\Requests\StoreEmployeeRequest;
use Modules\Employee\Http\Requests\UpdateEmployeeRequest;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Permission;
use Modules\Employee\Models\PermissionSet;
use Modules\Employee\Models\Role;
use Modules\Employee\Services\EmployeeActions;
use Modules\Establishment\Models\Establishment;

class EmployeeController extends Controller
{
    protected $establishments;
    protected $permissionSets;
    protected $roles;

    public function __construct()
    {
        $this->establishments = Establishment::all()->select('id', 'name');
        $this->permissionSets = PermissionSet::all()->select('id', 'permissionSetName');
        $this->roles = Role::all()->select('id', 'name');
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
            'employee' => EmployeeActions::getShowEditEmployee($id),
            'roles' => $this->roles,
            'permissionSets' => $this->permissionSets,
            'establishments' => $this->establishments
        ];
    }

    public function index(Request $request)
    {
        $employees = Employee::with('permissions:id,name')->
            select('id', 'name', 'name_en', 'phoneNumber', 'employmentStartDate', 'employmentEndDate', 'isActive', 'deleted_at');
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
        return view('employee::employee.index', compact('columns', 'permissions', 'employees'));
    }

    public function create()
    {
        return view(
            'employee::employee.create',
            ['roles' => $this->roles, 'permissionSets' => $this->permissionSets, 'establishments' => $this->establishments]
        );
    }

    public function store(StoreEmployeeRequest $request)
    {
        DB::transaction(function () use ($request) {
            $filteredRequest = $request->safe()->collect()->filter(function ($item) {
                return isset($item);
            });
            $storeEmployee = new EmployeeActions($filteredRequest);
            $storeEmployee->store();
        });
        return to_route('employees.index')->with('success', __('employee::responses.created_successfully', ['name' => __('employee::fields.employee')]));
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
        DB::transaction(function () use ($request, $employee) {
            $filteredRequest = $request->safe()->collect()->filter(function ($item) {
                return isset($item);
            });
            $updateEmployee = new EmployeeActions($filteredRequest);
            $updateEmployee->update($employee);
        });

        return to_route('employees.index')->with('success', __('employee::responses.updated_successfully', ['name' => __('employee::fields.employee')]));
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
