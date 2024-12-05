<?php
namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\Vendor;
use Modules\Product\Models\RecipeProduct;
use Modules\Product\Models\RecipeModifier;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inventory\Models\ProductInventory;

class Ingredient extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'product_ingredients';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'name_en',
        'name_ar',
        'cost',
        'unit_measurement',
        'SKU',
        'barcode',
        'active' ,
        'vendor_id',
        'reorder_point' ,
        'reorder_quantity',
        'yield_percentage'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public function addToFillable($key){
        return array_push($this->fillable, $key);
    }

    public $type = 'Ingredient';
    // Define relationships here (if any)

    public function vendors()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }
    public function recipeModifier()
    {
        return $this->hasMany(RecipeModifier::class, 'ingredient_id', 'id');
    }
    public function recipeProduct()
    {
        return $this->hasMany(RecipeProduct::class, 'ingredient_id', 'id');
    }
    public function inventory()
    {
        return $this->belongsTo(ProductInventory::class, 'id', 'ingredient_id');
    }

    public function unitTransfers()
    {
        return $this->hasMany(UnitTransfer::class, 'ingredient_id', 'id');
    }

}
?>