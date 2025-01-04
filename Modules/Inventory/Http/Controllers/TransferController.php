<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Establishment\Models\Establishment;
use Modules\Inventory\Models\InventoryOperation;
use Modules\Inventory\Models\PurchaseOrder;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('inventory::transfer.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $transfer = new PurchaseOrder();
        $transfer->establishment = new Establishment();
        $transfer->items = [];
        return view('inventory::transfer.create', compact('transfer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $inventoryOperation  = InventoryOperation::with('establishment')->find($id);
        if($inventoryOperation->hasDetail()){
            $inventoryOperation->detail->addToFillable();
            foreach ($inventoryOperation->detail->getFillable() as $key) {
                $inventoryOperation->$key = $inventoryOperation->detail[$key];
                $inventoryOperation->addToFillable($key);
            }
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
            $newItem["unit"] = $item->unit->toArray();
            $resInventoryOperation["items"][] =$newItem;
        }
        return view('inventory::transfer.edit', compact('resInventoryOperation'));
    }

    public function searchEstablishments(Request $request)
    {
        $query = $request->query('query');  // Get 'query' parameter
        $key = $request->query('key', '');
        $establishments = Establishment::where('name', 'like', '%' . $key . '%')
                            ->take(10)
                            ->get();
        return response()->json($establishments);
    }

    
}
