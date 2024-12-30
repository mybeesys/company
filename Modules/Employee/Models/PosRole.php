<?php

namespace Modules\Employee\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Modules\Establishment\Models\Establishment;
use Spatie\Permission\Models\Role as SpatieRole;


class PosRole extends SpatieRole
{
    protected static function booted()
    {
        static::addGlobalScope('posRole', function (Builder $query) {
            $query->where('type', 'pos');
        });
    }

    public function scopeDepartments(Builder $query)
    {
        return $query->whereNotNull('department')->pluck('department');
    }

    public function scopePosRole(Builder $query)
    {
        return $query->where('type', 'pos');
    }

    public function establishments()
    {
        return $this->belongsToMany(Establishment::class, 'emp_employee_establishments_roles', 'role_id', 'establishment_id')->withTimestamps()->withPivot('employee_id');
    }
}
