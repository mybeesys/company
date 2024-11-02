<?php

namespace Modules\Employee\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Establishment\Models\Establishment;
// use Modules\Employee\Database\Factories\WageFactory;

class Wage extends BaseEmployeeModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class);
    }
}
