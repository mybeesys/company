<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\ProductFactory;

class Product extends Model
{
    protected $table = 'product_products';

    use HasFactory;
    
    public $timestamps = true;

    // Define fillable fields for mass assignment
    protected $fillable = [
        'name_ar',
        'name_en',
        'SKU',
        'barcode',
        'class',
        'cost',
        'price',
        'category',
        'subcategory',
        'description_ar',
        'description_en'
    ];
}
