<?php

namespace Modules\Franchise\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Franchise\Database\Factories\FranchiseCompaniesFactory;

class FranchiseCompanies extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

public function contracts() {
    return $this->hasMany(FranchiseContract::class, 'franchise_id');
}

public function scopeWithoutContracts($query) {
    return $query->has('contracts', '=', 0);
}
}
