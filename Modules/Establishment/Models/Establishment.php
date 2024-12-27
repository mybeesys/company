<?php

namespace Modules\Establishment\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Payroll;
use Modules\Employee\Models\PayrollGroup;
use Modules\Employee\Models\Role;
use Modules\Employee\Models\Shift;
use Modules\Employee\Models\TimeCard;

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
        return $this->belongsToMany(Role::class, 'emp_employee_establishments_roles')->withTimestamps()->withPivot('establishment_id')->where('type', 'pos');
    }

    public function main()
    {
        return $this->belongsTo(Establishment::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Establishment::class, 'parent_id');
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
        return $this->hasMany(Employee::class);
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
}
