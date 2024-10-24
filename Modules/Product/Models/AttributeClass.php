<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Modules\Product\Database\Factories\ModifierFactory;

class AttributeClass extends Model
{
    use HasFactory;
    
    use SoftDeletes;
    
    protected $table = 'product_attributeclass';
        
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

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'attributeClass';
    public $childType = 'attribute';
    public $childKey = 'parent_id';

    public function children()
    {
        return $this->hasMany(Attribute::class, 'parent_id', 'id');
    }
}
