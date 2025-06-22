<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\ModifierFactory;
use Illuminate\Database\Eloquent\SoftDeletes;


class Product_Attribute extends Model
{
    use HasFactory;

    protected $table = 'product_product_attribute';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'attribute_id1',
        'attribute_id2',
        'name_ar',
        'name_en',
        'barcode',
        'SKU',
        'price',
        'starting'
    ];

    public function getFillable()
    {
        return $this->fillable;
    }



    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
