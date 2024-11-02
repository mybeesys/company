<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\ModifierFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Modifier extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = 'product_modifiers';
        
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'class_id',
        'price',
        'cost',
        'PLU',
        'color',
        'image',
        'order',
        'active',
        'prep_recipe',
        'recipe_yield'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'modifier';
    public $parentKey = 'class_id';

    public function modifierClass()
    {
        return $this->belongsTo(ModifierClass::class, 'class_id', 'id');
    }

    
    public function products()
    {
        return $this->hasMany(ProductModifier::class, 'modifier_id', 'id');
    }

    // protected static function newFactory(): ModifierFactory
    // {
    //     // return ModifierFactory::new();
    // }
}
