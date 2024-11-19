<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Employee\Models\AdministrativeUser;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Permission;
use Modules\Employee\Services\DashboardRoleActions;
use Modules\Employee\Services\PosRoleActions;

class PermissionController extends Controller
{

    public function assignPosPermissionsToEmployee(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'pos_permissions' => ['array', 'nullable'],
            'pos_permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('type', 'pos')]
        ]);
        $permissions = new PosRoleActions(collect($validated));
        $permissions->storeUpdateRolePermissions($employee);
        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    public function getEmployeePosPermissions($id)
    {
        $employee = Employee::with([
            'permissions' => function ($query) {
                $query->where('type', 'pos');
            }
        ])->find($id);
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

    public function assignDashboardPermissionsToUser(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'dashboard_permissions' => ['array', 'nullable'],
            'dashboard_permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('type', 'ems')]
        ]);
        $permissions = new DashboardRoleActions(collect($validated));
        $permissions->storeUpdateRolePermissions($employee, false);

        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    public function getEmployeeDashboardPermissions($id)
    {
        $user = Employee::with([
            'permissions' => function ($query) {
                $query->where('type', 'ems');
            }
        ])->firstWhere('id', $id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }
        return response()->json([
            'success' => true,
            'data' => [
                'userPermissions' => $user->permissions->pluck('id'),
            ],
        ]);
    }
}
