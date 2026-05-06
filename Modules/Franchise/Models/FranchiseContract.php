<?php

namespace Modules\Franchise\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// use Modules\Franchise\Database\Factories\FranchiseContractFactory;

class FranchiseContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'franchise_id',
        'contract_duration',
        'start_date',
        'end_date',
        'reality_fees',
        'unite_no',
        'contract_file',
        'notes',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function getStatusLabelAttribute()
    {
        if ($this->end_date < now()) {
            return '<span class="badge badge-light-danger">'.__('franchise::lang.expired_contracts').'</span>';
        }

        return '<span class="badge badge-light-success">'.__('franchise::lang.active_contracts').'</span>';
    }
}
