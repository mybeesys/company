<?php

namespace Modules\Employee\Models;

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
            'isActive' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }

    public function establishments()
    {
        return $this->belongsToMany(Establishment::class, 'employee_employee_establishments')->using(EmployeeEstablishment::class)->withTimestamps()->withPivot('role_id', 'wage_id');
    }

    public function establishmentRoles()
    {
        return $this->belongsToMany(Role::class, 'employee_employee_establishments')->using(EmployeeEstablishment::class)->withTimestamps()->withPivot('establishment_id', 'wage_id');
    }

    public function wages()
    {
        return $this->hasMany(Wage::class);
    }

    public function administrativeUser()
    {
        return $this->hasOne(AdministrativeUser::class);
    }

    public function establishmentsPivot()
    {
        return $this->hasMany(EmployeeEstablishment::class);
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
        return $this->hasMany(AllowanceDeduction::class)->where('type', 'allowance');
    }

    public function deductions()
    {
        return $this->hasMany(AllowanceDeduction::class)->where('type', 'deduction');
    }
}
