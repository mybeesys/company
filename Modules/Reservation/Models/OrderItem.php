<?php

namespace Modules\Reservation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = 'reservation_order_items';
        
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'quantity',
        'item_price',
        'item_total_price',
        'order_id',
        'item_id'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'orderItem';

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
