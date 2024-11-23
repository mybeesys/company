<?php

namespace Modules\Employee\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Modules\Establishment\Models\Establishment;
use Spatie\Permission\Models\Role as SpatieRole;


class Role extends SpatieRole
{
    public function scopeDepartments(Builder $query)
    {
        return $query->whereNotNull('department')->pluck('department');
    }

    public function establishments()
    {
        return $this->belongsToMany(Establishment::class, 'emp_employee_est_roles_wages')->using(EmployeeRoles::class)->withTimestamps()->withPivot('employee_id', 'wage_type', 'rate');
    }
}
