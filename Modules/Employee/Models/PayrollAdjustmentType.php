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

    public function adjustment()
    {
        return $this->hasMany(PayrollAdjustment::class)->withTrashed();
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
