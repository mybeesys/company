<?php

namespace Modules\Employee\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollAdjustmentType extends BaseEmployeeModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function getTranslatedNameAttribute()
    {
        return ($this->{get_name_by_lang()} ?? $this->name_en) ?? $this->name;
    }

    public function adjustments()
    {
        return $this->hasMany(PayrollAdjustment::class, 'adjustment_type_id')->withTrashed();
    }

    public function allowances()
    {
        return $this->hasMany(PayrollAdjustment::class, 'adjustment_type_id')->where('type', 'allowance')->withTrashed();
    }
    
    public function deductions()
    {
        return $this->hasMany(PayrollAdjustment::class, 'adjustment_type_id')->where('type', 'deduction')->withTrashed();
    }

    public function scopeAllowance(Builder $query)
    {
        $query->where('type', 'allowance');
    }

    public function scopeDeduction(Builder $query)
    {
        $query->where('type', 'deduction');
    }
}
