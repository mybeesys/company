<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Enums\InventoryOperationStatus;
use Modules\Inventory\Models\InventoryOperation;
use Modules\Inventory\Models\InventoryOperationItem;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Product\Models\Vendor;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('inventory::purchaseOrder.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $purchaseOrder = new PurchaseOrder();
        $purchaseOrder->vendor = new Vendor();
        $purchaseOrder->items = [];
        return view('inventory::purchaseOrder.create', compact('purchaseOrder'));
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
                $prod = $item->product->toArray();
                $prod["id"] =  $item->product_id.'-p';
                $newItem["product"] =$prod;
            }
            if(isset($item->ingredient_id)){
                $newItem["product_id"] = $item->ingredient_id.'-i';
                $ingr = $item->ingredient->toArray();
                $ingr["id"] =  $item->ingredient_id.'-i';
                $newItem["product"] =$ingr;
            }
            if(isset($item->modifier_id)){
                $newItem["product_id"] = $item->modifier_id.'-m';
                $mod = $item->modifier->toArray();
                $mod["id"] =  $item->modifier_id.'-m';
                $newItem["product"] =$mod;
            }
            $newItem["unit"] = $item->unit->toArray();
            $resInventoryOperation["items"][] =$newItem;
        }
        return view('inventory::purchaseOrder.edit', compact('resInventoryOperation'));
    }

    public function recieve($id)
    {
        $inventoryOperation  = InventoryOperation::find($id);
        $inventoryOperation->detail->addToFillable();
        foreach ($inventoryOperation->detail->getFillable() as $key) {
            $inventoryOperation->$key = $inventoryOperation->detail[$key];
            $inventoryOperation->addToFillable($key);
        }
        $inventoryOperation->addToFillable('op_status_name');
        $inventoryOperation->op_status_name = $inventoryOperation->op_status->name;
        $resInventoryOperation = $inventoryOperation->toArray();
        $resInventoryOperation["items"] = [];
        foreach ($inventoryOperation->items as $item) {
            foreach ($item->detail->getFillable() as $key) {
                $item->$key = $item->detail[$key];
            }
            $newItem = $item->toArray();
            if(isset($item->product_id)){
                $newItem["product"] = $item->product;
            }
            if(isset($item->ingredient_id)){
                $newItem["product"] = $item->ingredient;
            }
            if(isset($item->modifier_id)){
                $newItem["product"] = $item->modifier;
            }
            $newItem["unit"] = $item->unit;
            $resInventoryOperation["items"][] =$newItem;
        }
        return view('inventory::purchaseOrder.recieve', compact('resInventoryOperation'));
    }

    public function updateRecive(Request $request){
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'tax' => 'nullable|numeric',
            'misc_amount' => 'nullable|numeric',
            'shipping_amount' => 'nullable|numeric',
        ]);
        $inventoryOperation = InventoryOperation::find($validated['id']);
        $inventoryOperationItems = InventoryOperationItem::where("operation_id", "=",$inventoryOperation->id)->get();
        $inventoryOperation->notes = $validated["notes"];
        $inventoryOperation->total = 0;
        if(isset($request['items'])){
            $itemTotal = array_reduce($request['items'], function($carry, $item) {
                return $carry + $item["qty"] * $item["cost"];
            }, 0);
            $itemTotalQty = array_reduce($inventoryOperationItems->toArray(), function($carry, $item) {
                return $carry + $item["qty"];
            }, 0);
        }
        $detail = $inventoryOperation->detail;
        $total = $itemTotal + $detail->totals($validated);
        $inventoryOperation->total = $total;

        foreach ($detail->getFillable() as $key) {
            if(isset($validated[$key]))
                $detail->$key = $validated[$key];
        }
        DB::transaction(function () use ($inventoryOperation, $itemTotalQty, $request) {
            $recievedTotal = 0;
            if(isset($request['items'])){
                foreach ($request['items'] as $newItem) {
                    if(isset($newItem)){
                        if(isset($newItem['recievd_qty'])){
                            $recievedTotal += $newItem['recievd_qty'];
                            $item = InventoryOperationItem::find($newItem['id']);
                            $detailItem = $item->detail;
                            $detailItem->recievd_qty = $newItem['recievd_qty'];
                            $detailItem->save();
                        }
                    }
            }
            if($itemTotalQty == $recievedTotal)
                $inventoryOperation->op_status = InventoryOperationStatus::fullyReceived;
            else if($recievedTotal >0)
                $inventoryOperation->op_status = InventoryOperationStatus::partiallyReceived;
            $inventoryOperation->save();
        }});
        return response()->json(["message" => "Done"]);
    }
}
