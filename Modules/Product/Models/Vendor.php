<?php
namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\Ingredient;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'product_vendors';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'name_en',
        'name_ar'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'Vendor';
    // Define relationships here (if any)

    public function ingredients()
    {
        return $this->hasMany(Ingredient::class, 'vendor_id', 'id');
    }


}
?>