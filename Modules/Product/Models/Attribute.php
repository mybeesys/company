<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Modules\Product\Database\Factories\ModifierFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Attribute extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'product_attributes';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'order',
        'active',
        'parent_id',
    ];

    public function scopeRestrictByFranchise($query)
    {
        $user = auth()->user();

        if ($user && $user->franchise_id) {
            return $query->whereExists(function ($q) use ($user) {
                $q->select(DB::raw(1))
                    ->from('franchise_product_permissions')
                    ->whereColumn('franchise_product_permissions.permitted_id', 'product_attributes.id')
                    ->where('franchise_product_permissions.permitted_type', 'attribute')
                    ->where('franchise_product_permissions.franchise_id', $user->franchise_id);
            });
        }

        return $query;
    }

    public function getFillable()
    {
        return $this->fillable;
    }

    public $type = 'attribute';

    public $parentKey = 'parent_id';

    public function attributeClass()
    {
        return $this->belongsTo(attributeClass::class, 'parent_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(Product_Attribute::class, 'product_id', 'id');
    }
}
