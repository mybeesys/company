<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\SerialNumberFactory;

class SerialNumber extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = 
    [
        'serial_number',
        'product_id',
        'status'
    ];

    public function product()
    {
        return $this->belongsTo(product::class, 'product_id', 'id');
    }
}
