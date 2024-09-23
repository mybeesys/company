<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
// use Modules\Employee\Database\Factories\EmployeeEstablishmentFactory;

class EmployeeEstablishment extends Pivot
{
    use HasFactory;

    protected $table = 'employee_employee_establishments';
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): EmployeeEstablishmentFactory
    // {
    //     // return EmployeeEstablishmentFactory::new();
    // }
}
