<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\General\Models\Transaction;
use Modules\Inventory\Models\TransactionUtil;

class WasteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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
        $waste = TransactionUtil::prepareTransaction($id);
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
            $result = TransactionUtil::createTransaction('WASTE', $validated, $request, false);
            if(count($result) > 0)
                return response()->json($result);
            else
                return response()->json(["message" => "Done"]);
        }
        else {
            $result = TransactionUtil::updateTransaction($validated, $request, false);
            if(count($result) > 0)
                return response()->json($result);
            else
                return response()->json(["message" => "Done"]);
        }
    }

}
