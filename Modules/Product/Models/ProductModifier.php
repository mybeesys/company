<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\ProductModifierFactory;

class ProductModifier extends Model
{

    protected $table = 'product_product_modifiers';

    use HasFactory;
    
    public $timestamps = true;

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'modifier_id',
        'active',
        'default',
        'required',
        'free_quantity',
        'free_type',
        'max_modifiers',
        'min_modifiers',
        'button_display',
        'modifier_display',
        'display_order'
    ];

    public function modifiers()
    {
        return $this->belongsTo(modifierclass::class, 'modifier_id', 'id');
    }
    public function products()
    {
        return $this->belongsTo(products::class, 'product_id', 'id');
    }

    // protected static function newFactory(): ProductModifierFactory
    // {
    //     // return ProductModifierFactory::new();
    // }
}
