<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Modules\Employee\Classes\EmployeeTable;
use Modules\Employee\Http\Requests\StoreEmployeeRequest;
use Modules\Employee\Http\Requests\UpdateEmployeeRequest;
use Modules\Employee\Models\Employee;
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
        if ($request->ajax()) {
            $employees = Employee::
                select('id', 'name', 'name_en', 'phoneNumber', 'employmentStartDate', 'employmentEndDate', 'isActive', 'deleted_at');

            if ($request->has('deleted_records') && !empty($request->deleted_records)) {
                $request->deleted_records == 'only_deleted_records'
                    ? $employees->onlyTrashed()
                    : ($request->deleted_records == 'with_deleted_records' ? $employees->withTrashed() : null);
            }
            return EmployeeTable::getEmployeeTable($employees);
        }
        $columns = EmployeeTable::getEmployeeColumns();
        return view('employee::employee.index', compact('columns'));
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
            $filteredRequest = $request->safe()->collect()->filter();
            $storeEmployee = new EmployeeActions($filteredRequest);
            $storeEmployee->store();
        });
        return redirect()->route('employees.index')->with('success', __('employee::responses.employee_created_successfully'));
    }

    public function show(Employee $employee)
    {
        return view('employee::employee.show', compact('employee'));
    }

    public function edit(int $id)
    {
        $employee = Employee::with([
            'establishmentsPivot',
            'establishmentsPivot.wage' => function ($query) {
                $query->select('id', 'rate');
            },
            'establishmentsPivot.establishment' => function ($query) {
                $query->select('id', 'name');
            },
            'roles' => function ($query) {
                $query->select('roles.id', 'roles.name');
            },
            'roles.wage' => function ($query) use ($id) {
                $query->select('role_id', 'rate', 'establishment_id')->where('employee_id', $id);
            },
            'administrativeUser.permissionSets'
        ])->findOrFail($id);
        return view(
            'employee::employee.edit',
            ['employee' => $employee, 'roles' => $this->roles, 'permissionSets' => $this->permissionSets, 'establishments' => $this->establishments]
        );
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        DB::transaction(function () use ($request, $employee) {
            $filteredRequest = $request->safe()->collect()->filter();
            $updateEmployee = new EmployeeActions($filteredRequest);
            $updateEmployee->update($employee);
        });

        return redirect()->route('employees.index')->with('success', __('employee::responses.employee_updated_successfully'));
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
            return response()->json(['message' => __('employee::responses.employee_deleted_successfully')]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function forceDelete($id)
    {
        $delete = Employee::where('id', $id)->forceDelete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.employee_deleted_successfully')]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
