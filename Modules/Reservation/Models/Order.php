<?php

namespace Modules\Reservation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Models\Subcategory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = 'reservation_orders';
        
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'no',
        'order_date',
        'order_status',
        'establishment_id',
        'table_id'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'order';

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }
}
