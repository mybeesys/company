<?php

namespace Modules\Inventory\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Modules\Inventory\Models\InventoryOperation;

class PurchaseOrderReportController extends Controller
{
    public function generatePDF($id)
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
        $resInventoryOperation["itemTotal"] = array_reduce($inventoryOperation->items->toArray(), 
        function ($carry, $item) {
            return $carry + $item["total"];
        }, 0);
        $image = base64_encode(file_get_contents(public_path('assets/media/logos/1-01.png')));

        $pdf = PDF::loadView('inventory::purchaseOrder.purchase_order_pdf', ['data' => $resInventoryOperation, 'image'=> $image]);

        return $pdf->stream();
    }

    public function purchase_order_pdf($id)
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
        $resInventoryOperation["itemTotal"] = array_reduce($inventoryOperation->items->toArray(), 
        function ($carry, $item) {
            return $carry + $item["total"];
        }, 0);
        
        $image = base64_encode(file_get_contents(public_path('assets/media/logos/1-01.png')));
        
        return view('inventory::purchaseOrder.purchase_order_pdf', ['data' => $resInventoryOperation, 'image'=> $image]);
    }

    public function show($id)
    {
        return view('inventory::show');
    }
}