<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Models\Ingredient;
use Modules\Product\Models\Product;
use Modules\Product\Models\UnitTransfer;
use Modules\Product\Models\Vendor;

class WarhouseProduct extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'inventory_warhouse_products';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'product_id',
        'warhouse_id'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'warhouseProduct';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function warhouse()
    {
        return $this->belongsTo(Warehouse::class, 'warhouse_id', 'id');
    }

}
