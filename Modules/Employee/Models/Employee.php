<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Employee\database\factories\EmployeeFactory;
use Modules\Establishment\Models\Establishment;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable
{
    use HasFactory, HasRoles, SoftDeletes, HasPermissions, HasApiTokens, Notifiable;

    protected $table = 'emp_employees';

    protected $guard_name = "web";

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }

    public function defaultEstablishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
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

    public function wage()
    {
        return $this->hasOne(Wage::class);
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
