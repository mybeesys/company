<?php
namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\Ingredient;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitTransfer extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'product_unit_transfer';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'transfer',
        'unit1',
        'unit2',
        'product_id',
        'ingredient_id',
        'primary'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'UnitTransfer';

    public function units1()
    {
        return $this->belongsTo(Unit::class, 'unit1', 'id');
    }

    public function units2()
    {
        return $this->belongsTo(Unit::class, 'unit2', 'id');
    }

    public function products()
    {
        return $this->belongsTo(Product::class, 'productId', 'id');
    }

    public function ingredients()
    {
        return $this->belongsTo(Ingredient::class, 'ingredientId', 'id');
    }

}
?>