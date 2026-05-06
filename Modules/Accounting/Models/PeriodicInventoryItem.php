<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\Product;

// use Modules\Accounting\Database\Factories\PeriodicInventoryItemFactory;

class PeriodicInventoryItem extends Model
{
    protected $fillable = [
        'periodic_inventory_id',
        'product_id',
        'unit_label',
        'unit_transfer_id',
        'unit_factor',
        'physical_quantity_input',
        'system_quantity',
        'physical_quantity',
        'unit_cost',
        'variance',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
