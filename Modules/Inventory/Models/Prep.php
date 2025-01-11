<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inventory\Enums\PurchaseOrderInvoiceStatus;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\Product;
use Modules\Product\Models\Vendor;


class Prep extends Model
{
    use HasFactory;

    use SoftDeletes;

    // If the table name does not follow Laravel's conventions,
    // specify it here (e.g., if your table name is 'your_table_name')
    protected $table = 'inventory_Op_preps';

    // Specify the primary key if it is not 'id'
    protected $primaryKey = 'id';

    // If you want to allow mass assignment, define the fillable fields
    protected $fillable = [
        'operation_id',
        'product_id',
        'modifier_id',
        'times'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public function addToFillable(){
        $result= array_push($this->fillable, 'product');
    }

    public $type = 'prep';

    public $validated = ['times' => 'required|numeric'];

    public function totals($validated){
        return 0;//(isset($validated["total"]) ? $validated["total"] * $validated["times"] : $validated["total"] );
    }

    public function fillValidated($validated, $data){
        if (isset($data["product"])) {
            $idd = explode("-",$data["product"]["id"]);
            if($idd[1] == 'p')
                $validated["product_id"] = $idd[0];
            if($idd[1] == 'm')
                $validated["modifier_id"] = $idd[0];
        }
        
        return $validated;
    }

    public function preped()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function modifier()
    {
        return $this->belongsTo(Modifier::class, 'modifier_id', 'id');
    }

    public function getProductAttribute()
    {
        $result = null;
        if($this->preped)
        {
            $result = $this->preped->toArray();
            $result["id"] = $result["id"].'-p';
        }
        if($this->modifier)
        {
            $result = $this->modifier->toArray();
            $result["id"] = $result["id"].'-m';
        }
        return $result;
    }
}
