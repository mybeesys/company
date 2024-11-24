<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inventory\Enums\PurchaseOrderInvoiceStatus;
use Modules\Inventory\Enums\PurchaseOrderStatus;
use Modules\Product\Models\Vendor;

class PurchaseOrder extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'inventory_purchase_orders';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'no',
        'vendor_id',
        'po_status',
        'invoice_status',
        'po_date',
        'notes',
        'tax',
        'misc_amount',
        'shipping_amount',
        'total'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public function addToFillable($key){
        return array_push($this->fillable, $key);
    }

    public $type = 'purchaseOrder';

    protected $casts = [
        'po_status' => PurchaseOrderStatus::class,  // Cast the 'status' attribute to the Status enum
        'invoice_status' => PurchaseOrderInvoiceStatus::class,  // Cast the 'status' attribute to the Status enum
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id', 'id');
    }
}
