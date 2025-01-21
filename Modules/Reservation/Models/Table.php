<?php

namespace Modules\Reservation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Table extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = 'reservation_tables';
        
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'area_id',
        'steating_capacity',
        'table_status',
        'active'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'table';
    public $parentKey = 'area_id';


    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
}
