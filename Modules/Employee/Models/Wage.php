<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Employee\Database\Factories\WageFactory;

class Wage extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): WageFactory
    // {
    //     // return WageFactory::new();
    // }
}
