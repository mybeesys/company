<?php

namespace Modules\Screen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Establishment\Models\Establishment;
// use Modules\Screen\Database\Factories\DeviceFactory;

class Device extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'updated_at', 'created_at'];

    protected $table = "screen_devices";

    public function playlists()
    {
        return $this->morphToMany(Playlist::class, 'related', 'screen_playlists_relates');
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }
}
