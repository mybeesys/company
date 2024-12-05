<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inventory\Models\ProductInventory;

class Product extends Model
{
    protected $table = 'product_products';

    use HasFactory;
    use SoftDeletes;
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
        'order',
        'prep_recipe',
        'recipe_yield',
        'group_combo',
        'set_price',
        'use_upcharge',
        'linked_combo',
        'promot_upsell'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public function addToFillable($key){
        return array_push($this->fillable, $key);
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
        return $this->hasMany(SerialNumber::class, 'product_id', 'id');
    }

    public function modifiers()
    {
        return $this->hasMany(ProductModifier::class, 'product_id', 'id');
    }
    public function combos()
    {
        return $this->hasMany(ProductCombo::class, 'product_id', 'id');
    }

    public function linkedCombos()
    {
        return $this->hasMany(ProductLinkedComboItem::class, 'product_id', 'id');
    }

    public function inventory()
    {
        return $this->belongsTo(ProductInventory::class, 'id', 'product_id');
    }

    public function unitTransfers()
    {
        return $this->hasMany(UnitTransfer::class, 'product_id', 'id');
    }
}
