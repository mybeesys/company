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

        $recipeQyt = Product::where('id', $id)->first();

        $products = RecipeProduct::where('product_id', $id)->with('products')->get();
        $response = [
            'products' => $products,
            'recipeQyt' => $recipeQyt,
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
            'productionQty' => 'required|numeric',
            'ingredients' => 'required|array',
            'ingredients.*.id' => 'required|numeric',
            'ingredients.*.order' => 'required|numeric',
            'ingredients.*.quantity' => 'required|numeric|min:0.01'

        ]);
        $from = $validated['from'];
        $to = $validated['to'];
        $productId = $validated['productId'];
        $productionQty = $validated['productionQty'];
        $ingredients = $validated['ingredients'];
        $product = Product::where('id', $productId)->first();
        $price = $product->price;
        DB::transaction(
            function () use ($from, $to, $ingredients, $productId, $productionQty, $price) {
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
                    'establishment_id' => $to,
                    'parent_id' => $transactionPurchasesId->id
                ]);
                foreach ($ingredients as $ingredient) {
                    $recipeProduct = RecipeProduct::with('unitTransfer')->where("item_id", $ingredient['id'])
                        ->where('order', $ingredient['order'])
                        ->where('product_id', $productId)
                        ->first();
                    $unit2 = $recipeProduct->unitTransfer->unit2;
                    $ingredientId = Product::where("id", $ingredient['id'])->first();
                    $ingredientCost = $ingredientId->cost;
                    if ($unit2 != null)
                        $unitPrice = $ingredient['quantity'] / $ingredientCost;
                    else
                        $unitPrice = $ingredient['quantity'] * $ingredientCost;
                    $sellId = TransactionSellLine::create([
                        'transaction_id' => $transactionSellId->id,
                        'unit_price_before_discount' => $unitPrice,
                        'unit_price' => $unitPrice,
                        'product_id' => $ingredient['id'],
                        'qyt' => $ingredient['quantity'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                TransactionePurchasesLine::create([
                    'transaction_id' => $transactionPurchasesId->id,
                    'unit_price_before_discount' => $price,
                    'unit_price' => $price,
                    'product_id' =>  $productId,
                    'qyt' => $productionQty,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'transactionsell_id' => $sellId->id,
                ]);
            }
        );



        return response()->json(["message" => "Done"]);
    }
}
