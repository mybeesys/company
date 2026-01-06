<?php

namespace Modules\Reservation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Employee\Models\Employee;
use Modules\General\Models\Transaction;

class Table extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reservation_tables';

    protected $fillable = [
        'code',
        'area_id',
        'steating_capacity',
        'table_status',
        'active',
        'assigned_waiter_id'
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function orders()
    {
        return $this->hasMany(Transaction::class, 'table_id');
    }

    public function activeOrder()
    {
        return $this->hasOne(Transaction::class, 'table_id')
            // ->whereIn('order_status', ['in_the_kitchen', 'done', 'delivered'])
            ->latest();
    }

    public function reservation()
    {
        return $this->hasOne(Reservation::class, 'table_id')
            ->where('status', 'active');
    }

    public function assignedWaiter()
    {
        return $this->belongsTo(Employee::class, 'assigned_waiter_id');
    }
}
