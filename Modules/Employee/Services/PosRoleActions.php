<?php
namespace Modules\Employee\Services;
use Modules\Employee\Models\Permission;
use Modules\Employee\Models\PosRole;

class PosRoleActions
{
    public function __construct(protected $request)
    {
    }

    public function storeUpdateRolePermissions($role, $is_role = true)
    {
        $selectAllPermission = Permission::firstWhere('name', 'select_all_permissions');
        $permissions = $this->request->has('pos_permissions') ? collect($this->request['pos_permissions'])->map(fn($value) => (int) $value) : null;

        $permissions = $permissions ? ($permissions->contains($selectAllPermission->id) ? [$selectAllPermission->id] : $permissions->toArray()) : [];
        if (!$is_role) {
            $permissions = array_merge($role->getDirectPermissions()->where('type', 'ems')->pluck('id')->toArray(), $permissions);
        }
        $role->syncPermissions($permissions);
    }
    public function store()
    {
        $role = PosRole::create(array_merge($this->request->except('pos_permissions'), ['type' => 'pos']));
        $this->storeUpdateRolePermissions($role);
    }

    public function update($role)
    {
        $role->update($this->request->all());
        $this->storeUpdateRolePermissions($role);
    }
}