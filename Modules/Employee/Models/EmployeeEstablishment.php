<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Establishment\Models\Establishment;

class EmployeeEstablishment extends Pivot
{
    use HasFactory;

    public $incrementing = true;
    
    protected $table = 'employee_employee_establishments';
    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    public function permissionSet()
    {
        return $this->hasMany(PermissionSet::class, 'permissionSet_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function wage()
    {
        return $this->belongsTo(Wage::class);
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
