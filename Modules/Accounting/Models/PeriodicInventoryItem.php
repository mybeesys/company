<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Models\Product;

// use Modules\Accounting\Database\Factories\PeriodicInventoryItemFactory;

class PeriodicInventoryItem extends Model
{
     protected $fillable = [
        'periodic_inventory_id',
        'product_id',
        'system_quantity',
        'physical_quantity',
        'unit_cost',
        'variance',
        'notes'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
