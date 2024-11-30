<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Establishment\Models\Establishment;

class PayrollGroup extends BaseScheduleModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function establishments()
    {
        return $this->belongsToMany(Establishment::class, 'sch_establishment_payroll_groups',  'payroll_group_id', 'establishment_id');
    }
}
