<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;
use Modules\Inventory\Models\TransactionUtil;
use DB;
use Illuminate\Support\Facades\Auth;
use Modules\General\Models\TransactionePurchasesLine;

class WasteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public static function generatePoNo($opType)
    {
        $prefix = [
            'PO0' => 'PO0',
            'PREP' => 'PREP',
            'RMA' => 'RMA',
            'WASTE' => 'WASTE',
            'TRANSFER' => 'TRANS'
        ];
        // Get the last invoice number (if any)
        $lastPO = Transaction::where('type', '=', $opType)->orderBy('ref_no', 'desc')->first();

        // Check if there is a previous invoice
        $newPONumber = $prefix[$opType] . '-1001';  // Default starting number
        if ($lastPO) {
            // Extract the number part from the last invoice
            preg_match('/(\d+)/', $lastPO->ref_no, $matches);
            $lastNumber = (int)$matches[0];
            $newPONumber = $prefix[$opType] . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }

        return $newPONumber;
    }
    public static function prepareTransaction($id)
    {
        $transaction  = Transaction::with('establishment')->find($id); //::->find($id);
        $related = Transaction::with('establishment')->where('parent_id', $id)->first();
        $transaction->toEstablishment = $related?->establishment;
        $resTransaction = $transaction->toArray();
        $resTransaction["items"] = [];
        foreach ($transaction->sell_lines as $item) {
            $resTransaction["items"][] = self::prepareItem($item);
        }
        $resTransaction["purshaseItems"] = [];
        if ($related) {
            foreach ($related->purchases_lines as $purchaseItem) {
                $resTransaction["purshaseItems"][] = self::prepareItem($purchaseItem);
            }
        }
        return $resTransaction;
    }
    private static function prepareItem($item)
    {
        $newItem = $item->toArray();
        $transaction = Transaction::where('parent_id', $item->transaction_id)->first();
        if (!$transaction) {
            $newItem["receivedQuantity"] = 0;
            $newItem["remainingQuantity"] = $item->qyt;
        } else {
            $transactionePurchasesLine = TransactionePurchasesLine::where('transaction_id', $transaction->id)
                ->where('transactionsell_id', $item->id)
                ->sum('qyt');

            $newItem["receivedQuantity"] = $transactionePurchasesLine ? $transactionePurchasesLine : 0;
            $newItem["remainingQuantity"] = $transactionePurchasesLine ? ($item->qyt - $transactionePurchasesLine) : $item->qyt;
        }

        $newItem["qty"] = $item->qyt;
        $newItem["quantityToReceive"] = 0;
        if (isset($item->product_id)) {
            $newItem["product_id"] = $item->product_id;
            $prod = $item->product->toArray();
            $prod["id"] =  $item->product_id;
            $newItem["product"] = $prod;
        }
        if (isset($item->ingredient_id)) {
            $newItem["product_id"] = $item->ingredient_id;
            $ingr = $item->ingredient->toArray();
            $ingr["id"] =  $item->ingredient_id;
            $newItem["product"] = $ingr;
        }
        if (isset($item->modifier_id)) {
            $newItem["product_id"] = $item->modifier_id;
            $mod = $item->modifier->toArray();
            $mod["id"] =  $item->modifier_id;
            $newItem["product"] = $mod;
        }
        $newItem["unit"] = $item->unitTransfer?->toArray();
        return $newItem;
    }
    public function index()
    {
        return view('inventory::waste.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $waste = new Transaction();
        $waste->items = [];
        return view('inventory::waste.create', compact('waste'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $waste = self::prepareTransaction($id);
        Log::info($waste);
        return view('inventory::waste.edit', compact('waste'));
    }

    public function getWastes()
    {
        return response()->json(TransactionUtil::getTransactions('WASTE'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'transaction_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);
        if (!isset($validated['id'])) {
            Log::info($request);
            $result = TransactionUtil::createTransaction('WASTE', $validated, $request, false);
            if (count($result) > 0)
                return response()->json($result);
            else
                return response()->json(["message" => "Done"]);
        } else {
            $result = TransactionUtil::updateTransaction($validated, $request, false);
            if (count($result) > 0)
                return response()->json($result);
            else
                return response()->json(["message" => "Done"]);
        }
    }

    public function storeWaste(Request $request)
    {

        $validated = $request->validate([
            'id' => 'sometimes|required|integer|exists:transactions,id',
            'establishment.id' => 'required|integer|exists:est_establishments,id',
            'items' => 'required|array|min:1',
            'items.*.product.id' => 'required|integer|exists:product_products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'transaction_date' => 'nullable|date',
            'description' => 'nullable',
        ]);

        try {
            DB::beginTransaction();

            $transactionId = $validated['id'] ?? null;
            $transaction = null;
            if ($transactionId) {
                $transaction = Transaction::findOrFail($transactionId);
                $transaction->update([
                    'transaction_date' => $validated['transaction_date'] ?? now(),
                    'establishment_id' => $validated['establishment']['id'],
                    'description' => $validated['description'],

                ]);
                $requestedItemIds = collect($request->items)->pluck('id')->filter()->all();
                TransactionSellLine::where('transaction_id', $transaction->id)
                    ->whereNotIn('id', $requestedItemIds)
                    ->delete();
            } else {
                $transaction = Transaction::create([
                    'type' => 'WASTE',
                    'status' => 'draft',
                    'ref_no' => self::generatePoNo('WASTE'),
                    'created_by' => Auth::user()->id,
                    'transaction_date' => $validated['transaction_date'] ?? now(),
                    'description' => $validated['description'],
                    'establishment_id' => $validated['establishment']['id'],
                ]);
            }
            foreach ($request->items as $item) {
                $itemId = $item['id'] ?? null;
                if ($transactionId) {
                    $line = TransactionSellLine::findOrFail($itemId);
                    $line->update([
                        'qyt' => $item['qty'],
                        'unit_price' => $item['product']['cost'] ?? 0,
                        'unit_price_before_discount' => $item['product']['cost'] ?? 0,
                        'product_id' => $item['product']['id'],
                        'unit_id' => $item['unit']['id'] ?? null,
                    ]);
                } else {
                    TransactionSellLine::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product']['id'],
                        'qyt' => $item['qty'],
                        'unit_id' => $item['unit']['id'] ?? null,
                        'unit_price' => $item['product']['cost'] ?? 0,
                        'unit_price_before_discount' => $item['product']['cost'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json(["message" => "Done"]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing waste transaction: ' . $e->getMessage());

            return response()->json(["message" => "An error occurred while processing the waste transaction.", "error" => $e->getMessage()], 500);
        }
    }
}
