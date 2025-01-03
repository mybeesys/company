<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Establishment\Models\Establishment;

class Payroll extends BaseScheduleModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];


    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
    
    public function adjustments()
    {
        return $this->belongsToMany(PayrollAdjustment::class, 'sch_adjustments_payrolls', 'payroll_id', 'adjustment_id')->withTrashed();
    }

    public function payrollGroup()
    {
        return $this->belongsTo(PayrollGroup::class);
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id')->withTrashed();
    }
}
