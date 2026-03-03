<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\CustomMenuTime;
use Modules\Product\Models\CustomMenuItem;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\Establishment\Models\Establishment;

class CustomMenu extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'product_custom_menus';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'name_en',
        'name_ar',
        'application_type',
        'mode',
        'station_id', // establishment_id
        'active',
        'price_tier_id'
        // add more fields as needed
    ];


    public function scopeRestrictByFranchise($query)
{
    $user = auth()->user();

    if ($user && $user->franchise_id) {
        return $query->whereExists(function ($q) use ($user) {
            $q->select(DB::raw(1))
              ->from('franchise_custom_menu_permissions')
              ->whereColumn('franchise_custom_menu_permissions.custom_menu_id', 'product_custom_menus.id')
              ->where('franchise_custom_menu_permissions.franchise_id', $user->franchise_id);
        });
    }

    return $query;
}

    public function getFillable()
    {
        return $this->fillable;
    }

    public $type = 'customMenu';
    // Define relationships here (if any)

    public function dates()
    {
        return $this->hasMany(CustomMenuTime::class, 'custommenu_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(CustomMenuItem::class, 'custommenu_id', 'id');
    }
}
