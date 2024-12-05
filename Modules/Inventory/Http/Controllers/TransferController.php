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
        return view('inventory::transfer.edit', compact('inventoryOperation'));
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
