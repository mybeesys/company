<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\SubcategoryFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subcategory extends Model
{
    use HasFactory;
    use SoftDeletes;
    
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

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'subcategory';
    public $parentKey = 'parent_id';
    public $parentType = 'subcategory';
    public $parentKey1 = 'category_id';
    public $parentType1 = 'category';
    // public $childType = 'subcategory';
    // public $childType1 = 'product';
    // public $childKey = 'parent_id';
    // public $childKey1 = 'subcategory_id';

    public $childType = 'product';
    public $childKey = 'subcategory_id';

    public function children()
    {
        return $this->hasMany(SubCategory::class, 'parent_id');
    }

    public function children1()
    {
        return $this->hasMany(Product::class, 'subcategory_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'subcategory_id', 'id');
    }

    public function productsForSale()
    {
        return $this->hasMany(Product::class, 'subcategory_id', 'id')
            ->where('for_sell', 1)->where('active' , 1);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id' , 'id');
    }

    public function parent()
    {
        return $this->belongsTo(Subcategory::class, 'parent_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->order = OrderGenerator::generateOrder($model->order, 'category_id', $model->category_id, $model->table);
        });
        static::updating(function ($model) {
            $model->order = OrderGenerator::generateOrder($model->order, 'category_id', $model->category_id, $model->table);
        });
    }
}
