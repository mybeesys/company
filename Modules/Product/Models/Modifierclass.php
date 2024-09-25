<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\ModifierclassFactory;

class Modifierclass extends Model
{
    use HasFactory;
    protected $table = 'product_modifierclasses';
        
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_ar',
        'name_en',

    ];

    public function modifiers()
    {
        return $this->hasMany(modifiers::class, 'class_id', 'id');
    }

    // protected static function newFactory(): ModifierclassFactory
    // {
    //     // return ModifierclassFactory::new();
    // }
}
