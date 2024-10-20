<?php
namespace Modules\Employee\Services;
use Modules\Employee\Models\PermissionSet;

class DashboardRoleActions
{
    public function __construct(protected $request)
    {
    }

    public function storeUpdateRolePermissions($dashboardRole)
    {
        $permissions = collect($this->request->permissions);
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
        });
        $dashboardRole->permissions()->sync($filteredPermissions);
    }


    public function store()
    {
        $dashboardRole = PermissionSet::create($this->request->all());
        $this->request->has('permissions') ? $this->storeUpdateRolePermissions($dashboardRole) : null;
    }

    public function update($dashboardRole)
    {
        $dashboardRole->update($this->request->all());
        $this->request->has('permissions') ? $this->storeUpdateRolePermissions($dashboardRole) : null;
    }
}