<?php
namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\CustomMenuTimeDetail;

class ServiceFeeDiningType extends Model
{
    use HasFactory;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'product_service_fee_dining_types';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'service_fee_id',
        'dining_type_id'
        // add more fields as needed
    ];

    public function getFillable(){
        return $this->fillable;
    }

}
?>