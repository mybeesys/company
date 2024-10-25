<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Permission;

class PermissionController extends Controller
{

    public function aasignPermissionsToEmployee(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'permissions' => ['array', 'nullable'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('type', 'pos')]
        ]);
        $selectAllPermission = Permission::firstWhere('name', 'select_all_permissions');
        $permissions = collect($validated['permissions'])->map(function ($value) {
            return (int) $value;
        });
        $permissions->contains($selectAllPermission->id) ? $employee->syncPermissions([$selectAllPermission->id]) : $employee->syncPermissions($permissions);
        return response()->json(['message' => __('employee::responses.opreation_success')]);
    }

    public function getEmployeePosPermissions($id)
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
}
