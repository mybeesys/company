<?php

namespace Modules\Product\Models;

use App\Helpers\TaxHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\ModifierFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\General\Models\Tax;

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
        'tax_id',
        'PLU',
        'color',
        'image',
        'order',
        'active',
        'prep_recipe',
        'recipe_yield'
    ];

    public function getPriceWithTaxAttribute()
    {
        return $this->price + TaxHelper::getTax($this->price,$this->tax->amount); // Calculate the field on the fly
    }

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

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id', 'id');
    }

    public function priceTiers()
    {
        return $this->hasMany(ModifierPriceTier::class, 'modifier_id', 'id');
    }

    public function recipe()
    {
        return $this->hasMany(RecipeModifier::class, 'modifier_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if($model->SKU == null){
                // Generate a unique random number
                do {
                    $SKU = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
                } while (self::where('SKU', $SKU)->exists());

                $model->SKU = $SKU;
            }
            if($model->barcode == null){
                do {
                    $barcode = Barcode::generateUPCA();
                } while (self::where('barcode', $barcode)->exists());

                $model->barcode = $barcode;
            }

        });
        static::updating(function ($model) {
            if($model->SKU == null){
                // Generate a unique random number
                do {
                    $SKU = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
                } while (self::where('SKU', $SKU)->exists());

                $model->SKU = $SKU;
            }
            if($model->barcode == null){
                do {
                    $barcode = Barcode::generateUPCA();
                } while (self::where('barcode', $barcode)->exists());

                $model->barcode = $barcode;
            }
        });
    }
}
