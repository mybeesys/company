<?php

namespace Modules\Establishment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Employee\Models\Role;

class Establishment extends Model
{
    use HasFactory;

    protected $table = 'establishment_establishments';
    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    public function posRoles()
    {
        return $this->belongsToMany(Role::class, 'emp_employee_establishments_roles')->withTimestamps()->withPivot('establishment_id')->where('type', 'pos');
    }
}
