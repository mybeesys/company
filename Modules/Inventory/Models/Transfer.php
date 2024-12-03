<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Establishment\Models\Establishment;
use Modules\Inventory\Enums\PurchaseOrderInvoiceStatus;
use Modules\Product\Models\Product;
use Modules\Product\Models\Vendor;


class Transfer extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'inventory_Op_transfer';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'operation_id',
        'establishment_id'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public function addToFillable(){
        return array_push($this->fillable, 'establishment');
    }

    public $type = 'transfer';

    public $validated = [];

    public function totals($validated){
        return 0;//(isset($validated["total"]) ? $validated["total"] * $validated["times"] : $validated["total"] );
    }

    public function fillValidated($validated, $data){
        if (isset($data["establishment"])) {
            $validated["establishment_id"] = $data["establishment"]["id"];
        }
        
        return $validated;
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id', 'id');
    }
}
