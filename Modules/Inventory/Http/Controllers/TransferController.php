<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Establishment\Models\Establishment;
use Illuminate\Http\Request;
use Modules\General\Models\Transaction;
use Modules\Inventory\Models\TransactionUtil;

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
        ]);
        if (!isset($validated['id'])) {
            $result = TransactionUtil::createTransaction('TRANSFER', $validated, $request, true);
            if(count($result) > 0)
                return response()->json($result);
            else
                return response()->json(["message" => "Done"]);
        }
        else {
            $result = TransactionUtil::updateTransaction($validated, $request, true);
            if(count($result) > 0)
                return response()->json($result);
            else
                return response()->json(["message" => "Done"]);
        }
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
