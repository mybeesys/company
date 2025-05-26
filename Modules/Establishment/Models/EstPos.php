<?php

namespace Modules\Establishment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Establishment\Models\Establishment;

class EstPos extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'est_pos';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',               // Device name
        'type',               // Device type
        'ref',                // Reference number
        'establishment_id',   // Branch ID
    ];

    public function establishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id', 'id');
    }
}
