<?php

namespace Modules\Establishment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Establishment\Database\Factories\DivisionFactory;

class Division extends Model
{
    use HasFactory;

    protected $table = 'establishment_divisions';
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): DivisionFactory
    // {
    //     // return DivisionFactory::new();
    // }
}
