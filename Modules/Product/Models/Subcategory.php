<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\SubcategoryFactory;

class Subcategory extends Model
{
    use HasFactory;

    
    protected $table = 'product_subcategories';
        
    public $timestamps = true;
    
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [   
    'name_ar',
    'name_en',
    'category_id',
    'parent_id',
    'active',
    'order'
];

public function products()
{
    return $this->hasMany(Product::class, 'subcategory_id', 'id');
}

public function category()
{
    return $this->belongsTo(Category::class, 'category_id' , 'id');
}

public function parent()
{
    return $this->belongsTo(Subcategory::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(Subcategory::class, 'parent_id');
}
}
