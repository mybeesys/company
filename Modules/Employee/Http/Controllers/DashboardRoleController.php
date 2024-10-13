<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Modules\Employee\Classes\Tables;
use Modules\Employee\Http\Requests\StoreDashboardRoleRequest;
use Modules\Employee\Http\Requests\UpdateDashboardRoleRequest;
use Modules\Employee\Models\PermissionSet;

class DashboardRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $dashboardRoles = PermissionSet::
                select('id', 'permissionSetName', 'isActive', 'rank');
            return Tables::getDashboardRoleTable($dashboardRoles);
        }
        $columns = Tables::getDashboardRoleColumns();
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
        return view('employee::dashboard-roles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDashboardRoleRequest $request)
    {
        DB::beginTransaction();
        $dashboardRole = PermissionSet::create($request->safe()->all());
        DB::commit();
        if ($dashboardRole) {
            return redirect()->route('dashboard-roles.index')->with('message', __('employee::responses.role_created_successfully'));
        } else {
            return redirect()->route('dashboard-roles.index')->with('error', __('employee::responses.something_wrong_happened'));
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
    public function edit(PermissionSet $dashboardRole)
    {
        return view('employee::dashboard-roles.edit', compact('dashboardRole'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDashboardRoleRequest $request, PermissionSet $dashboardRole)
    {
        DB::beginTransaction();
        $updated = $dashboardRole->update($request->safe()->all());
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
    public function destroy(PermissionSet $dashboardRole)
    {
        $delete = $dashboardRole->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.role_deleted_successfully')]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
