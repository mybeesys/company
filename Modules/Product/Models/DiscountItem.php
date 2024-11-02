<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountItem extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = 'product_discount_items';
        
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'discount_id',
        'item_id'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'discountItem';
}
