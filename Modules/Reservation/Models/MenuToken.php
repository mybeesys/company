<?php

namespace Modules\Reservation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// use Modules\Reservation\Database\Factories\MenuTokenFactory;

class MenuToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'est_id',
        'est_ids',
        'est_locations',
        'title',
        'sub_title',
        'products',
        'custom_menu_id',
        'map_lat',
        'map_lng',
        'map_label',
        'allergy_document_path',
        'section_flags',
        'allergen_visible_keys',
        'cover',
        'token',
    ];

    protected $casts = [
        'products' => 'array',
        'est_ids' => 'array',
        'est_locations' => 'array',
        'section_flags' => 'array',
        'allergen_visible_keys' => 'array',
        'map_lat' => 'float',
        'map_lng' => 'float',
    ];
}
