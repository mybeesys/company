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
        $permissions = $this->request->has('pos_permissions') ? collect($this->request['pos_permissions'])->map(fn($value) => (int) $value) : null;
        $permissions ? ($permissions->contains($selectAllPermission->id) ? $role->syncPermissions([$selectAllPermission->id]) : $role->syncPermissions($permissions)) : $role->syncPermissions([]);
    }
    public function store()
    {
        $role = Role::create(array_merge($this->request->except('pos_permissions'), ['type' => 'pos']));
        $this->storeUpdateRolePermissions($role);
    }

    public function update($role)
    {
        $role->update($this->request->all());
        $this->storeUpdateRolePermissions($role);
    }
}