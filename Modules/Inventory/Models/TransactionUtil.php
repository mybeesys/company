<?php

namespace Modules\Inventory\Models;

use Illuminate\Support\Facades\DB;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionSellLine;
use Modules\Inventory\Models\ModifierInventoryTotal;
use Modules\Inventory\Models\ProductInventoryTotal;
use Modules\Product\Models\Ingredient;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\Product;
use Modules\Product\Models\UnitTransfer;
use Modules\Product\Models\UnitTransferConvertor;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TransactionUtil
{

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

    private static function validate($establishmentId, $items)
    {
        $prods = [];
        $ingrs = [];
        $mods = [];
        foreach ($items as $newItem) {
            $item = new TransactionSellLine();
            $idd = explode("-", $newItem['product']['id']);
            $item->qty = $newItem['qty'];
            if ($idd[1] == 'p') {
                $item->product_id = $idd[0];
                if (isset($newItem['unit']))
                    $item->unit_id = $newItem['unit']['id'];
                else
                    $item->unit_id = UnitTransferConvertor::getMainUnit('P', $idd[0], null);
                $prods[] = $item;
            } else if ($idd[1] == 'm') {
                $item->modifier_id = $idd[0];
                if (isset($newItem['unit']))
                    $item->unit_id = $newItem['unit']['id'];
                else
                    $item->unit_id = UnitTransferConvertor::getMainUnit('M', $idd[0], null);
                $mods[] = $item;
            } else {
                $item->ingredient_id = $idd[0];
                if (isset($newItem['unit']))
                    $item->unit_id = $newItem['unit']['id'];
                else
                    $item->unit_id = UnitTransferConvertor::getMainUnit('M', $idd[0], null);
                $ingrs[] = $item;
            }
        }
        $result =  self::isValidQty($establishmentId, $prods, $ingrs, $mods,  $request["times"] ?? null);
        return $result;
    }

    private static function isValidQty($establishment_id, $products, $ingredients, $modifiers, $times)
    {
        $result = [];
        $prodIds =  array_map(function ($product) {
            return $product->product_id;
        }, $products);
        $ingrIds =  array_map(function ($ingredient) {
            return $ingredient->ingredient_id;
        }, $ingredients);
        $modIds =  array_map(function ($modifier) {
            return $modifier->modifier_id;
        }, $modifiers);
        $prodTotals = ProductInventoryTotal::where('establishment_id', '=', $establishment_id)
            ->whereIn('product_id', $prodIds)->get();
        foreach ($products as $prod) {
            $prodTotal = array_filter($prodTotals->toArray(), function ($value) use ($prod) {
                return $prod->product_id == $value["product_id"]; // Keep only even numbers
            });
            $prodTotal = reset($prodTotal);
            $totalQty = isset($times) && $times != null ? $prod->qty * $times : $prod->qty;
            $totalQty =  UnitTransferConvertor::convertUnit(
                'P',
                $prod->product_id,
                $prod->unit_id,
                null,
                $totalQty,
                null
            );
            if ((!$prodTotal) ||
                $prodTotal["qty"] == null ||
                $prodTotal["qty"] < $totalQty
            ) {
                $product = Product::find($prod->product_id);
                $result[] = [
                    "name_ar" => $product->name_ar,
                    "name_en" => $product->name_en,
                    "qty" => !$prodTotal || $prodTotal["qty"] == null ? 0 : $prodTotal["qty"]
                ];
            }
        }
        $modTotals = ModifierInventoryTotal::where('establishment_id', '=', $establishment_id)
            ->whereIn('modifier_id', $modIds)->get();
        foreach ($modifiers as $mod) {
            $modTotal = array_filter($modTotals->toArray(), function ($value) use ($mod) {
                return $mod->modifier_id == $value["modifier_id"]; // Keep only even numbers
            });
            $modTotal = reset($modTotal);
            $totalQty = isset($times) && $times != null ? $mod->qty * $times : $mod->qty;
            $totalQty =  UnitTransferConvertor::convertUnit(
                'M',
                $mod->modifier_id,
                $mod->unit_id,
                null,
                $totalQty,
                null
            );
            if ((!$modTotal) ||
                $modTotal["qty"] == null ||
                $modTotal["qty"] < $totalQty
            ) {
                $modifier = Modifier::find($mod->modifier_id);
                $result[] = [
                    "name_ar" => $modifier->name_ar,
                    "name_en" => $modifier->name_en,
                    "qty" => !$modTotal || $modTotal["qty"] == null ? 0 : $modTotal["qty"]
                ];
            }
        }
        return $result;
    }

    private static function fillMainUnit($items)
    {
        $newItems = [];
        foreach ($items as $newItem) {
            $idd = explode("-", $newItem['product']['id']);
            if ($idd[1] == 'p' && !isset($newItem['unit']))
                $newItem["unit"] = UnitTransferConvertor::getMainUnit('P', $idd[0], null);
            else if ($idd[1] == 'm' && !isset($newItem['unit']))
                $newItem["unit"] = UnitTransferConvertor::getMainUnit('M', $idd[0], null);
            else if ($idd[1] == 'i' && !isset($newItem['unit']))
                $newItem["unit"] = UnitTransferConvertor::getMainUnit('I', $idd[0], null);
            $newItems[] = $newItem;
        }
        return $newItems;
    }

    public static function createTransaction($type, $validated, $request, $withRelated)
    {
        $validated['type'] = $type;
        $validated['transaction_date'] = $validated['transaction_date'] ?? date("Y-m-d");
        $validated["ref_no"] = self::generatePoNo($type);
        $validated["status"] = 'draft';
        $validated["transfer_status"] = 'draft';
        $validated['created_by'] = Auth::user()->id;
        $validated["establishment_id"] = $request["establishment"]["id"];
        $purchaseItems = $request['purshaseItems'] ?? ($request['items'] ?? []);
        $purchaseItems = self::fillMainUnit($purchaseItems);
        $sellItems = $request['items'] ?? [];
        $sellItems = self::fillMainUnit($sellItems);
        $result =  self::validate($validated["establishment_id"], $sellItems ?? []);
        if (count($result) > 0)
            return $result;
        DB::transaction(function () use ($request, $validated, $sellItems, $purchaseItems, $withRelated) {
            $transaction = Transaction::create($validated);
            $sellLines = self::createSellLines($transaction->id, $sellItems);
            foreach ($sellLines as $sellLine)
                $sellLine->save();
            if ($withRelated) {
                $validated["ref_no"] = self::generatePoNo($validated['type']);
                $validated["establishment_id"] = $request["toEstablishment"]["id"];
                $validated["parent_id"] = $transaction->id;
                $related = Transaction::create($validated);
                $purchaseLines = self::createPurchaseLines($related->id, $purchaseItems, $sellLines);
                foreach ($purchaseLines as $purchaseLine)
                    $purchaseLine->save();
            }
        });
        return [];
    }

    public static function updateTransaction($validated, $request, $withRelated)
    {
        $transaction = Transaction::find($validated['id']);
        $transaction->transaction_date = $validated["transaction_date"];
        $transaction->description = $validated["description"];
        $validated["establishment_id"] = $request["establishment"]["id"];
        $purchaseItems = $request['purshaseItems'] ?? ($request['items'] ?? []);
        $purchaseItems = self::fillMainUnit($purchaseItems);
        $sellItems = $request['items'] ?? [];
        $sellItems = self::fillMainUnit($sellItems);
        $result =  self::validate($validated["establishment_id"], $sellItems ?? []);
        if (count($result) > 0)
            return $result;
        $related = null;
        if ($withRelated) {
            $related = Transaction::with('establishment')->where('parent_id', $validated['id'])->first();
            $related->transaction_date = $validated["transaction_date"];
            $related->description = $validated["description"];
            $related["establishment_id"] = $request["toEstablishment"]["id"];
        }
        $items = $request['items'] ?? [];
        DB::transaction(function () use ($transaction, $sellItems, $purchaseItems, $withRelated, $related) {
            $transaction->save();
            TransactionSellLine::where('transaction_id', '=', $transaction->id)->delete();
            $sellLines = self::createSellLines($transaction->id, $sellItems);
            foreach ($sellLines as $sellLine)
                $sellLine->save();
            if ($withRelated) {
                $related->save();
                TransactionePurchasesLine::where('transaction_id', '=', $related->id)->delete();
                $purchaseLines = self::createPurchaseLines($related->id, $purchaseItems, $sellLines);
                foreach ($purchaseLines as $purchaseLine)
                    $purchaseLine->save();
            }
        });
        return [];
    }

    public static function QuantityUpdate($validated, $request)
    {
        return DB::transaction(function () use ($validated, $request) {
            $transactionId = Transaction::find($validated['id']);
            $transaction = Transaction::where('parent_id', $validated['id'])->first();
            $transaction->description = $validated["description"];
            $transaction->transfer_status = 'partiallyReceived';
            $transactionId->transfer_status = 'partiallyReceived';
            $transactionId->status = 'Approved';
            $transaction->status = 'Approved';
            $transaction->save();
            $transactionId->save();
            $allConditionsMet = true;

            foreach ($request['items'] as $item) {
                $transactionePurchasesLine = TransactionePurchasesLine::where('transaction_id', $transaction->id)
                    ->where('transactionsell_id', $item['id'])
                    ->first();

                if ($item['quantityToReceive'] != 0) {
                    if ($transactionePurchasesLine && ($item['quantityToReceive'] <= $item['remainingQuantity'] && $item['quantityToReceive'] <= $item['qyt'])) {
                        if ($transactionePurchasesLine->qyt == 0) {
                            $transactionePurchasesLine->qyt = $item['quantityToReceive'];
                            $transactionePurchasesLine->save();
                        } else {
                            $newTransaction = new TransactionePurchasesLine();
                            $newTransaction->fill([
                                'transaction_id' => $transaction->id,
                                'transactionsell_id' => $item['id'],
                                'qyt' => $item['quantityToReceive'],
                                'unit_price_before_discount' => $item['unit_price_before_discount'],
                                'unit_price' => $item['unit_price'],
                                'unit_id' => $item['unit_id'],
                                'product_id' => str_replace('-p', '', $item['product_id']),
                                'created_at' => Carbon::now(),
                            ]);
                            $newTransaction->save();
                        }
                    } else {
                        $allConditionsMet = false;
                    }
                }
            }
            return response()->json(["message" => $allConditionsMet ? "Done" : "notEnoughQuantity"]);
        });
    }


    private static function createSellLines($transactionId, $items)
    {
        $result = [];
        foreach ($items as $newItem) {
            if (isset($newItem)) {
                $item = new TransactionSellLine();
                $item->transaction_id = $transactionId;
                $idd = explode("-", $newItem['product']['id']);
                $cost = null;
                if ($idd[1] == 'p') {
                    $item->product_id = $idd[0];
                    $prod = Product::find($idd[0]);
                    $cost = $prod->cost;
                } else if ($idd[1] == 'm') {
                    $item->modifier_id = $idd[0];
                    $mod = Modifier::find($idd[0]);
                    $cost = $mod->cost;
                } else {
                    $item->ingredient_id = $idd[0];
                    $ingr = Ingredient::find($idd[0]);
                    $cost = $ingr->cost;
                }
                $item->qyt = $newItem['qty'];
                $item->unit_price = $cost;
                $item->unit_price_before_discount = $cost;
                //$item->total_before_vat = $newItem['qty'] * $newItem['unit_price_before_discount'];
                if (isset($newItem['unit']))
                    $item->unit_id = $newItem['unit']['id'];
                $result[] = $item;
            }
        }
        return $result;
    }

    private static function createPurchaseLines($transactionId, $items, $sellLines)
    {
        $result = [];
        $sellLineCount = count($sellLines);
        $sellLineIndex = 0;
        foreach ($items as $newItem) {

            if (isset($newItem)) {
                $item = new TransactionePurchasesLine();
                $item->transaction_id = $transactionId;
                $idd = explode("-", $newItem['product']['id']);
                $cost = null;
                if ($idd[1] == 'p') {
                    $item->product_id = $idd[0];
                    $prod = Product::find($idd[0]);
                    $cost = $prod->cost;
                } else if ($idd[1] == 'm') {
                    $item->modifier_id = $idd[0];
                    $mod = Modifier::find($idd[0]);
                    $cost = $mod->cost;
                } else {
                    $item->ingredient_id = $idd[0];
                    $ingr = Ingredient::find($idd[0]);
                    $cost = $ingr->cost;
                }
                $item->qyt = 0;
                if ($sellLineCount > 0) {
                    $item->transactionsell_id = $sellLines[$sellLineIndex]->id;

                    // Move to the next sell line, resetting if we exceed the count
                    $sellLineIndex = ($sellLineIndex + 1) % $sellLineCount;
                }
                $item->unit_price = $cost;
                $item->unit_price_before_discount = $cost;
                //$item->total_before_vat = $newItem['qty'] * $newItem['unit_price_before_discount'];
                if (isset($newItem['unit']))
                    $item->unit_id = $newItem['unit']['id'];
                $result[] = $item;
            }
        }
        return $result;
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
            $newItem["product_id"] = $item->product_id . '-p';
            $prod = $item->product->toArray();
            $prod["id"] =  $item->product_id . '-p';
            $newItem["product"] = $prod;
        }
        if (isset($item->ingredient_id)) {
            $newItem["product_id"] = $item->ingredient_id . '-i';
            $ingr = $item->ingredient->toArray();
            $ingr["id"] =  $item->ingredient_id . '-i';
            $newItem["product"] = $ingr;
        }
        if (isset($item->modifier_id)) {
            $newItem["product_id"] = $item->modifier_id . '-m';
            $mod = $item->modifier->toArray();
            $mod["id"] =  $item->modifier_id . '-m';
            $newItem["product"] = $mod;
        }
        $newItem["unit"] = $item->unitTransfer?->toArray();
        return $newItem;
    }

    public static function getTransactions($type)
    {
        $transactions = Transaction::with('establishment')->where('type', '=', $type)->whereNull('parent_id')->get(); //with('establishment')->
        $relatedTransactions = Transaction::with('establishment')->whereIn('parent_id', $transactions->pluck('id')->toArray())->get();
        foreach ($transactions as $transaction) {
            $relatedTransaction = array_filter($relatedTransactions->toArray(), function ($trans) use ($transaction) {
                return $transaction->id == $trans["parent_id"]; // Keep only even numbers
            });
            $relatedTransaction = reset($relatedTransaction);
            $transaction->toEstablishment = $relatedTransaction['establishment'] ?? null;
        }
        return $transactions;
    }
}
