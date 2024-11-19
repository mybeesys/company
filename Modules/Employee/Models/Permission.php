<?php

namespace Modules\Employee\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Permission extends SpatiePermission
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function emsRoles()
    {
        return $this->belongsToMany(Role::class, 'em_employee_establishments')->where('type', 'ems');
    }

    public function posRoles()
    {
        return $this->belongsToMany(Role::class, 'em_employee_establishments')->where('type', 'pos');
    }

    public function getModifiedNameAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->name));
    }
}
