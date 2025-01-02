<?php

namespace Modules\Employee\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollAdjustment extends BaseEmployeeModel
{
    use HasFactory, SoftDeletes;

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

    public function adjustmentType()
    {
        return $this->belongsTo(PayrollAdjustmentType::class);
    }

    public function scopeAllowance(Builder $query)
    {
        $query->where('type', 'allowance');
    }

    public function scopeDeduction(Builder $query)
    {
        $query->where('type', 'deduction');
    }

    public function scopeOnce(Builder $query)
    {
        $query->where('apply_once', true);
    }

    public function scopeAlways(Builder $query)
    {
        $query->where('apply_once', false);
    }
}
