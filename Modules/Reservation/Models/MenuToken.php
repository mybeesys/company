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
        'est_ids',
        'title',
        'sub_title',
        'products',
        'custom_menu_id',
        'map_lat',
        'map_lng',
        'map_label',
        'allergy_document_path',
        'section_flags',
        'cover',
        'token',
    ];

    protected $casts = [
        'products' => 'array',
        'est_ids' => 'array',
        'section_flags' => 'array',
        'map_lat' => 'float',
        'map_lng' => 'float',
    ];
}