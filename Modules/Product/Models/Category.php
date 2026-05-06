<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'product_categories';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'parent_id',
        'active',
        'order',
    ];

    public function getFillable()
    {
        return $this->fillable;
    }

    public $type = 'category';

    // public $childType = 'category';
    public $childType = 'subcategory';

    // public $childKey = 'parent_id';
    public $childKey = 'category_id';

    public function scopeRestrictByFranchise($query)
    {
        $user = auth()->user();

        if ($user && $user->franchise_id) {
            $allowedProductIds = DB::table('franchise_product_permissions')
                ->where('franchise_id', $user->franchise_id)
                ->where('permitted_type', 'product')
                ->pluck('permitted_id');

            return $query->whereHas('products', function ($q) use ($allowedProductIds) {
                $q->whereIn('id', $allowedProductIds)
                    ->where('active', 1)
                    ->where('for_sell', 1);
            });
        }

        return $query;
    }

    public function children()
    {
        return $this->hasMany(Subcategory::class, 'category_id', 'id')->whereNull('parent_id');
    }

    public function childrenWithProducts()
    {
        $user = auth()->user();

        return $this->hasMany(Subcategory::class, 'category_id', 'id')
            ->whereNull('parent_id')
            ->where('active', 1)
            ->whereHas('products', function ($query) use ($user) {
                $query->where('for_sell', 1)->where('active', 1);

                if ($user && $user->franchise_id) {
                    $allowedProductIds = DB::table('franchise_product_permissions')
                        ->where('franchise_id', $user->franchise_id)
                        ->where('permitted_type', 'product')
                        ->pluck('permitted_id');

                    $query->whereIn('id', $allowedProductIds);
                }
            });
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class, 'category_id', 'id')->whereNull('parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->order = OrderGenerator::generateOrder($model->order, null, null, $model->table);
        });
        static::updating(function ($model) {
            $model->order = OrderGenerator::generateOrder($model->order, null, null, $model->table);
        });
    }
}
