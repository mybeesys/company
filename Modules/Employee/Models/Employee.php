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
use Spatie\Permission\Models\Role;

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
        return $this->belongsTo(Establishment::class, 'establishment_id')->withTrashed();
    }

    public function establishments()
    {
        return $this->belongsToMany(Establishment::class, 'emp_employee_establishments_roles', 'employee_id', 'establishment_id')->withTimestamps()->withPivot('role_id')->withTrashed();
    }

    public function posRoles()
    {
        return $this->belongsToMany(PosRole::class, 'emp_employee_establishments_roles', 'employee_id', 'role_id')->withTimestamps()->withPivot('establishment_id');
    }

    public function dashboardRoles()
    {
        return $this->belongsToMany(DashboardRole::class, 'emp_employee_establishments_roles', 'employee_id', 'role_id')->withPivot('establishment_id');
    }

    public function allRoles()
    {
        return $this->belongsToMany(Role::class, 'emp_employee_establishments_roles', 'employee_id', 'role_id')->withPivot('establishment_id');
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
        return $this->{get_name_by_lang()};
    }

    public function ScopeActive(Builder $query)
    {
        $query->where('pos_is_active', true);
    }

    public function hasDashboardPermission($permission)
    {
        if ($permission) {
            $permission_sections = explode('.', $permission);
            $module = $permission_sections[0];
            $permission_action = $permission_sections[2];

            $directPermission = $this->hasDirectPermission($permission) || $this->getDirectPermissions()->where('name', "$module.all.$permission_action")->isNotEmpty();

            return $directPermission || $this->hasDashboardPermissionViaRoles($permission, $module, $permission_action);
        }
        return false;
    }

    public function hasDashboardPermissionViaRoles($permission, $module, $permission_action)
    {
        $permissionRoles = Permission::firstWhere('name', $permission)?->roles->pluck('name');
        $allPermissionRoles = Permission::where('name', "$module.all.$permission_action")->where('type', 'ems')->first()?->roles->pluck('name');

        return ($permissionRoles && $this->dashboardRoles->pluck('name')->intersect($permissionRoles)->isNotEmpty()) ||
            $allPermissionRoles && $this->dashboardRoles->pluck('name')->intersect($allPermissionRoles)->isNotEmpty();
    }
}
