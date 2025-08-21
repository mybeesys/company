<?php

namespace Modules\Reservation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Reservation\Database\Factories\MenuTokenFactory;

class MenuToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'est_id',
        'title',
        'sub_title',
        'products',
        'cover',
        'token'
    ];

    protected $casts = [
        'products' => 'array',
    ];
}