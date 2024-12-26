<?php
namespace Modules\Employee\Services;
use Modules\Employee\Models\Role;

class DashboardRoleActions
{
    public function __construct(protected $request)
    {
    }

    public function storeUpdateRolePermissions($dashboardRole, $role = true)
    {
        if ($this->request->has('dashboard_permissions')) {
            $permissions = collect($this->request['dashboard_permissions']);
            $allPermissions = [];
            $permissions->each(function ($item, $key) use (&$allPermissions) {
                [$module, $type, $action] = explode('.', $key);
                if ($type === 'all') {
                    $allPermissions["$module.$action"] = true;
                }
            });
            $filteredPermissions = $permissions->filter(function ($item, $key) use ($allPermissions) {
                [$module, $type, $action] = explode('.', $key);
                // If "all" permission exists for the same module and action, skip this item
                return !isset($allPermissions["$module.$action"]) || $type === 'all';
            })->map(function ($item) {
                return (int) $item;
            });
        } else {
            $filteredPermissions = null;
        }
        //Check if the permissions is for individual employee or role
        $role ? $dashboardRole->permissions()->sync($filteredPermissions) : $dashboardRole->syncPermissions($filteredPermissions);
    }


    public function store()
    {
        $dashboardRole = Role::create($this->request->merge(['type' => 'ems'])->all());
        $this->storeUpdateRolePermissions($dashboardRole);
    }

    public function update($dashboardRole)
    {
        $dashboardRole->update($this->request->all());
        $this->storeUpdateRolePermissions($dashboardRole);
    }
}