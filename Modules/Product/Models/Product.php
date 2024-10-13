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
        'category_id',
        'subcategory_id',
        'description_ar',
        'description_en',  
        'active',
        'sold_by_weight',
        'track_serial_number',
        'image',
        'color',
        'commissions',
        'order'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'product';
    public $parentKey = 'subcategory_id';



    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    
    public function subcategory()
    {
        return $this->belongsTo(subcategory::class, 'subcategory_id', 'id');
    }

    public function serial_numbers()
    {
        return $this->hasMany(serial_number::class, 'product_id', 'id');
    }

    public function modifiers()
    {
        return $this->hasMany(ProductModifier::class, 'product_id', 'id');
    }
}
