<?php

namespace Modules\Employee\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollAdjustmentType extends BaseEmployeeModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function getTranslatedNameAttribute()
    {
        $name = session('locale') === 'ar' ? 'name' : 'name_en';
        return ($this->$name ?? $this->name_en) ?? $this->name;
    }

    public function adjustment()
    {
        return $this->hasMany(PayrollAdjustment::class);
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
