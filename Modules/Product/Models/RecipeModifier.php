<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\Ingredient;
use Modules\Product\Models\Modifier;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecipeModifier extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'product_recipe_modifiers';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'item_id',
        'modifier_id',
        'quantity',
        'order',
        'item_type'
    ];


    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'RecipeModifier';
    // Define relationships here (if any)

    public function ingredients()
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id', 'id');
    }

    public function modifiers()
    {
        return $this->belongsTo(Modifier::class, 'modifier_id', 'id');
    }

}
?>