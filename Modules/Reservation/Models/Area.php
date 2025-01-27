<?php

namespace Modules\Reservation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Models\Subcategory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Establishment\Models\Establishment;

class Area extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = 'reservation_areas';
        
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'establishment_id',
        'active'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public function addToFillable($keys){
        foreach ($keys as $key) {
            return array_push($this->fillable, $key);
        }
    }

    public $type = 'area';
    public $childType = 'table';
    public $childKey = 'area_id';

    public function children()
    {
        return $this->hasMany(Table::class, 'area_id', 'id');
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }
}
