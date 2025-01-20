<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Models\Ingredient;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\Product;
use Modules\Product\Models\UnitTransfer;
use Modules\Product\Models\Vendor;

class ProductInventory extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'inventory_product_inventories';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'product_id',
        'ingredient_id',
        'modifier_id',
        'threshold',
        'unit_id',
        'primary_vendor_id',
        'primary_vendor_unit_id',
        'primary_vendor_default_quantity',
        'primary_vendor_default_price'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'productInventory';

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'primary_vendor_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id', 'id');
    }

    public function modifier()
    {
        return $this->belongsTo(Modifier::class, 'modifier_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo(UnitTransfer::class, 'unit_id', 'id');
    }

    public function vendorUnit()
    {
        return $this->belongsTo(UnitTransfer::class, 'primary_vendor_unit_id', 'id');
    }

    public function total()
    {
        return $this->belongsTo(ProductInventoryTotal::class, 'product_id', 'id');
    }

}
