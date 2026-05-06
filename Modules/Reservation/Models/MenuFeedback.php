<?php

namespace Modules\Reservation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuFeedback extends Model
{
    use HasFactory;

    protected $table = 'menu_feedbacks';

    protected $fillable = [
        'token',
        'stars',
        'comment',
    ];

    protected $casts = [
        'stars' => 'integer',
    ];
}
