<?php

namespace Modules\Employee\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Establishment\Models\Establishment;

class Shift extends BaseScheduleModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class);
    }

    public function getDateAttribute()
    {
        return Carbon::parse($this->startTime)->format('Y-m-d');
    }
}
