<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\ProductModifierFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductModifier extends Model
{

    protected $table = 'product_product_modifiers';

    use HasFactory;
    use SoftDeletes;
    public $timestamps = true;

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'modifier_class_id',
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

    public function modifierItem()
    {
        return $this->belongsTo(Product::class, 'modifier_id', 'id');
            // ->where('type', 'modifier');
    }
    public function modifierClass()
    {
        return $this->belongsTo(ModifierClass::class, 'modifier_class_id', 'id');
    }
    public function products()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}