<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\Ingredient;
use Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecipeProduct extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'product_recipe_products';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'item_id',
        'product_id',
        'quantity',
        'order',
        'item_type',
        'unit_transfer_id'
    ];

    protected $relatedModels = [
        'p' => \Modules\Product\Models\Product::class,
        'i' => \Modules\Product\Models\Ingredient::class
    ];


    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'RecipeProduct';
    // Define relationships here (if any)

    public function ingredients()
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id', 'id');
    }

    public function products()
    {
        return $this->belongsTo(Product::class, 'item_id', 'id');
    }

    public function unitTransfer()
    {
        return $this->belongsTo(UnitTransfer::class, 'unit_transfer_id', 'id');
    }

    public function detail()
    {

        $relatedModel = $this->relatedModels[$this->item_type] ?? null;
        
        if(!$relatedModel)
            return null;

        if ($relatedModel) {
            return $this->belongsTo($relatedModel, 'item_id', 'id');//->with('vendor');
        }

        //return $this->belongsTo($currentRelatedModel, 'operation_id', 'id');
        // Return null if no valid model is found
        //throw ex;
    }

}
?>