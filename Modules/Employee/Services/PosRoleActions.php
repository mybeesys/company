<?php
namespace Modules\Employee\Services;
use Modules\Employee\Models\Permission;
use Modules\Employee\Models\Role;

class PosRoleActions
{
    public function __construct(protected $request)
    {
    }

    public function storeUpdateRolePermissions($role)
    {
        $selectAllPermission = Permission::firstWhere('name', 'select_all_permissions');
        $permissions = collect($this->request->permissions)->map(function ($value) {
            return (int) $value;
        });
        $permissions->contains($selectAllPermission->id) ? $role->syncPermissions([$selectAllPermission->id]) : $role->syncPermissions($permissions);

    }
    public function store()
    {
        $role = Role::create($this->request->except('permissions'));
        $this->request->has('permissions') ? $this->storeUpdateRolePermissions($role) : null;
    }

    public function update($role)
    {
        $role->update($this->request->all());
        $this->request->has('permissions') ? $this->storeUpdateRolePermissions($role) : null;
    }
}