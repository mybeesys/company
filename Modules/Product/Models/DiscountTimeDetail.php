<?php
namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountTimeDetail extends Model
{
    use HasFactory;
    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'product_discount_times_detail';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'discount_time_id',
        'day_no',
        'from_time',
        'to_time',
        'active'
        // add more fields as needed
    ];

    public function getFillable(){
        return $this->fillable;
    }
}
?>