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
            'pos_is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }

    public function establishments()
    {
        return $this->belongsToMany(Establishment::class, 'emp_employee_est_roles_wages')->using(EmployeeRoles::class)->withTimestamps()->withPivot('role_id', 'wage_type', 'rate');
    }

    public function posRoles()
    {
        return $this->belongsToMany(Role::class, 'emp_employee_est_roles_wages')->using(EmployeeRoles::class)->withTimestamps()->withPivot('establishment_id', 'wage_type', 'rate')->where('type', 'pos');
    }

    public function dashboardRoles()
    {
        return $this->belongsToMany(Role::class, 'emp_employee_est_roles_wages')->withPivot('establishment_id', 'wage_type', 'rate')->where('type', 'ems');
    }
    
    public function allRoles()
    {
        return $this->belongsToMany(Role::class, 'emp_employee_est_roles_wages')->withPivot('establishment_id', 'wage_type', 'rate');
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
