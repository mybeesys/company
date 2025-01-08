<?php

namespace Modules\Product\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModifierPriceTier extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'product_modifier_price_tiers';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'price_tier_id',
        'modifier_id',
        'price'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public function addToFillable($key){
        return array_push($this->fillable, $key);
    }

    public $type = 'modifierPriceTier';

    public function modifier()
    {
        return $this->belongsTo(Modifier::class, 'modifier_id', 'id');
    }

    public function priceTier()
    {
        return $this->belongsTo(PriceTier::class, 'price_tier_id', 'id');
    }

}
