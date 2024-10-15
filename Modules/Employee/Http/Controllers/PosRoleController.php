<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Modules\Employee\Classes\PosRoleTable;
use Modules\Employee\Http\Requests\StoreRoleRequest;
use Modules\Employee\Http\Requests\UpdateRoleRequest;
use Modules\Employee\Models\Permission;
use Modules\Employee\Models\Role;

class PosRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $roles = Role::
                select('id', 'name', 'guard_name', 'department', 'rank');
            return PosRoleTable::getRoleTable($roles);
        }
        $columns = PosRoleTable::getRoleColumns();
        return view('employee::pos-roles.index', compact('columns'));
    }

    public function createLiveValidation(StoreRoleRequest $request)
    {
    }

    public function updateLiveValidation(UpdateRoleRequest $request)
    {
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::orderByRaw('FIELD(name, "select_all_permissions") DESC')->get(['id', 'name', 'name_ar', 'description', 'description_ar']);
        $departments = Role::departments();
        return view('employee::pos-roles.create', compact('departments', 'permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        DB::beginTransaction();
        $request = $request->safe();
        $role = Role::create($request->except('permissions'));

        if ($request->has('permissions')) {
            $selectAllPermission = Permission::firstWhere('name', 'select_all_permissions');
            $permissions = collect($request->permissions)->map(function ($value) {
                return (int) $value;
            });
            $permissions->contains($selectAllPermission->id) ? $role->givePermissionTo($selectAllPermission->id) : $role->syncPermissions($permissions);
        }

        DB::commit();
        if ($role) {
            return redirect()->route('roles.index')->with('message', __('employee::responses.role_created_successfully'));
        } else {
            return redirect()->route('roles.index')->with('error', __('employee::responses.something_wrong_happened'));
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
    public function edit(int $id)
    {
        $role = Role::where('id', $id)->with('permissions:id,name')->first();
        $departments = Role::departments();
        $permissions = Permission::orderByRaw('FIELD(name, "select_all_permissions") DESC')->get(['id', 'name', 'name_ar', 'description', 'description_ar']);
        return view('employee::pos-roles.edit', compact('role', 'departments', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        DB::beginTransaction();
        $request = $request->safe();
        $updated = $role->update($request->all());

        if ($request->has('permissions')) {
            $selectAllPermission = Permission::firstWhere('name', 'select_all_permissions');
            $permissions = collect($request->permissions)->map(function ($value) {
                return (int) $value;
            });
            $permissions->contains($selectAllPermission->id) ? $role->syncPermissions([$selectAllPermission->id]) : $role->syncPermissions($permissions);
        }

        DB::commit();
        if ($updated) {
            return redirect()->route('roles.index')->with('message', __('employee::responses.role_updated_successfully'));
        } else {
            return redirect()->route('roles.index')->with('error', __('employee::responses.something_wrong_happened'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $delete = $role->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.role_deleted_successfully')]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
