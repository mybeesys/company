<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
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

    public function aasignPermissionsToEmployee(Request $request, Employee $employee)
    {
        $selectAllPermission = Permission::firstWhere('name', 'select_all_permissions');
        $permissions = collect($request->permissions)->map(function ($value) {
            return (int) $value;
        });
        $permissions->contains($selectAllPermission->id) ? $employee->syncPermissions([$selectAllPermission->id]) : $employee->syncPermissions($permissions);
        return response()->json(['message' => __('employee::responses.opreation_success')]);
    }

    public function getEmployee($id)
    {
        $employee = Employee::with(['permissions'])->find($id);
        $allPermissionId = Permission::firstWhere('name', 'select_all_permissions')->id;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }
        return response()->json([
            'success' => true,
            'data' => [
                'employeePermissions' => $employee->permissions->pluck('id'),
                'allPermissionsId' => $allPermissionId,
            ],
        ]);
    }

    function generatePin()
    {
        $number = mt_rand(10000, 99999);
        if ($this->barcodeNumberExists($number)) {
            return $this->generatePin();
        }
        return response()->json(['data' => $number]);
    }

    function barcodeNumberExists($number)
    {
        return Employee::where('pin', $number)->exists();
    }

    public function createLiveValidation(StoreEmployeeRequest $request)
    {
    }

    public function updateLiveValidation(UpdateEmployeeRequest $request)
    {
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
        return redirect()->route('employees.index')->with('success', __('employee::responses.created_successfully', ['name' => __('employee::fields.employee')]));
    }

    public function getShowEditEmployee($id)
    {
        return Employee::with([
            'establishmentsPivot',
            'establishmentsPivot.wage' => function ($query) {
                $query->select('id', 'rate', 'wageType');
            },
            'establishmentsPivot.establishment' => function ($query) {
                $query->select('id', 'name');
            },
            'roles' => function ($query) {
                $query->select('roles.id', 'roles.name');
            },
            'roles.wage' => function ($query) use ($id) {
                $query->select('role_id', 'rate', 'wageType', 'establishment_id')->where('employee_id', $id);
            },
            'administrativeUser.permissionSets'
        ])->findOrFail($id);
    }

    public function getShowEditVariables($id)
    {
        return ['employee' => $this->getShowEditEmployee($id), 'roles' => $this->roles, 'permissionSets' => $this->permissionSets, 'establishments' => $this->establishments];
    }

    public function show(int $id)
    {
        return view(
            'employee::employee.show',
            $this->getShowEditVariables($id)
        );
    }

    public function edit(int $id)
    {
        return view(
            'employee::employee.edit',
            $this->getShowEditVariables($id)
        );
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

        return redirect()->route('employees.index')->with('success', __('employee::responses.updated_successfully', ['name' => __('employee::fields.employee')]));
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
