<?php

namespace Modules\Employee\Services;

use Modules\Employee\Models\DashboardRole;

class DashboardRoleActions
{
    public function __construct(protected $request) {}

    public function storeUpdateRolePermissions($dashboardRole, $role = true)
    {
        if ($this->request->has('dashboard_permissions')) {
            $filteredPermissions = collect($this->request['dashboard_permissions'])
                ->map(function ($item) {
                    return (int) $item;
                });
        } else {
            $filteredPermissions = collect([]);
        }

        if (! $role) {
            $filteredPermissions = collect(array_merge(
                $dashboardRole->getDirectPermissions()->where('type', 'pos')->pluck('id')->toArray(),
                $filteredPermissions->toArray()
            ));
        }

        $role ? $dashboardRole->permissions()->sync($filteredPermissions->toArray()) : $dashboardRole->syncPermissions($filteredPermissions->toArray());
    }

    public function store()
    {
        $dashboardRole = DashboardRole::create($this->request->merge(['type' => 'ems'])->all());
        $this->storeUpdateRolePermissions($dashboardRole);
    }

    public function update($dashboardRole)
    {
        $dashboardRole->update($this->request->all());
        $this->storeUpdateRolePermissions($dashboardRole);
    }
}
