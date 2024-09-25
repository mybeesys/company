<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Accounting\Database\Factories\AccountingAccTransMappingFactory;

class AccountingAccTransMapping extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];
    
    // protected static function newFactory(): AccountingAccTransMappingFactory
    // {
    //     // return AccountingAccTransMappingFactory::new();
    // }
}