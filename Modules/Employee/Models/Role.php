<?php

namespace Modules\Employee\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Modules\Establishment\Models\Establishment;
use Spatie\Permission\Models\Role as SpatieRole;


class Role extends SpatieRole
{
    public function scopeDepartments(Builder $query)
    {
        return $query->whereNotNull('department')->pluck('department');
    }

    public function wages()
    {
        return $this->hasMany(Wage::class);
    }

    public function establishments()
    {
        return $this->belongsToMany(Establishment::class, 'employee_employee_establishments')->using(EmployeeEstablishment::class)->withPivot('wage_id', 'employee_id');
    }
}
