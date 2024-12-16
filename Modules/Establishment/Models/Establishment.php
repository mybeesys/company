<?php

namespace Modules\Establishment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Employee\Models\PayrollGroup;
use Modules\Employee\Models\Role;

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

    public function payrollGroups()
    {
        $this->belongsToMany(PayrollGroup::class, 'sch_establishment_payroll_groups');
    }
}
