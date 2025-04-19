<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Establishment\Models\Establishment;
use Illuminate\Http\Request;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionSellLine;
use Modules\Inventory\Models\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $transfer = new Transaction();
        $transfer->items = [];
        return view('inventory::transfer.create', compact('transfer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $transfer = TransactionUtil::prepareTransaction($id);
        return view('inventory::transfer.edit', compact('transfer'));
    }

    public function getTransfer()
    {
        return response()->json(TransactionUtil::getTransactions('TRANSFER'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'transaction_date' => 'nullable|date',
            'description' => 'nullable|string',
            'type' => 'nullable|string',
        ]);
        if (!isset($validated['id'])) {
            $result = TransactionUtil::createTransaction('TRANSFER', $validated, $request, true);
            if (count($result) > 0)
                return response()->json($result);
            else
                return response()->json(["message" => "Done"]);
        }
        if (isset($validated['type'])) {
            return TransactionUtil::QuantityUpdate($validated, $request);
        } else {
            $result = TransactionUtil::updateTransaction($validated, $request, true);
            if (count($result) > 0)
                return response()->json($result);
            else
                return response()->json(["message" => "Done"]);
        }
    }
    public function fullReceiving(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $transactionId = Transaction::find($request->id);
            $transaction = Transaction::where('parent_id', $request->id)->first();
            $transaction->transfer_status = 'fullyReceived';
            $transactionId->transfer_status = 'fullyReceived';
            $transactionId->status = 'Approved';
            $transaction->status = 'Approved';
            $transaction->save();
            $transactionId->save();

            $items = TransactionSellLine::where('transaction_id', $transactionId->id)
                ->get();
            foreach ($items as $item) {
                $transactionePurchasesLine = TransactionePurchasesLine::where('transaction_id', $transaction->id)
                    ->where('transactionsell_id', $item->id)
                    ->first();
                $transactionePurchasesLine->qyt = $item->qyt;
                $transactionePurchasesLine->save();
            }

            return response()->json(["message" =>  "Done"]);
        });
    }

    public function rejected(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $transactionId = Transaction::find($request->id);
            $transaction = Transaction::where('parent_id', $request->id)->first();
            $transaction->status = 'rejected';
            $transactionId->status = 'rejected';
            $transaction->transfer_status = 'rejected';
            $transactionId->transfer_status = 'rejected';
            $transaction->status = 'cancel';
            $transactionId->status = 'cancel';
            $transaction->save();
            $transactionId->save();

            return response()->json(["message" =>  "Done"]);
        });
    }
    public function inTransit(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $transactionId = Transaction::find($request->id);
            $transaction = Transaction::where('parent_id', $request->id)->first();
            $transaction->transfer_status = 'inTransit';
            $transactionId->transfer_status = 'inTransit';
            $transaction->status = 'draft';
            $transactionId->status = 'draft';
            $transaction->save();
            $transactionId->save();

            return response()->json(["message" =>  "Done"]);
        });
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
    public function partialDeliveries($id1, $id2)
    {
        $transaction = Transaction::with('createdBy')->where('parent_id', $id1)->firstOrFail();

        $transactionPurchasesLines = TransactionePurchasesLine::where('transaction_id', $transaction->id)
            ->where('transactionsell_id', $id2)
            ->get()
            ->map(function ($line) use ($transaction) {
                return [
                    'qyt' => $line->qyt,
                    'created_at' => $line->created_at,
                    'created_by_name' => $transaction->createdBy ? $transaction->createdBy->name : null,
                ];
            });


        return response()->json(
            $transactionPurchasesLines,
        );
    }
}
