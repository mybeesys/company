<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Employee\Models\Employee;
use Modules\Establishment\Models\Establishment;

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
        'status',
        'approved_at',
        'approved_by',
        'adjustment_entry_id',
        'notes',
        'created_by',
        'establishment_id',
    ];

    public function items()
    {
        return $this->hasMany(PeriodicInventoryItem::class);
    }

    public function adjustmentEntry()
    {
        return $this->belongsTo(AccountingAccTransMapping::class, 'adjustment_entry_id');
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }

    public function creator()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
}
