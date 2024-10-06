<?php

namespace Modules\Employee\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;


class Role extends SpatieRole
{
    public function scopeDepartments(Builder $query)
    {
        return $query->whereNotNull('department')->pluck('department');
    }
}
