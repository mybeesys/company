<?php

namespace Modules\Establishment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Establishment\Database\Factories\EstablishmentFactory;

class Establishment extends Model
{
    use HasFactory;

    protected $table = 'establishment_establishments';
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): EstablishmentFactory
    // {
    //     // return EstablishmentFactory::new();
    // }
}
