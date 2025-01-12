<?php

namespace Modules\Product\Models;

use App\Helpers\TaxHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Establishment\Models\Establishment;
use Modules\Product\Models\Product;

class ProductPriceTier extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'product_price_tiers';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'price_tier_id',
        'product_id',
        'price'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public function addToFillable($key){
        return array_push($this->fillable, $key);
    }

    public $type = 'productPriceTier';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function priceTier()
    {
        return $this->belongsTo(PriceTier::class, 'price_tier_id', 'id');
    }

}
