<?php

namespace Modules\Establishment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Establishment\Database\Factories\BrandFactory;

class Brand extends Model
{
    use HasFactory;

    protected $table = 'establishment_brands';
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): BrandFactory
    // {
    //     // return BrandFactory::new();
    // }
}
