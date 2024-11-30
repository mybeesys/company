<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Inventory\Models\InventoryOperation;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Product\Models\Vendor;

class RMAController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('inventory::rma.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $rma = new PurchaseOrder();
        $rma->vendor = new Vendor();
        $rma->items = [];
        return view('inventory::rma.create', compact('rma'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $inventoryOperation  = InventoryOperation::find($id);
        $inventoryOperation->detail->addToFillable();
        foreach ($inventoryOperation->detail->getFillable() as $key) {
            $inventoryOperation->$key = $inventoryOperation->detail[$key];
            $inventoryOperation->addToFillable($key);
        }
        $inventoryOperation->addToFillable('op_status_name');
        $inventoryOperation->op_status_name = $inventoryOperation->op_status->name;
        foreach ($inventoryOperation->items as $item) {
            $item->product = $item->product;
            $item->unit = $item->unit;
        }
        return view('inventory::rma.edit', compact('inventoryOperation'));
    }
}
