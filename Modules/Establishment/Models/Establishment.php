<?php

namespace Modules\Establishment\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Payroll;
use Modules\Employee\Models\PosRole;
use Modules\Employee\Models\Shift;
use Modules\Employee\Models\TimeCard;
use Modules\Product\Models\CustomMenu;
use Modules\Sales\Models\Coupon;

class Establishment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'est_establishments';
    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function posRoles()
    {
        return $this->belongsToMany(PosRole::class, 'emp_employee_establishments_roles', 'establishment_id', 'role_id')->withTimestamps()->withPivot('employee_id');
    }

    public function main()
    {
        return $this->belongsTo(Establishment::class, 'parent_id')->withTrashed();
    }

    public function children()
    {
        return $this->hasMany(Establishment::class, 'parent_id')->withTrashed();
    }

    public function getAllDescendants()
    {
        return $this->children->flatMap(function ($child) {
            return [$child, ...$child->getAllDescendants()];
        });
    }

    public function scopeActive(Builder $query)
    {
        $query->where('is_active', true);
    }

    public function scopeNotMain(Builder $query)
    {
        $query->where('is_main', false);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class)->withTrashed();
    }

    public function timecards()
    {
        return $this->hasMany(TimeCard::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function hasAnyRelation()
    {
        $relationships = [
            'posRoles',
            'employees',
            'timecards',
            'shifts',
            'payrolls',
            'children'
        ];

        foreach ($relationships as $relation) {
            if ($this->$relation()->exists()) {
                return true;
            }
        }

        return false;
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'sales_coupons_establishments');
    }
    public function customMenus()
    {
        return $this->hasMany(CustomMenu::class, 'station_id', 'id');
    }
}
