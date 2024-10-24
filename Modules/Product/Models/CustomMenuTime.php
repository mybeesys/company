<?php
namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\CustomMenuTimeDetail;

class CustomMenuTime extends Model
{
    use HasFactory;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'product_custommenu_times';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'custommenu_id',
        'from_date',
        'to_date',
        'active'
        // add more fields as needed
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public function times()
    {
        return $this->hasMany(CustomMenuTimeDetail::class, 'custommenu_time_id', 'id');
    }


}
?>