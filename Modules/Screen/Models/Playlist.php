<?php

namespace Modules\Screen\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Establishment\Models\Establishment;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Playlist extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'updated_at', 'created_at'];

    protected $table = "screen_playlists";

    protected function startTimeDate(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Carbon::parse($value)->format('Y-m-d H:i'),
        );
    }

    protected function casts(): array
    {
        return [
            'days_settings' => 'array',
        ];
    }

    public function promos()
    {
        return $this->morphedByMany(Promo::class, 'related', 'screen_playlists_relates')->withTimestamps()->orderBy('screen_playlists_relates.created_at', 'asc');;
    }

    public function devices()
    {
        return $this->morphedByMany(Device::class, 'related', 'screen_playlists_relates')->withTimestamps();
    }

    public function establishments()
    {
        return $this->morphedByMany(Establishment::class, 'related', 'screen_playlists_relates')->withTimestamps();
    }
}
