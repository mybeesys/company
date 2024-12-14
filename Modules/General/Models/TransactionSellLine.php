<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\General\Database\Factories\TransactionSellLineFactory;

class TransactionSellLine extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

}
