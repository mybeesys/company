<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Models\Ingredient;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Product\Models\UnitTransfer;
use Modules\Product\Models\Vendor;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'inventory_purchase_order_items';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'ingredient_id',
        'taxed',
        'unit_id',
        'qty',
        'cost',
        'total'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'purchaseOrderItems';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo(UnitTransfer::class, 'unit_id', 'id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id', 'id');
    }

}
