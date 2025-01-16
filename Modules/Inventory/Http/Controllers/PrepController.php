<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;
use Modules\Inventory\Models\Prep;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Product\Models\Product;

class PrepController extends Controller
{
    protected $inventoryOperationController;

    public function __construct(InventoryOperationController $inventoryOperationController){
        $this->inventoryOperationController = $inventoryOperationController;
    }
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
        $inventoryOperation  = Transaction::with('establishment')->find($id);
        $inventoryOperation->op_status_name = $inventoryOperation->status;//->name;
        $prep = Prep::where('operation_id' ,'=' , $inventoryOperation->id)->first();
        $inventoryOperation->product = $prep->product;
        $inventoryOperation->times = $prep->times;
        $resInventoryOperation = $inventoryOperation->toArray();
        $resInventoryOperation["items"] = [];
        foreach ($inventoryOperation->sell_lines as $item) {
            $newItem = $item->toArray();
            $newItem["qty"] = $item->qyt;
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
            if(isset($item->modifier_id)){
                $newItem["product_id"] = $item->modifier_id.'-m';
                $item->modifier->unitTransfers = $item->modifier->unitTransfers;
                $mod = $item->modifier->toArray();
                $mod["id"] =  $item->modifier_id.'-m';
                $newItem["product"] =$mod;
            }
            $newItem["unit_transfer"] = $item->unitTransfer?->toArray();
            $resInventoryOperation["items"][] =$newItem;
        }
        return view('inventory::prep.edit', compact('resInventoryOperation'));
    }

    public function getPreps()
    {
        $inventoryOperations = Transaction::with('establishment')->where('type', '=', 'PREP')->get();//with('establishment')->
        foreach ($inventoryOperations as $inventoryOperation) {
            $inventoryOperation->op_status_name = $inventoryOperation->op_status;
            $prep = Prep::where('operation_id' ,'=' , $inventoryOperation->id)->first();
            $inventoryOperation->product = $prep->product;
        }  
        return response()->json($inventoryOperations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'transaction_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);
        $validated['type'] = 'PREP';
        $validated['transaction_date'] = $validated['transaction_date'] ?? date("Y-m-d");
        if (!isset($validated['id'])) {
            $validated["ref_no"] = $this->inventoryOperationController->generatePoNo(1);
            $validated["status"] = 'draft';
            $validated["total_before_tax"] = 0;
            if(isset($request['items'])){
                $itemTotal = array_reduce($request['items'], function($carry, $item) {
                    return $carry + $item["qty"] * $item["unit_price_before_discount"];
                }, 0);
            }
            $inventoryOperation = new Transaction();
            $inventoryOperation->type = $validated['type'];
            $validated["establishment_id"] = $request["establishment"]["id"];
            $validated["total_before_tax"] = $itemTotal;
            if(isset($request['items'])){
                $prods = [];
                $ingrs = [];
                $mods = [];
                foreach ($request['items'] as $newItem) {
                    $item = new TransactionSellLine();
                    $idd = explode("-", $newItem['product']['id']);
                    $item->qty = $newItem['qty'];
                    if($idd[1] == 'p'){
                        $item->product_id = $idd[0];
                        $prods [] = $item;
                    }
                    else if($idd[1] == 'm'){
                        $item->modifier_id = $idd[0];
                        $mods [] = $item;
                    }
                    else{
                        $item->ingredient_id = $idd[0];
                        $ingrs [] = $item;
                    }
                }
                if($validated['type']!=0){
                    $result =  $this->inventoryOperationController->isValidQty($request["establishment"]["id"], $prods, $ingrs, $mods,  $request["times"] ?? null);
                    if(count($result) >0 )
                        return response()->json($result);
                }
            }
            DB::transaction(function () use ($validated, $request) {
                $inventoryOperation = Transaction::create($validated);
                $prep = new Prep();
                $prep->operation_id = $inventoryOperation->id;
                $prep->times =$request["times"];
                if (isset($request["product"])) {
                    $idd = explode("-",$request["product"]["id"]);
                    if($idd[1] == 'p')
                        $prep->product_id = $idd[0];
                    if($idd[1] == 'm')
                        $prep->modifier_id = $idd[0];
                    $prep->save();
                }
                if(isset($request['items'])){
                    foreach ($request['items'] as $newItem) {
                        if(isset($newItem)){
                            $item = new TransactionSellLine();
                            $item->transaction_id = $inventoryOperation->id;
                            $idd = explode("-", $newItem['product']['id']);
                            if($idd[1] == 'p')
                                $item->product_id = $idd[0];
                            else if($idd[1] == 'm')
                                $item->modifier_id = $idd[0];
                            else
                                $item->ingredient_id = $idd[0];
                            $item->qyt = $newItem['qty'];
                            $item->unit_price = $newItem['unit_price_before_discount'];
                            $item->unit_price_before_discount = $newItem['unit_price_before_discount'];
                            $item->total_before_vat = $newItem['qty'] * $newItem['unit_price_before_discount'];
                            if(isset($newItem['unit_transfer'])) 
                                $item->unit_id = $newItem['unit_transfer']['id'];
                            $item->save();
                        }
                    }
                }
            });
        }
        else {
            $inventoryOperation = Transaction::find($validated['id']);
            $validated["establishment_id"] = $request["establishment"]["id"];
            $inventoryOperation->transaction_date = $validated["transaction_date"];
            $inventoryOperation->description = $validated["description"];
            $inventoryOperation->establishment_id = $request["establishment"]["id"];
            $inventoryOperation->total_before_tax = 0;
            if(isset($request['items'])){
                $itemTotal = array_reduce($request['items'], function($carry, $item) {
                    return $carry + $item["qty"] * $item["unit_price_before_discount"];
                }, 0);
            }
            $inventoryOperation->total_before_tax = $itemTotal;
            DB::transaction(function () use ($inventoryOperation, $validated, $request) {
                $inventoryOperation->save();
                if(isset($request['items'])){
                    TransactionSellLine::where('transaction_id', '=', $inventoryOperation->id)->delete();
                    foreach ($request['items'] as $newItem) {
                        if(isset($newItem)){
                            $item = new TransactionSellLine();
                            $item->transaction_id = $inventoryOperation->id;
                            $idd = explode("-", $newItem['product']['id']);
                            if($idd[1] == 'p')
                                $item->product_id = $idd[0];
                            else if($idd[1] == 'm')
                                $item->modifier_id = $idd[0];
                            else
                                $item->ingredient_id = $idd[0];
                            $item->qyt = $newItem['qty'];
                            $item->unit_price_before_discount = $newItem['unit_price_before_discount'];
                            $item->unit_price = $newItem['unit_price_before_discount'];
                            $item->total_before_vat = $newItem['qty'] * $newItem['unit_price_before_discount'];
                            if(isset($newItem['unit_transfer'])) 
                                $item->unit_id = $newItem['unit_transfer']['id'];
                            $item->save();
                        }
                    }
            }
            });
        }
        return response()->json(["message" => "Done"]);
    }
}
