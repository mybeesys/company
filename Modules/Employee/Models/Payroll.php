<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payroll extends BaseScheduleModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];


    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    
    public function adjustments()
    {
        return $this->belongsToMany(PayrollAdjustment::class, 'sch_adjustments_payrolls', 'payroll_id', 'adjustment_id');
    }

    public function payrollGroup()
    {
        return $this->belongsTo(PayrollGroup::class);
    }
}
