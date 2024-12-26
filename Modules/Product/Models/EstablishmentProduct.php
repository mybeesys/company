<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Establishment\Models\Establishment;
use Modules\Product\Models\Product;

class EstablishmentProduct extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'product_establishment_products';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'product_id',
        'establishment_id'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public function addToFillable($key){
        return array_push($this->fillable, $key);
    }

    public $type = 'establishmentProduct';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id', 'id');
    }

}
