<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollAdjustment extends BaseEmployeeModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function payrolls()
    {
        return $this->belongsToMany(Payroll::class, 'sch_adjustments_payrolls', 'adjustment_id', 'payroll_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
