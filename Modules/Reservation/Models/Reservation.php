<?php

namespace Modules\Reservation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// use Modules\Reservation\Database\Factories\ReservationFactory;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reservation_reservations';

    protected $fillable = [
        'table_id',
        'customer_name',
        'customer_phone',
        'reservation_time',
        'guests_count',
        'status',
        'created_by',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class, 'table_id');
    }
}
