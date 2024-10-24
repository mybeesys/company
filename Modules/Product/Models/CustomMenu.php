<?php
namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\CustomMenuTime;
use Modules\Product\Models\CustomMenuItem;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'station_id',
        'active'
        // add more fields as needed
    ];

    public function getFillable(){
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
?>