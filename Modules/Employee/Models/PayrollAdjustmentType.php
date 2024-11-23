<?php

namespace Modules\Employee\Models;

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
        $name = session()->get('locale') === 'ar' ? 'name' : 'name_en';
        return $this->$name ?? $this->name_en ?? $this->name;
    }
}
