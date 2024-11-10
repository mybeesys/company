<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class   ProductComboItem extends Model
{

    protected $table = 'product_combos_items';

    use HasFactory;
    use SoftDeletes;
    public $timestamps = true;

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'item_id',
        'combo_id',
        'price'
    ];
}
