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
        foreach ($inventoryOperation->items as $item) {
            $item->product = $item->product;
            $item->unit = $item->unit;
        }
        $inventoryOperation->itemTotal = array_reduce($inventoryOperation->items->toArray(), 
        function ($carry, $item) {
            return $carry + $item["total"];
        }, 0);
        $image = base64_encode(file_get_contents(public_path('assets/media/logos/1-01.png')));

        $pdf = PDF::loadView('inventory::purchaseOrder.purchase_order_pdf', ['data' => $inventoryOperation, 'image'=> $image]);

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
        foreach ($inventoryOperation->items as $item) {
            $item->product = $item->product;
            $item->unit = $item->unit;
        }
        $inventoryOperation->itemTotal = array_reduce($inventoryOperation->items->toArray(), 
        function ($carry, $item) {
            return $carry + $item["total"];
        }, 0);
        
        $image = base64_encode(file_get_contents(public_path('assets/media/logos/1-01.png')));
        
        return view('inventory::purchaseOrder.purchase_order_pdf', ['data' => $inventoryOperation, 'image'=> $image]);
    }

    public function show($id)
    {
        return view('inventory::show');
    }
}