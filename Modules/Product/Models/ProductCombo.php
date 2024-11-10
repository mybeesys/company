<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCombo extends Model
{

    protected $table = 'product_product_combos';

    use HasFactory;
    use SoftDeletes;
    public $timestamps = true;

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'name_ar',
        'name_en',
        'barcode',
        'combo_saving',
        'quantity'
    ];

    public function items()
    {
        return $this->hasMany(ProductComboItem::class, 'combo_id', 'id');
    }
}
