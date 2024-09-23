<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Product\Database\Factories\CategoryFactory;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    public function items()
    {
        return $this->hasMany('Modules\Product\Entities\Item', 'category_id', 'id');
    }
}
