<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\SerialNumberFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SerialNumber extends Model
{
    use HasFactory;
    use SoftDeletes;
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
