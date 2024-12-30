<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\General\Models\Tax;

class ProductTax extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'product_taxes';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'tax_id',
        'product_id',
    ];


    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'productTaxes';
    // Define relationships here (if any)

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

}
?>