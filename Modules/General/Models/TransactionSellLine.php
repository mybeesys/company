<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\General\Support\TransactionLineTaxRate;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\Product;
use Modules\Product\Models\UnitTransfer;

// use Modules\General\Database\Factories\TransactionSellLineFactory;

class TransactionSellLine extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function childLines()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function modifier()
    {
        return $this->belongsTo(Modifier::class, 'modifier_id');
    }

    public function unitTransfer()
    {
        return $this->belongsTo(UnitTransfer::class, 'unit_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function getTaxRatePercentAttribute(): string
    {
        return TransactionLineTaxRate::displayPercent(
            $this->tax_id !== null ? (string) $this->tax_id : null
        );
    }
}
