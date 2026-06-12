<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\Product;
use Modules\Establishment\Models\Establishment;

class ProductInventoryCost extends Model
{
    protected $table = 'product_inventory_costs';

    protected $fillable = [
        'product_id',
        'establishment_id',
        'qty_on_hand',
        'average_cost',
        'stock_value',
    ];

    protected $casts = [
        'qty_on_hand' => 'float',
        'average_cost' => 'float',
        'stock_value' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }
}
