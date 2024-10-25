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
use Modules\Employee\Services\PosRoleActions;

class PosRoleController extends Controller
{


    protected $permissions;
    protected $departments;

    public function __construct()
    {
        $this->permissions = Permission::where('type', 'pos')->orderByRaw('FIELD(name, "select_all_permissions") DESC')->get(['id', 'name', 'name_ar', 'description', 'description_ar']);
        $this->departments = Role::departments();
    }
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
        return view('employee::pos-roles.create', ['departments' => $this->departments, 'permissions' => $this->permissions]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        DB::transaction(function () use ($request) {
            $filteredRequest = $request->safe();
            $storeRole = new PosRoleActions($filteredRequest);
            $storeRole->store();
        });
        return to_route('roles.index')->with('success', __('employee::responses.created_successfully', ['name' => __('employee::fields.role')]));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $role = Role::where('id', $id)->with('permissions:id,name')->first();
        return view('employee::pos-roles.show', ['role' => $role, 'departments' => $this->departments, 'permissions' => $this->permissions]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        $role = Role::where('id', $id)->with('permissions:id,name')->first();
        return view('employee::pos-roles.edit', ['role' => $role, 'departments' => $this->departments, 'permissions' => $this->permissions]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        DB::transaction(function () use ($request, $role) {
            $filteredRequest = $request->safe();
            $storeRole = new PosRoleActions($filteredRequest);
            $storeRole->update($role);
        });

        return to_route('roles.index')->with('success', __('employee::responses.updated_successfully', ['name' => __('employee::fields.role')]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $delete = $role->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('employee::fields.role')])]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
