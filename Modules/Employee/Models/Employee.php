<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Employee\Database\Factories\EmployeeFactory;
use Spatie\Permission\Traits\HasRoles;


class Employee extends BaseModel
{
    use HasFactory, HasRoles;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at','updated_at'];


    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }
}
