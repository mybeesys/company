<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCostLayer extends Model
{
    protected $table = 'inventory_cost_layers';

    protected $fillable = [
        'product_id',
        'establishment_id',
        'transaction_id',
        'transaction_line_id',
        'qty_remaining',
        'unit_cost',
        'layer_date',
    ];

    protected $casts = [
        'qty_remaining' => 'float',
        'unit_cost' => 'float',
        'layer_date' => 'datetime',
    ];
}
