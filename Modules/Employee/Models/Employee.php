<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Employee\database\factories\EmployeeFactory;
use Modules\Establishment\Models\Establishment;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;


class Employee extends BaseEmployeeModel
{
    use HasFactory, HasRoles, SoftDeletes, HasPermissions;

    protected $guard_name = "web";

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pos_is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }


    public function wageEstablishments()
    {
        return $this->belongsToMany(Establishment::class, 'emp_wages')->withTimestamps();
    }

    public function establishments()
    {
        return $this->belongsToMany(Establishment::class, 'emp_employee_establishments_roles')->withTimestamps()->withPivot('role_id');
    }

    public function posRoles()
    {
        return $this->belongsToMany(Role::class, 'emp_employee_establishments_roles')->withTimestamps()->withPivot('establishment_id')->where('type', 'pos');
    }

    public function dashboardRoles()
    {
        return $this->belongsToMany(Role::class, 'emp_employee_establishments_roles')->withPivot('establishment_id')->where('type', 'ems');
    }

    public function allRoles()
    {
        return $this->belongsToMany(Role::class, 'emp_employee_establishments_roles')->withPivot('establishment_id');
    }

    public function wages()
    {
        return $this->hasMany(Wage::class);
    }

    public function timecards()
    {
        return $this->hasMany(TimeCard::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function allowances()
    {
        return $this->hasMany(PayrollAdjustment::class)->where('type', 'allowance');
    }

    public function deductions()
    {
        return $this->hasMany(PayrollAdjustment::class)->where('type', 'deduction');
    }

    public function getEmployeeEstablishmentsWithAllOption()
    {
        $hasAllEstablishmentsRole = $this->posRoles()->whereNull('establishment_id')->exists();

        $specificEstablishments = $this->establishments()->whereHas('posRoles')->get();

        $establishments = $specificEstablishments->pluck('name')->toArray();
        if ($hasAllEstablishmentsRole) {
            array_unshift($establishments, __('employee::general.all_establishments'));
        }
        return $establishments;
    }

    public function getTranslatedNameAttribute()
    {
        $name = session()->get('locale') === 'ar' ? 'name' : 'name_en';
        return $this->$name;
    }

    public function ScopeActive(Builder $query)
    {
        $query->where('pos_is_active', true);
    }
}
