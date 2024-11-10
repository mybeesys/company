<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductLinkedComboUpcharge extends Model
{

    protected $table = 'product_linked_combos_upcharges';

    use HasFactory;
    use SoftDeletes;
    public $timestamps = true;

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_combo_id',
        'product_id',
        'combo_id',
        'price'
    ];
}
