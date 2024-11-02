<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\ModifierFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = 'product_discounts';
        
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'function_id',
        'discount_type',
        'amount',
        'qualification',
        'qualification_type',
        'auto_apply',
        'item_level',
        'required_product_count',
        'minimum_amount'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'discount';

    public function items()
    {
        return $this->hasMany(DiscountItem::class, 'discount_id', 'id');
    }

    public function dates()
    {
        return $this->hasMany(DiscountTime::class, 'discount_id', 'id');
    }


}
