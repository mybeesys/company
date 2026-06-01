<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Employee\database\factories\EmployeeFactory;
use Modules\Establishment\Models\Establishment;
use Modules\Franchise\Models\FranchiseCompanies;
use Modules\Franchisee\Models\Agreement;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, HasPermissions, HasRoles, Notifiable, SoftDeletes;

    /**
     * Per-request cache for {@see hasDashboardPermission()} (direct + role-granted names).
     *
     * @var array{direct: array<string, true>, via_roles: array<string, true>, via_roles_ems: array<string, true>}|null
     */
    protected ?array $dashboardPermissionCache = null;

    protected $table = 'emp_employees';

    protected $guard_name = 'web';

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

    public function createdBy()
    {
        return $this->belongsTo(Employee::class, 'created_by');
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

    // User.php
    public function franchise()
    {
        return $this->belongsTo(FranchiseCompanies::class, 'franchise_id');
    }

    public function hasDashboardPermission($permission)
    {
        // If permission is empty/null in config('menu'), treat it as public item.
        if (empty($permission)) {
            return true;
        }

        if (! $permission) {
            return false;
        }

        $permission_sections = explode('.', $permission);
        if (count($permission_sections) < 3) {
            return false;
        }

        $module = $permission_sections[0];
        $permission_action = $permission_sections[2];
        $wildcard = "{$module}.all.{$permission_action}";

        $cache = $this->dashboardPermissionLookup();
        $direct = $cache['direct'];
        $viaRoles = $cache['via_roles'];
        $viaRolesEms = $cache['via_roles_ems'];

        return isset($direct[$permission])
            || isset($direct[$wildcard])
            || isset($viaRoles[$permission])
            || isset($viaRolesEms[$wildcard]);
    }

    /**
     * @return array{direct: array<string, true>, via_roles: array<string, true>, via_roles_ems: array<string, true>}
     */
    protected function dashboardPermissionLookup(): array
    {
        if ($this->dashboardPermissionCache !== null) {
            return $this->dashboardPermissionCache;
        }

        $direct = [];
        foreach ($this->getDirectPermissions()->pluck('name') as $name) {
            $direct[$name] = true;
        }

        $viaRoles = [];
        $viaRolesEms = [];
        $roleIds = $this->dashboardRoles()->pluck('roles.id');
        if ($roleIds->isNotEmpty()) {
            $permissions = Permission::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('roles.id', $roleIds))
                ->get(['name', 'type']);

            foreach ($permissions as $perm) {
                $viaRoles[$perm->name] = true;
                if ($perm->type === 'ems') {
                    $viaRolesEms[$perm->name] = true;
                }
            }
        }

        return $this->dashboardPermissionCache = [
            'direct' => $direct,
            'via_roles' => $viaRoles,
            'via_roles_ems' => $viaRolesEms,
        ];
    }

    public function hasDashboardPermissionViaRoles($permission, $module, $permission_action)
    {
        $wildcard = "{$module}.all.{$permission_action}";
        $cache = $this->dashboardPermissionLookup();
        $viaRoles = $cache['via_roles'];
        $viaRolesEms = $cache['via_roles_ems'];

        return isset($viaRoles[$permission]) || isset($viaRolesEms[$wildcard]);
    }

    public function parent()
    {
        return $this->belongsTo(Employee::class, 'parent_id');
    }

    public function agreement()
    {
        return $this->belongsTo(Agreement::class, 'agreement_id');
    }
}
