<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Establishment\Models\Establishment;
use Modules\Product\Models\EstablishmentProduct;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Product;
use Modules\Product\Models\RecipeProduct;
use Modules\Product\Models\Unit;
use Modules\Product\Models\UnitTransfer;

class IngredientController extends Controller
{
    private const PRODUCT_TYPE = 'ingredint';

    public function getIngredientsTree()
    {
        $ingredients = Product::with('unitTransfers')
            ->where('type', self::PRODUCT_TYPE)
            ->restrictByFranchise()
            ->get();

        $treeBuilder = new TreeBuilder();
        $tree = $treeBuilder->buildTreeIngredient($ingredients, null, 'Ingredient', null, null, null);
        return response()->json($tree);
    }

    public function ingredientProductList()
    {
        $product = Product::where('type', self::PRODUCT_TYPE)
            ->restrictByFranchise()
            ->get();

        $product = array_map(fn($item) => $item + ['type' => "{$item['id']}-" . self::PRODUCT_TYPE], $product->toArray());
        return response()->json($product);
    }

    public function getUnitTypeList()
    {
        return response()->json(Unit::all());
    }

    public function getVendors()
    {
        return response()->json(Contact::where('business_type', 'supplier')->get());
    }

    public function index()
    {
        return view('product::ingredient.index');
    }

    public function edit($id)
    {
        $ingredient = Product::where('type', self::PRODUCT_TYPE)
            ->with(['establishments.establishment', 'unitTransfers'])
            ->restrictByFranchise()
            ->findOrFail($id);

        $ingredient->allEstablishments = Establishment::where('is_main', 0)->get();
        return view('product::ingredient.edit', compact('ingredient'));
    }

    public function create()
    {
        $ingredient = new Product();
        $ingredient->establishments = Establishment::where('is_main', 0)->get();
        $ingredient->active = 1;
        return view('product::ingredient.create', compact('ingredient'));
    }

    private function validateInUse($ingredient_id)
    {
        $product = RecipeProduct::where([
            ['item_id', '=', $ingredient_id],
            ['item_type', '=', 'i']
        ])->first();

        return $product != null ? response()->json(["message" => "INGREDIENT_USED_RECIPE"]) : null;
    }

    public function store(Request $request)
    {
        $validated = $request->all();
        if (isset($validated['method']) && ($validated['method'] == "delete")) {
            $validateUsing = $this->validateInUse($validated['id']);
            if ($validateUsing != null) return $validateUsing;

            $product = Product::where('type', self::PRODUCT_TYPE)->restrictByFranchise()->find($validated['id']);
            if ($product) $product->delete();
            return response()->json(["message" => "Done"]);
        }

        if (isset($validated['id'])) {
            $validated['tax_id'] = $validated['order_tax_id'] ?? null;
            $res = $this->validateProduct($validated['id'], $validated);
            if (count($res) > 0) return $res;
            $this->saveProduct($validated, $request);
        } else {
            $res = $this->validateProduct(null, $validated);
            if (count($res) > 0) return $res;
            $this->createProduct($validated, $request);
        }
        return response()->json(["message" => "Done"]);
    }

    public function validateProduct($id, $product)
    {
        $checkResult = [];
        $uniqueFields = ['name_ar', 'name_en'];
        if (isset($product['SKU'])) $uniqueFields[] = 'SKU';

        $query = Product::where('type', self::PRODUCT_TYPE);
        if ($id != null) $query->where('id', '!=', $id);

        $query->where(function ($subQuery) use ($uniqueFields, $product) {
            foreach ($uniqueFields as $field) {
                $subQuery->orWhere($field, '=', $product[$field]);
            }
        });

        $products = $query->get();
        foreach ($uniqueFields as $field) {
            if ($products->contains($field, $product[$field])) {
                $checkResult[] = $field;
            }
        }

        return count($checkResult) > 0 ? ['message' => 'UNIQUE', 'data' => $checkResult] : $checkResult;
    }

    protected function saveProduct($validated, $request)
    {
        $product = Product::where('type', self::PRODUCT_TYPE)->restrictByFranchise()->find($validated['id']);
        $product->fill($validated);

        DB::transaction(function () use ($product, $request) {
            $product->save();
            $user = auth()->user();

            if ($user->franchise_id) {
                DB::table('franchise_product_permissions')->insert([
                    'franchise_id'    => $user->franchise_id,
                    'permitted_type'  => self::PRODUCT_TYPE,
                    'permitted_id'    => $product->id,
                    'created_at'      => now(),
                    'updated_at'      => now()
                ]);
            }
            $oldUnites = UnitTransfer::where('product_id', $product->id)->get();

            if (isset($request["transfer"])) {
                $ids = [];
                $insertedIds = [];
                $requestIds = array_map(fn($item) => $item["id"], $request["transfer"]);
                UnitTransfer::where('product_id', $product->id)->whereNotIn('id', $requestIds)->delete();

                foreach ($oldUnites as $old) $ids[] = ['oldId' => $old['id'], 'newId' => $old['id']];

                foreach ($request["transfer"] as $transfer) {
                    if ($transfer['id'] <= 0) {
                        $id = UnitTransfer::create([
                            'product_id' => $product->id,
                            'transfer' => ($transfer['transfer'] ?? null) != -100 ? $transfer['transfer'] : null,
                            'primary' => ($transfer['primary'] ?? false) ? 1 : 0,
                            'unit1' => $transfer['unit1'],
                            'unit2' => null
                        ])->id;
                        $insertedIds[] = ['id' => $id, 'unit2' => $transfer['unit2']];
                        $ids[] = ['oldId' => $transfer['id'], 'newId' => $id];
                    } else {
                        UnitTransfer::where('id', $transfer['id'])->update([
                            'unit1' => $transfer['unit1'],
                            'unit2' => $transfer['unit2'] ?? null,
                            'primary' => $transfer['primary'] ?? 0,
                            'transfer' => $transfer['transfer'] ?? null
                        ]);
                    }
                }
                foreach ($insertedIds as $transfer) {
                    foreach ($ids as $updateId) {
                        if ($transfer['unit2'] == $updateId['oldId']) {
                            UnitTransfer::where('id', $transfer['id'])->update(['unit2' => $updateId['newId']]);
                        }
                    }
                }
            }

            EstablishmentProduct::where('product_id', $product->id)->delete();
            if (isset($request["establishments"])) {
                foreach ($request["establishments"] as $newEst) {
                    EstablishmentProduct::create(['product_id' => $product->id, 'establishment_id' => $newEst["establishment_id"]]);
                }
            }
        });
    }

    protected function createProduct($validated, $request)
    {
        DB::transaction(function () use ($validated, $request) {
            $validated['type'] = self::PRODUCT_TYPE;
            $product = Product::create($validated);
            $user = auth()->user();

            if ($user->franchise_id) {
                DB::table('franchise_product_permissions')->insert([
                    'franchise_id'    => $user->franchise_id,
                    'permitted_type'  => self::PRODUCT_TYPE,
                    'permitted_id'    => $product->id,
                    'created_at'      => now(),
                    'updated_at'      => now()
                ]);
            }
            if (isset($request["transfer"])) {
                $ids = [];
                $insertedIds = [];

                foreach ($request["transfer"] as $transfer) {
                    $tran = [
                        'product_id' => $product->id,
                        'transfer' => isset($transfer['transfer']) && $transfer['transfer'] != -100 ? $transfer['transfer'] : null,
                        'primary' => isset($transfer['primary']) && $transfer['primary'] == true ? 1 : 0,
                        'unit1' => $transfer['unit1'],
                        'unit2' => null
                    ];

                    $id = UnitTransfer::create($tran)->id;
                    $insertedIds[] = ['id' => $id, 'unit2' => $transfer['unit2']];
                    $ids[] = ['oldId' => $transfer['id'], 'newId' => $id];
                }

                foreach ($insertedIds as $transfer) {
                    foreach ($ids as $updateId) {
                        if ($transfer['unit2'] == $updateId['oldId']) {
                            UnitTransfer::where('id', $transfer['id'])->update(['unit2' => $updateId['newId']]);
                        }
                    }
                }
            }

            if (isset($request["establishments"])) {
                foreach ($request["establishments"] as $newEst) {
                    EstablishmentProduct::create([
                        'product_id' => $product->id,
                        'establishment_id' => $newEst["id"]
                    ]);
                }
            }

            if ($request->unit) {
                UnitTransfer::create([
                    'unit1' => $request->unit,
                    'unit2' => null,
                    'product_id' => $product->id,
                    'primary' => 1,
                    'transfer' => -100,
                ]);
            }
        });
    }
}
