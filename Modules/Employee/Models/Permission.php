<?php

namespace Modules\Employee\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    public function permissionSet()
    {
        return $this->belongsToMany(PermissionSet::class, 'employee_permission_set_permissions')->withPivot('accessLevel');
    }
}
