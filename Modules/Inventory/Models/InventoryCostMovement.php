<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCostMovement extends Model
{
    protected $table = 'inventory_cost_movements';

    protected $fillable = [
        'product_id',
        'establishment_id',
        'transaction_id',
        'transaction_line_id',
        'line_side',
        'movement_type',
        'qty_delta',
        'unit_cost',
        'total_cost',
        'average_cost_after',
        'qty_on_hand_after',
        'stock_value_after',
        'movement_date',
    ];

    protected $casts = [
        'qty_delta' => 'float',
        'unit_cost' => 'float',
        'total_cost' => 'float',
        'average_cost_after' => 'float',
        'qty_on_hand_after' => 'float',
        'stock_value_after' => 'float',
        'movement_date' => 'datetime',
    ];
}
