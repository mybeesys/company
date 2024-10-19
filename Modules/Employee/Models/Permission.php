<?php

namespace Modules\Employee\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Permission extends SpatiePermission
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function permissionSet()
    {
        return $this->belongsToMany(PermissionSet::class, 'employee_permission_set_permissions')->withPivot('accessLevel');
    }

    public function getModifiedNameAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->name));
    }
}
