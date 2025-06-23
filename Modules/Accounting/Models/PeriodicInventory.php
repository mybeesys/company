<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Accounting\Database\Factories\PeriodicInventoryFactory;

class PeriodicInventory extends Model
{
    protected $fillable = [
        'start_date',
        'end_date',
        'opening_stock_value',
        'purchases_value',
        'closing_stock_value',
        'cogs',
        'adjustment_entry_id',
        'notes',
        'created_by'
    ];

    public function items()
    {
        return $this->hasMany(PeriodicInventoryItem::class);
    }

    public function adjustmentEntry()
    {
        return $this->belongsTo(AccountingAccTransMapping::class, 'adjustment_entry_id');
    }
}
