<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Establishment\Models\Establishment;

class EmployeeRoles extends Pivot
{
    use HasFactory;

    public $incrementing = true;
    
    protected $table = 'emp_employee_est_roles_wages';
    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
