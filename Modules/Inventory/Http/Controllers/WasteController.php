<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Inventory\Models\InventoryOperation;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Product\Models\Vendor;

class WasteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('inventory::waste.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $waste = new PurchaseOrder();
        $waste->items = [];
        return view('inventory::waste.create', compact('waste'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $inventoryOperation  = InventoryOperation::find($id);
        if($inventoryOperation->hasDetail()){
            $inventoryOperation->detail->addToFillable();
            foreach ($inventoryOperation->detail->getFillable() as $key) {
                $inventoryOperation->$key = $inventoryOperation->detail[$key];
                $inventoryOperation->addToFillable($key);
            }
        }
        $inventoryOperation->addToFillable('op_status_name');
        $inventoryOperation->op_status_name = $inventoryOperation->op_status->name;
        foreach ($inventoryOperation->items as $item) {
            $item->product = $item->product;
            $item->unit = $item->unit;
        }
        return view('inventory::waste.edit', compact('inventoryOperation'));
    }
}
