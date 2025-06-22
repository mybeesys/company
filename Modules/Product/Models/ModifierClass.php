<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\ModifierclassFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModifierClass extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'product_modifierclasses';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'order',
        'active'
    ];

    public function getFillable()
    {
        return $this->fillable;
    }

    public $type = 'modifierClass';
    public $childType = 'modifier';
    public $childKey = 'class_id';

    public function children()
    {
        return $this->hasMany(Product::class, 'class_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(ProductModifier::class, 'modifier_id', 'id');
    }
}
