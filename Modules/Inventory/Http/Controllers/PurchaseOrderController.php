<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Inventory\Models\ProductInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Enums\PurchaseOrderInvoiceStatus;
use Modules\Inventory\Enums\PurchaseOrderStatus;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Models\PurchaseOrderItem;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Vendor;

class PurchaseOrderController extends Controller
{
    protected $poStatusController;

    public function __construct(PoStatusController $poStatusController){
        $this->poStatusController = $poStatusController;
    }

    public function getPurchaseOrders()
    {
        $TreeBuilder = new TreeBuilder();
        $purchaseOrders = PurchaseOrder::with('vendor')->get();
        foreach ($purchaseOrders as $purchaseOrder) {
            $purchaseOrder->addToFillable('vendor');
            $purchaseOrder->addToFillable('po_status_name');
            $purchaseOrder->po_status_name = $purchaseOrder->po_status->name;
        }  
        $tree = $TreeBuilder->buildTree($purchaseOrders ,null, 'purchaseOrder', null, null, null);
        return response()->json($tree);
    }

    public function poStatusUpdate(Request $request)
    {
        // Validate incoming data (optional)
        $validated = $request->validate([
            'id' => 'required|numeric',
            'po_status' => 'required|numeric',
        ]);
        $purchaseOrder = PurchaseOrder::find($validated['id']);
        $purchaseOrder->po_status = $validated['po_status'];
        $purchaseOrder->save();
        $purchaseOrder->po_status_name = $purchaseOrder->po_status->name;
        return response()->json($purchaseOrder);
    }

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

    private function generatePoNo()
    {
        // Get the last invoice number (if any)
        $lastPO = PurchaseOrder::orderBy('no', 'desc')->first();
        
        // Check if there is a previous invoice
        $newPONumber = 'PO-1001';  // Default starting number
        if ($lastPO) {
            // Extract the number part from the last invoice
            preg_match('/(\d+)/', $lastPO->no, $matches);
            $lastNumber = (int)$matches[0];
            $newPONumber = 'PO-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }
        
        return $newPONumber;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'po_date' => 'required|date',
            'notes' => 'nullable|string',
            'tax' => 'nullable|numeric',
            'misc_amount' => 'nullable|numeric',
            'shipping_amount' => 'nullable|numeric',
        ]);
        if (!isset($validated['id'])) {
            if (isset($request["vendor"])) {
                $validated["vendor_id"] = $request["vendor"]["id"];
            }
            $validated["no"] = $this->generatePoNo();
            $validated["po_status"] = PurchaseOrderStatus::new_po;
            $validated["invoice_status"] = PurchaseOrderInvoiceStatus::unIvoiced;
            $validated["total"] = 0;
            if(isset($request['items'])){
                $itemTotal = array_reduce($request['items'], function($carry, $item) {
                    return $carry + $item["qty"] * $item["cost"];
                }, 0);
                $total = $itemTotal + 
                        (isset($validated["tax"]) ? $validated["tax"] : 0 ) + 
                        (isset($validated["misc_amount"]) ? $validated["misc_amount"] : 0 ) +
                        (isset($validated["shipping_amount"]) ? $validated["shipping_amount"] : 0 );
                $validated["total"] = $total;
            }
            DB::transaction(function () use ($validated, $request) {
            $purchaseOrder = PurchaseOrder::create($validated);
            if(isset($request['items'])){
                foreach ($request['items'] as $newItem) {
                    if(isset($newItem)){
                        $item = new PurchaseOrderItem();
                        $item->purchase_order_id = $purchaseOrder->id;
                        $item->product_id = $newItem['product']['id'];
                        $item->qty = $newItem['qty'];
                        $item->cost = $newItem['cost'];
                        $item->total = $newItem['qty'] * $newItem['cost'];
                        $item->taxed = 1;
                        if(isset($newItem['unit'])) 
                            $item->unit_id = $newItem['unit']['id'];
                        $item->save();
                    }
                }
            }
            });
        }
        else {
            $purchaseOrder = PurchaseOrder::find($validated['id']);
            if (isset($request["vendor"])) {
                $purchaseOrder->vendor_id = $request["vendor"]["id"];
            }
            $purchaseOrder->po_date = $validated["po_date"];
            $purchaseOrder->notes = $validated["notes"];
            $purchaseOrder->tax = $validated["tax"];
            $purchaseOrder->misc_amount = $validated["misc_amount"];
            $purchaseOrder->shipping_amount = $validated["shipping_amount"];
            $purchaseOrder->total = 0;
            if(isset($request['items'])){
                $itemTotal = array_reduce($request['items'], function($carry, $item) {
                    return $carry + $item["qty"] * $item["cost"];
                }, 0);
                $total = $itemTotal + 
                        (isset($validated["tax"]) ? $validated["tax"] : 0 ) + 
                        (isset($validated["misc_amount"]) ? $validated["misc_amount"] : 0 ) +
                        (isset($validated["shipping_amount"]) ? $validated["shipping_amount"] : 0 );
                $purchaseOrder->total = $total;
            }
            DB::transaction(function () use ($purchaseOrder, $validated, $request) {
            $purchaseOrder->save();
            if(isset($request['items'])){
                PurchaseOrderItem::where('purchase_order_id', '=', $purchaseOrder->id)->delete();
                foreach ($request['items'] as $newItem) {
                    if(isset($newItem)){
                        $item = new PurchaseOrderItem();
                        $item->purchase_order_id = $purchaseOrder->id;
                        $item->product_id = $newItem['product']['id'];
                        $item->qty = $newItem['qty'];
                        $item->cost = $newItem['cost'];
                        $item->total = $newItem['qty'] * $newItem['cost'];
                        $item->taxed = 1;
                        if(isset($newItem['unit'])) 
                            $item->unit_id = $newItem['unit']['id'];
                        $item->save();
                    }
                }
            }
            });
        }
        return response()->json(["message" => "Done"]);
    }

    public function updateRecive(Request $request){
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'tax' => 'nullable|numeric',
            'misc_amount' => 'nullable|numeric',
            'shipping_amount' => 'nullable|numeric',
        ]);
        $purchaseOrder = PurchaseOrder::find($validated['id']);
        $purchaseOrder->notes = $validated["notes"];
        $purchaseOrder->tax = $validated["tax"];
        $purchaseOrder->misc_amount = $validated["misc_amount"];
        $purchaseOrder->shipping_amount = $validated["shipping_amount"];
        $purchaseOrder->total = 0;
        $purchaseOrderItems = PurchaseOrderItem::where("purchase_order_id", "=",$purchaseOrder->id)->get();
        $itemTotalQty = array_reduce($purchaseOrderItems->toArray(), function($carry, $item) {
            return $carry + $item["qty"];
        }, 0);
        $itemTotalCost = array_reduce($purchaseOrderItems->toArray(), function($carry, $item) {
            return $carry + $item["qty"] * $item["cost"];
        }, 0);
        
        $total = $itemTotalCost + 
                        (isset($validated["tax"]) ? $validated["tax"] : 0 ) + 
                        (isset($validated["misc_amount"]) ? $validated["misc_amount"] : 0 ) +
                        (isset($validated["shipping_amount"]) ? $validated["shipping_amount"] : 0 );
        $purchaseOrder->total = $total;
        DB::transaction(function () use ($purchaseOrder, $itemTotalQty, $request) {
            $recievedTotal = 0;
            if(isset($request['items'])){
                foreach ($request['items'] as $newItem) {
                    if(isset($newItem)){
                        if(isset($newItem['recievd_qty'])){
                            $recievedTotal += $newItem['recievd_qty'];
                            $item = PurchaseOrderItem::find($newItem['id']);
                            $item->recievd_qty = $newItem['recievd_qty'];
                            $item->save();
                        }
                    }
            }
            if($itemTotalQty == $recievedTotal)
                $purchaseOrder->po_status = PurchaseOrderStatus::fullyReceived;
            else if($recievedTotal >0)
                $purchaseOrder->po_status = PurchaseOrderStatus::partiallyReceived;
            $purchaseOrder->save();
        }});
        return response()->json(["message" => "Done"]);
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
        $purchaseOrder  = PurchaseOrder::with('vendor')->find($id);
        foreach ($purchaseOrder->items as $item) {
            $item->product = $item->product;
            $item->unit = $item->unit;
        }
        return view('inventory::purchaseOrder.edit', compact('purchaseOrder'));
    }

    public function recieve($id)
    {
        $purchaseOrder  = PurchaseOrder::with('vendor')->find($id);
        foreach ($purchaseOrder->items as $item) {
            $item->product = $item->product;
            $item->unit = $item->unit;
        }
        return view('inventory::purchaseOrder.recieve', compact('purchaseOrder'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
