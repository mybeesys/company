<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inventory\Enums\InventoryOperationType;
use Modules\Inventory\Enums\PurchaseOrderInvoiceStatus;
use Modules\Inventory\Enums\PurchaseOrderStatus;
use Modules\Product\Models\Ingredient;
use Modules\Product\Models\Product;
use Modules\Product\Models\UnitTransfer;
use Modules\Product\Models\Vendor;



class InventoryOperationItem extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $relatedModels = [
        0 => PurchaseOrderItem::class,
        2 => PurchaseOrderItem::class
    ];
    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'inventory_Operation_items';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'operation_id',
        'product_id',
        'ingredient_id',
        'unit_id',
        'qty',
        'cost',
        'total',
        'item_type'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public function addToFillable($key){
        return array_push($this->fillable, $key);
    }

    public $type = 'inventoryOperationItem';

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

    public function parent()
    {
        return $this->belongsTo(InventoryOperation::class, 'operation_id', 'id');
    }

    public function makeDetail($opType)
    {
        // Determine the model class based on op_type
        $modelClass = $this->relatedModels[$opType] ?? null;

        if (!$modelClass) {
            return null;
        }

        // Return an instance of the related model without saving
        return new $modelClass();
    }

    public function createDetail(array $attributes, $opType)
    {
        // Get the related model class based on op_type
        $modelClass = $this->relatedModels[$opType] ?? null;

        if (!$modelClass) {
            throw new \Exception("Invalid op_type: {$opType}");
        }

        // Create and return the related model instance
        return $modelClass::create($attributes);
    }

    public function Detail()
    {
        $relatedModels = [
            0 => PurchaseOrderItem::class,
            2 => PurchaseOrderItem::class
        ];

        // Dynamically set the related model based on `op_type`
        $relatedModel = $relatedModels[$this->parent->op_type->value] ?? null;

        if ($relatedModel) {
            return $this->belongsTo($relatedModel, 'id','operation_item_id');
        }
        else{
            return null;
        }

        // Return null if no valid model is found
        //throw new \Exception("Invalid op_type: {$this->op_type->value}");
    }

}
