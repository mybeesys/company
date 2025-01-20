<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Models\Ingredient;
use Modules\Product\Models\Product;
use Modules\Product\Models\UnitTransfer;
use Modules\Product\Models\Vendor;

class ModifierInventoryTotal extends Model
{
    use HasFactory;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'modifier_inventories';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'modifier_id',
        'establishment_id',
        'qty'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'modifierInventoryTotal';
}
