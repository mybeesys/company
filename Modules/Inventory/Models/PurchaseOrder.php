<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inventory\Enums\PurchaseOrderInvoiceStatus;
use Modules\Product\Models\Vendor;


class PurchaseOrder extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'inventory_Op_purchaseOrder';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = ['operation_id',
        'vendor_id',
        'invoice_status',
        'tax',
        'misc_amount',
        'shipping_amount'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public function addToFillable(){
        return array_push($this->fillable, 'vendor');
    }

    public $type = 'purchaseOrder';

    protected $casts = [
       'invoice_status' => PurchaseOrderInvoiceStatus::class,  // Cast the 'status' attribute to the Status enum
    ];

    public $validated = ['tax' => 'nullable|numeric',
            'misc_amount' => 'nullable|numeric',
            'shipping_amount' => 'nullable|numeric'];

    public function totals($validated){
        return (isset($validated["tax"]) ? $validated["tax"] : 0 ) + 
        (isset($validated["misc_amount"]) ? $validated["misc_amount"] : 0 ) +
        (isset($validated["shipping_amount"]) ? $validated["shipping_amount"] : 0 );
    }

    public function fillValidated($validated, $data){
        $validated["invoice_status"] = PurchaseOrderInvoiceStatus::unIvoiced;
        if (isset($data["vendor"])) {
            $validated["vendor_id"] = $data["vendor"]["id"];
        }
        return $validated;
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }
}
