<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Establishment\Models\Establishment;
use Modules\Inventory\Enums\InventoryOperationStatus;
use Modules\Inventory\Enums\InventoryOperationType;



class InventoryOperation extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $relatedModels = [
        0 => \Modules\Inventory\Models\PurchaseOrder::class,
        1 => \Modules\Inventory\Models\Prep::class,
        2 => \Modules\Inventory\Models\PurchaseOrder::class,
        3 => null,
        4 => \Modules\Inventory\Models\Transfer::class
    ];

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'inventory_Operations';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'establishment_id',
        'no',
        'op_type',
        'op_status',
        'op_date',
        'total'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    protected $casts = [
        'op_type' => InventoryOperationType::class,
        'op_status' => InventoryOperationStatus::class  // Cast the 'status' attribute to the Status enum
     ];
 

    public function addToFillable($key){
        return array_push($this->fillable, $key);
    }

    public $type = 'inventoryOperation';

    public function hasDetail(){
        
        $relatedModel = $this->relatedModels[$this->op_type->value] ?? null;
       if(!$relatedModel)
            return false;
        else
            return true;
    }

    public function detail()
    {

        $relatedModel = $this->relatedModels[$this->op_type->value] ?? null;
        
        if(!$relatedModel)
            return null;

        if ($relatedModel) {
            return $this->belongsTo($relatedModel, 'id', 'operation_id');//->with('vendor');
        }

        //return $this->belongsTo($currentRelatedModel, 'operation_id', 'id');
        // Return null if no valid model is found
        //throw ex;
    }

    public function createDetail(array $attributes)
    {
        // Get the related model class based on op_type
        $modelClass = $this->relatedModels[$this->op_type->value] ?? null;

        if (!$modelClass) {
            return null;
        }

        // Create and return the related model instance
        return $modelClass::create($attributes);
    }

    public function makeDetail()
    {
        // Determine the model class based on op_type
        $modelClass = $this->relatedModels[$this->op_type->value] ?? null;

        if (!$modelClass) {
            return null;
        }

        // Return an instance of the related model without saving
        return new $modelClass();
    }

    public function items()
    {
        return $this->hasMany(InventoryOperationItem::class, 'operation_id', 'id');
    }
    public function establishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id', 'id');
    }
}
