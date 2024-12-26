<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Inventory\Models\ProductInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Enums\InventoryOperationStatus;
use Modules\Inventory\Enums\PurchaseOrderInvoiceStatus;
use Modules\Inventory\Enums\PurchaseOrderStatus;
use Modules\Inventory\Models\InventoryOperation;
use Modules\Inventory\Models\InventoryOperationItem;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Models\PurchaseOrderItem;
use Modules\Product\Models\Product;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Vendor;

class PrepController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('inventory::prep.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $prep = new PurchaseOrder();
        $prep->product = new Product();
        $prep->items = [];
        return view('inventory::prep.create', compact('prep'));
    }
    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('inventory::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $inventoryOperation  = InventoryOperation::with('establishment')->find($id);
        $inventoryOperation->detail->addToFillable();
        foreach ($inventoryOperation->detail->getFillable() as $key) {
            $inventoryOperation->$key = $inventoryOperation->detail[$key];
            $inventoryOperation->addToFillable($key);
        }
        $inventoryOperation->addToFillable('op_status_name');
        $inventoryOperation->addToFillable('establishment');
        $inventoryOperation->op_status_name = $inventoryOperation->op_status->name;
        $resInventoryOperation = $inventoryOperation->toArray();
        $resInventoryOperation["items"] = [];
        foreach ($inventoryOperation->items as $item) {
            $newItem = $item->toArray();
            if(isset($item->product_id)){
                $newItem["product_id"] = $item->product_id.'-p';
                $item->product->unitTransfers = $item->product->unitTransfers;
                $prod = $item->product->toArray();
                $prod["id"] =  $item->product_id.'-p';
                $newItem["product"] =$prod;
            }
            if(isset($item->ingredient_id)){
                $newItem["product_id"] = $item->ingredient_id.'-i';
                $item->ingredient->unitTransfers = $item->ingredient->unitTransfers;
                $ingr = $item->ingredient->toArray();
                $ingr["id"] =  $item->ingredient_id.'-i';
                $newItem["product"] =$ingr;
            }
            $newItem["unit"] = $item->unit->toArray();
            $resInventoryOperation["items"][] =$newItem;
        }
        return view('inventory::prep.edit', compact('resInventoryOperation'));
    }
}
