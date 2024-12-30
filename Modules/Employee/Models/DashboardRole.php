<?php

namespace Modules\Employee\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Modules\Establishment\Models\Establishment;
use Spatie\Permission\Models\Role as SpatieRole;


class DashboardRole extends SpatieRole
{

    protected static function booted()
    {
        static::addGlobalScope('dashboardRole', function (Builder $query) {
            $query->where('type', 'ems');
        });
    }

    public function scopeDepartments(Builder $query)
    {
        return $query->whereNotNull('department')->pluck('department');
    }

    public function scopeDashboardRole(Builder $query)
    {
        return $query->where('type', 'ems');
    }
}
