<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\ModifierFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceTier extends Model
{
    use HasFactory;

    use SoftDeletes;
    
    protected $table = 'price_tiers';
        
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'active', 
        'parent_id'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'priceTier';
}