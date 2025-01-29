<?php

namespace Modules\Screen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Screen\Database\Factories\VideoFactory;

class Promo extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'updated_at', 'created_at'];

    protected $table = "screen_promos";

    public function playlists()
    {
        return $this->morphToMany(Playlist::class, 'related', 'screen_playlists_relates');
    }
}
