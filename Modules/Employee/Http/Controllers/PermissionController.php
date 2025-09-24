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
use Illuminate\Support\Facades\Log;
use DB;

class PermissionController extends Controller
{

    public function assignPosPermissionsToEmployee(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'pos_permissions' => ['array', 'nullable'],
            'pos_permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('type', 'pos')],
            'pos_role_ids' => ['array', 'nullable'],
            'pos_role_ids.*' => ['integer', Rule::exists('roles', 'id')]
        ]);
        $permissions = new PosRoleActions(collect($validated));
        $permissions->storeUpdateRolePermissions($employee, false);
        if (isset($validated['pos_role_ids'])) {
            $establishmentId = $employee->establishment_id;

            // Use a model or DB facade to delete old roles
            DB::table('emp_employee_establishments_roles')
                ->where('employee_id', $employee->id)
                ->where('establishment_id', $establishmentId)
                ->delete();

            $uniqueRoleIds = array_unique($validated['pos_role_ids']);
            // Prepare data for insertion
            $dataToInsert = [];
            foreach ($uniqueRoleIds as $roleId) {
                $dataToInsert[] = [
                    'employee_id' => $employee->id,
                    'establishment_id' => $establishmentId,
                    'role_id' => $roleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert new roles
            if (!empty($dataToInsert)) {
                DB::table('emp_employee_establishments_roles')->insert($dataToInsert);
            }
        }
        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    public function getEmployeePosPermissions($employeeId)
    {
        // 1. Fetch employee with its permissions and the new posRoles relationship
        $employee = Employee::with(['posRoles' => function ($query) {}, 'permissions'])->find($employeeId);

        // Handle the case where the employee is not found
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        // 2. Get all permission IDs directly assigned to the employee
        $employeePermissions = $employee->permissions->pluck('id')->toArray();

        // 3. Safely get the "select all" permission ID
        $allPermissions = Permission::where('name', 'pos_select_all_permissions')->first();
        $allPermissionsId = $allPermissions ? $allPermissions->id : null;

        // 4. Collect the IDs of all assigned POS roles
        $posRoleIds = $employee->posRoles->pluck('id')->toArray();

        // 5. Collect all permissions from all assigned POS roles
        $rolePermissions = collect();
        foreach ($employee->posRoles as $role) {
            $rolePermissions = $rolePermissions->merge($role->permissions->pluck('id'));
        }

        // 6. Merge the employee's direct permissions and the combined role permissions
        $combinedPermissions = array_unique(array_merge($employeePermissions, $rolePermissions->toArray()));

        return response()->json([
            'success' => true,
            'data' => [
                'employeePermissions' => $combinedPermissions,
                'allPermissionsId' => $allPermissionsId,
                'pos_role_ids' => $posRoleIds, // Return an array of role IDs
            ],
        ]);
    }

    public function assignDashboardPermissionsToUser(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'dashboard_permissions' => ['array', 'nullable'],
            'dashboard_permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('type', 'ems')],
            'dashboard_role_ids' => ['array', 'nullable'],
            'dashboard_role_ids.*' => ['integer', Rule::exists('roles', 'id')]
        ]);

        // Handle permissions
        $permissions = new DashboardRoleActions(collect($validated));
        $permissions->storeUpdateRolePermissions($employee, false);

        // If dashboard_role_ids are provided, handle them
        if (!empty($validated['dashboard_role_ids'])) {
            $establishmentId = $employee->establishment_id;

            // Delete old roles assigned to the employee in the current establishment
            DB::table('emp_employee_establishments_roles')
                ->where('employee_id', $employee->id)
                ->where('establishment_id', $establishmentId)
                ->delete();

            $uniqueRoleIds = array_unique($validated['dashboard_role_ids']);

            // Prepare data for insertion
            $dataToInsert = [];
            foreach ($uniqueRoleIds as $roleId) {
                $dataToInsert[] = [
                    'employee_id' => $employee->id,
                    'establishment_id' => $establishmentId,
                    'role_id' => $roleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert new roles if data is not empty
            if (!empty($dataToInsert)) {
                DB::table('emp_employee_establishments_roles')->insert($dataToInsert);
            }
        }

        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    public function getEmployeeDashboardPermissions($id)
    {
        $employee = Employee::with([
            'dashboardRoles' => function ($query) {
                $query->with('permissions');
            },
            'permissions' => function ($query) {
                $query->where('type', 'ems');
            }
        ])->find($id);

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        $userPermissions = $employee->permissions->pluck('id')->toArray();

        $dashboardRoleIds = $employee->dashboardRoles->pluck('id')->toArray();
        $rolePermissions = collect();
        foreach ($employee->dashboardRoles as $role) {
            $rolePermissions = $rolePermissions->merge($role->permissions->where('type', 'ems')->pluck('id'));
        }
        $combinedPermissions = array_unique(array_merge($userPermissions, $rolePermissions->toArray()));

        return response()->json([
            'success' => true,
            'data' => [
                'userPermissions' => $combinedPermissions,
                'dashboard_role_ids' => $dashboardRoleIds,
            ],
        ]);
    }
}
