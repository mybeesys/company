<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\ModifierFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LinkedCombo extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = 'product_linked_combos';
        
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'price',
        'barcode',
        'active',
        'combos',
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'linkedCombo';

    public function combos()
    {
        return $this->hasMany(ProductCombo::class, 'linked_combo_id', 'id');
    }

}
