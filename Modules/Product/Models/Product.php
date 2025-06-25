<?php

namespace Modules\Product\Models;

use App\Helpers\TaxHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\General\Models\Tax;
use Modules\Inventory\Models\ProductInventory;
use Modules\Inventory\Models\ProductInventoryTotal;

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
        'type',
        'cost',
        'price',
        'category_id',
        'subcategory_id',
        'tax_id',
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
        'promot_upsell',
        'for_sell',
        'preparation_time',
        'calories',
        'show_in_menu',

        'vendor_id',
        'alertQuantity',
        'defaultOrderQuantity',
        'orderPriceWithTax',
        'track_inventory',
        'attribute_id1',
        'attribute_id2',
        'parent_id',
        'class_id',

    ];

    protected $appends = ['price_with_tax'];

    public function getPriceWithTaxAttribute()
    {
        return $this->price + TaxHelper::getTax($this->price, $this->tax ? $this->tax->amount : 0); // Calculate the field on the fly
    }

    public function getFillable()
    {
        return $this->fillable;
    }

    public function addToFillable($key)
    {
        return array_push($this->fillable, $key);
    }

    protected $attributes = [
        'type' => 'product'
    ];
    public $parentKey = 'subcategory_id';

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class, 'subcategory_id', 'id');
    }
    public function serial_numbers()
    {
        return $this->hasMany(SerialNumber::class, 'product_id', 'id');
    }
    public function modifiers()
    {
        return $this->hasMany(ProductModifier::class, 'product_id', 'id');
    }
    public function recipe()
    {
        return $this->hasMany(RecipeProduct::class, 'product_id', 'id');
    }
    public function combos()
    {
        return $this->hasMany(ProductCombo::class, 'product_id', 'id');
    }
    public function establishments()
    {
        return $this->hasMany(EstablishmentProduct::class, 'product_id', 'id');
    }
    public function priceTiers()
    {
        return $this->hasMany(ProductPriceTier::class, 'product_id', 'id');
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
    public function attributes()
    {
        return $this->hasMany(Product_Attribute::class, 'product_id', 'id');
    }
    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id', 'id');
    }
    public function total()
    {
        return $this->belongsTo(ProductInventoryTotal::class, 'product_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->SKU == null) {
                // Generate a unique random number
                do {
                    $SKU = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
                } while (self::where('SKU', $SKU)->exists());

                $model->SKU = $SKU;
            }
            if ($model->barcode == null) {
                do {
                    $barcode = Barcode::generateUPCA();
                } while (self::where('barcode', $barcode)->exists());

                $model->barcode = $barcode;
            }
            $model->order = OrderGenerator::generateOrder($model->order, 'subcategory_id', $model->subcategory_id, $model->table);
        });
        static::updating(function ($model) {
            if ($model->SKU == null) {
                // Generate a unique random number
                do {
                    $SKU = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
                } while (self::where('SKU', $SKU)->exists());

                $model->SKU = $SKU;
            }
            if ($model->barcode == null) {
                do {
                    $barcode = Barcode::generateUPCA();
                } while (self::where('barcode', $barcode)->exists());

                $model->barcode = $barcode;
            }
            $model->order = OrderGenerator::generateOrder($model->order, 'subcategory_id', $model->subcategory_id, $model->table);
        });
    }
    public function attribute1()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id1', 'id');
    }

    public function attribute2()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id2', 'id');
    }

    public function modifierClass()
    {
        return $this->belongsTo(ModifierClass::class, 'class_id', 'id');
    }


    public static function productsForSell()
    {
        return Product::where([['active', '=', 1], ['for_sell', '=', 1]])
            ->whereIn('type', ['product', 'variation'])
            ->with(['unitTransfers' => function ($query) {
                $query->whereNull('unit2');
            }])
            ->get();
    }

     public static function productsForPurchese()
    {
        return Product::where([['active', '=', 1], ['for_sell', '=', 1]])
            ->whereIn('type', ['product', 'variation'])
            ->with(['unitTransfers' => function ($query) {
                $query->whereNull('unit2');
            }])
            ->get();
    }

}