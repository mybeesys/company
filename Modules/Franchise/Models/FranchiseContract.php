<?php

namespace Modules\Franchise\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Franchise\Database\Factories\FranchiseContractFactory;

class FranchiseContract extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): FranchiseContractFactory
    // {
    //     // return FranchiseContractFactory::new();
    // }
}
