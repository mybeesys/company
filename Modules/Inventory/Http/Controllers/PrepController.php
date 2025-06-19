<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionSellLine;
use Modules\Inventory\Models\Prep;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Models\TransactionUtil;
use Modules\Product\Models\Product;
use Modules\Product\Models\RecipeProduct;
use Modules\Product\Models\UnitTransfer;

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
    public function needPreparationList()
    {
        $products = Product::where('prep_recipe', 1)->get();
        return response()->json($products);
    }
    public function establishmentList()
    {
        $establishmentList = Establishment::where('is_main', 0)->get();
        return response()->json($establishmentList);
    }
    public function getIngredientList($id)
    {

        $products = Product::where('type', 'ingredint')->get();


        $quantities = RecipeProduct::where('product_id', $id)->get();


        $response = [
            'products' => $products,
            'quantities' => $quantities,
        ];

        return response()->json($response);
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
            if (count($result) > 0)
                return response()->json($result);
            else
                return response()->json(["message" => "Done"]);
        } else {
            $result = TransactionUtil::updateTransaction($validated, $request, true);
            if (count($result) > 0)
                return response()->json($result);
            else
                return response()->json(["message" => "Done"]);
        }
    }


    public function prepareRecipe(Request $request)
    {
        $validated = $request->validate([
            'from' => 'required|numeric',
            'to' => 'required|numeric',
            'productId' => 'required|numeric',
            'ingredients' => 'required|array',
            'ingredients.*.id' => 'required|numeric',
            'ingredients.*.quantity' => 'required|numeric|min:0.01'
        ]);
        $from = $validated['from'];
        $to = $validated['to'];
        $productId = $validated['productId'];
        $ingredients = $validated['ingredients'];

        DB::transaction(function () use ($from, $to, $ingredients, $productId) {
            foreach ($ingredients as $ingredient) {
                $transactionPurchasesId = Transaction::create([
                    'type' => "PREP",
                    'status' => "approved",
                    'ref_no' => 'PREP-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'transaction_date' => now(),
                    'establishment_id' => $from
                ]);
                $transactionSellId = Transaction::create([
                    'type' => "PREP",
                    'status' => "approved",
                    'ref_no' => 'PREP-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'transaction_date' => now(),
                    'establishment_id' => $to
                ]);
                $sellId = TransactionSellLine::create([
                    'transaction_id' => $transactionSellId->id,
                    'unit_price_before_discount' => 0,
                    'unit_price' => 0,
                    'product_id' => $ingredient['id'],
                    'qyt' => $ingredient['quantity'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                TransactionePurchasesLine::create([
                    'transaction_id' => $transactionPurchasesId->id,
                    'unit_price_before_discount' => 0,
                    'unit_price' => 0,
                    'product_id' =>  $ingredient['id'],
                    'qyt' => $ingredient['quantity'],
                    'created_at' => now(),
                    'updated_at' => now(),
                    'transactionsell_id' => $sellId->id,
                ]);
            }
        });



        return response()->json(["message" => "Done"]);
    }
}
