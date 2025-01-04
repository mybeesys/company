<?php

namespace Modules\Employee\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function getModifiedNameAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->name));
    }
}
