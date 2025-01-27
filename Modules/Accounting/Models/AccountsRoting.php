<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Accounting\Database\Factories\AccountsRotingFactory;

class AccountsRoting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
}