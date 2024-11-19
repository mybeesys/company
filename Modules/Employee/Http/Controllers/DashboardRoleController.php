<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Modules\Employee\Classes\DashboardRoleTable;
use Modules\Employee\Http\Requests\StoreDashboardRoleRequest;
use Modules\Employee\Http\Requests\UpdateDashboardRoleRequest;
use Modules\Employee\Models\Permission;
use Modules\Employee\Models\Role;
use Modules\Employee\Services\dashboardRoleActions;

class DashboardRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $dashboardRoles = Role::where('type', 'ems')->get(['id', 'name', 'is_active', 'rank']);
            return DashboardRoleTable::getDashboardRoleTable($dashboardRoles);
        }
        $columns = DashboardRoleTable::getDashboardRoleColumns();
        return view('employee::dashboard-roles.index', compact('columns'));
    }

    public function createLiveValidation(StoreDashboardRoleRequest $request)
    {
    }

    public function updateLiveValidation(UpdateDashboardRoleRequest $request)
    {
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $modules = Permission::where('type', 'ems')
            ->get(['id', 'name', 'name_ar', 'description', 'description_ar'])
            ->groupBy(function ($item) {
                return explode(".", $item->name)[0];
            })
            ->map(function ($permissions) {
                return $permissions->map(function ($item) {
                    $nameParts = explode(".", $item->name);
                    return [
                        'entity' => $item->name_ar ? "$nameParts[1].$item->name_ar" : "$nameParts[1]",
                        'action' => $nameParts[2],
                        'id' => $item->id,
                    ];
                })->groupBy('entity')->map(function ($groupedPermissions) {
                    return $groupedPermissions->mapWithKeys(function ($item) {
                        return [
                            $item['action'] => $item['id'],
                        ];
                    });
                });
            });
        return view('employee::dashboard-roles.create', ['modules' => $modules]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDashboardRoleRequest $request)
    {
        DB::transaction(function () use ($request) {
            $filteredRequest = $request->safe();
            $storeRole = new dashboardRoleActions($filteredRequest);
            $storeRole->store();
        });
        return to_route('dashboard-roles.index')->with('success', __('employee::responses.created_successfully', ['name' => __('employee::fields.role')]));
    }

    /**
     * Show the specified resource.
     */
    public function show(Role $dashboardRole)
    {
        $modules = Permission::where('type', 'ems')
            ->get(['id', 'name', 'name_ar', 'description', 'description_ar'])
            ->groupBy(function ($item) {
                return explode(".", $item->name)[0];
            })
            ->map(function ($permissions) {
                return $permissions->map(function ($item) {
                    $nameParts = explode(".", $item->name);
                    return [
                        'entity' => $item->name_ar ? "$nameParts[1].$item->name_ar" : "$nameParts[1]",
                        'action' => $nameParts[2],
                        'id' => $item->id,
                    ];
                })->groupBy('entity')->map(function ($groupedPermissions) {
                    return $groupedPermissions->mapWithKeys(function ($item) {
                        return [
                            $item['action'] => $item['id'],
                        ];
                    });
                });
            });
        $rolePermissions = $dashboardRole->permissions()->get()->pluck('id');
        return view('employee::dashboard-roles.show', compact('dashboardRole', 'modules', 'rolePermissions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $dashboardRole)
    {
        $modules = Permission::where('type', 'ems')
            ->get(['id', 'name', 'name_ar', 'description', 'description_ar'])
            ->groupBy(function ($item) {
                return explode(".", $item->name)[0];
            })
            ->map(function ($permissions) {
                return $permissions->map(function ($item) {
                    $nameParts = explode(".", $item->name);
                    return [
                        'entity' => $item->name_ar ? "$nameParts[1].$item->name_ar" : "$nameParts[1]",
                        'action' => $nameParts[2],
                        'id' => $item->id,
                    ];
                })->groupBy('entity')->map(function ($groupedPermissions) {
                    return $groupedPermissions->mapWithKeys(function ($item) {
                        return [
                            $item['action'] => $item['id'],
                        ];
                    });
                });
            });
        $rolePermissions = $dashboardRole->permissions()->get()->pluck('id');
        return view('employee::dashboard-roles.edit', compact('dashboardRole', 'modules', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDashboardRoleRequest $request, Role $dashboardRole)
    {
        DB::transaction(function () use ($request, $dashboardRole) {
            $filteredRequest = $request->safe();
            $storeRole = new dashboardRoleActions($filteredRequest);
            $storeRole->update($dashboardRole);
        });
        return to_route('dashboard-roles.index')->with('success', __('employee::responses.updated_successfully', ['name' => __('employee::fields.role')]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $dashboardRole)
    {
        $delete = $dashboardRole->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('employee::fields.role')])]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
