<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\Product;
use Modules\Product\Models\UnitTransfer;

// use Modules\General\Database\Factories\TransactionePurchasesLineFactory;

class TransactionePurchasesLine extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
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
    public function transactionSellLine()
    {
        return $this->belongsTo(TransactionSellLine::class, 'transactionsell_id');
    }
}
