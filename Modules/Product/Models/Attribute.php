<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\ModifierFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model
{
    use HasFactory;

    use SoftDeletes;
    
    protected $table = 'product_attributes';
        
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'order',
        'active', 
        'parent_id'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'attribute';
    public $parentKey = 'parent_id';

    public function attributeClass()
    {
        return $this->belongsTo(attributeClass::class, 'parent_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(Product_Attribute::class, 'product_id', 'id');
    }
}