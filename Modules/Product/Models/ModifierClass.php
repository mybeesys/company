<?php

namespace Modules\Product\Models;

use App\Helpers\FranchiseProductCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Modules\Product\Database\Factories\ModifierclassFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ModifierClass extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'product_modifierclasses';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'order',
        'active',
    ];

    public function getFillable()
    {
        return $this->fillable;
    }

    public $type = 'modifierClass';

    public $childType = 'modifier';

    public $childKey = 'class_id';

    public function children()
    {
        $user = auth()->user();

        return $this->hasMany(\Modules\Product\Models\Modifier::class, 'class_id', 'id')
            ->when(
                $user && $user->franchise_id && FranchiseProductCatalog::restrictsToGrantedProductsOnly($user),
                function ($query) use ($user) {
                    $query->whereExists(function ($q) use ($user) {
                        $q->select(DB::raw(1))
                            ->from('franchise_product_permissions')
                            ->whereColumn('franchise_product_permissions.permitted_id', 'product_products.id')
                            ->where('franchise_product_permissions.permitted_type', 'modifier')
                            ->where('franchise_product_permissions.franchise_id', $user->franchise_id);
                    });
                }
            );
    }

    public function products()
    {
        return $this->hasMany(ProductModifier::class, 'modifier_id', 'id');
    }
}
