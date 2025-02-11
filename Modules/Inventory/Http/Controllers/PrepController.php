<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionSellLine;
use Modules\Inventory\Models\Prep;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Models\TransactionUtil;
use Modules\Product\Models\Product;

class PrepController extends Controller
{

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
        $prep = new Transaction();
        $prep->items = [];
        $prep->purshaseItems = [];
        return view('inventory::prep.create', compact('prep'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $prep = TransactionUtil::prepareTransaction($id);
        return view('inventory::prep.edit', compact('prep'));
    }

    public function getPreps()
    {
        return response()->json(TransactionUtil::getTransactions('PREP'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'transaction_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);
        if (!isset($validated['id'])) {
            $result = TransactionUtil::createTransaction('PREP', $validated, $request, true);
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
}
