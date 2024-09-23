<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
// use Modules\Employee\Database\Factories\AdministrativeUserFactory;

class AdministrativeUser extends Pivot
{
    use HasFactory;
    
    protected $table = "employee_administrative_users";
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): AdministrativeUserFactory
    // {
    //     // return AdministrativeUserFactory::new();
    // }
}
