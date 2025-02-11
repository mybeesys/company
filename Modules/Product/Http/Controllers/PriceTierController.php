<?php

namespace Modules\Product\Http\Controllers;

use App\Helpers\TaxHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\ModifierPriceTier;
use Modules\Product\Models\PriceTier;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductPriceTier;
use Modules\Product\Models\TreeBuilder;

class PriceTierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::priceTier.index' ); 
    }
    
    public function getPriceTierlist()
    {
        $TreeBuilder = new TreeBuilder();
        $result = PriceTier::all();
        $tree = $TreeBuilder->buildTree($result ,null, 'priceTier', null, null, null);
        return response()->json($tree);
    }

    private function validateInUse($price_tier_id){
        $product = ProductPriceTier::where([['price_tier_id', '=', $price_tier_id]])->first();
        if($product != null)
            return response()->json(["message"=>"PRICE_TIER_USED_INVENTORY"]);
        return null;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'active' => 'required|boolean',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);
  
        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $validateUsing = $this->validateInUse($validated['id']);
            if($validateUsing != null)
                return $validateUsing;
            $result = PriceTier::find($validated['id']);
            $result->delete();
            return response()->json(["message"=>"Done"]);
        }

        if(!isset($validated['id']))
        {
            $result = PriceTier::where('name_ar', $validated['name_ar'])->first();
            if($result != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $result = PriceTier::where('name_en', $validated['name_en'])->first();
            if($result != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            $result = PriceTier::create($validated);
        }
         else
         {
            $result = PriceTier::where('name_ar', $validated['name_ar'])->where('id', '!=', $validated['id'])->first();
            if($result != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $result = PriceTier::where('name_en', $validated['name_en'])->where('id', '!=', $validated['id'])->first();
            if($result != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

            $result = PriceTier::find($validated['id']);
            $result->name_ar = $validated['name_ar'];
            $result->name_en = $validated['name_en'];
            $result->active = $validated['active'];
            $result->save();
         }

        return response()->json(["message"=>"Done"]);
    }

    public function searchPriceTiers(Request $request)
    {
        $query = $request->query('query');  // Get 'query' parameter
        $key = $request->query('key', '');
        $result = PriceTier::Where('active', 1)->where(function ($query) use($key) {
                                $query->where('name_ar', 'like', '%' . $key . '%')
                                    ->orWhere('name_en', 'like', '%' . $key . '%');
                            })
                            ->take(10)
                            ->get();
        return response()->json($result);
    }

    public function searchProductPriceTiers(Request $request)
    {
        $query = $request->query('query');  // Get 'query' parameter
        $key = $request->query('key', '');
        $priceTiers =[];
        if ($request->has('id')) {
            $idd = explode("-",$request['id']);
            if($idd[1] == 'p')
                $request['product_id'] = $idd[0];
            else if($idd[1] == 'm')
                $request['modifier_id'] = $idd[0];
            else
                $request['ingredient_id'] = $idd[0];
        }
        $porduct_id = $request->query('product_id', '');
        $modifier_id=  $request->query('modifier_id', '');
        $key = $request->query('key', '');
        $prod = null;
        if ($request->has('product_id')) {
            $prod = Product::find($porduct_id);
            $priceTiers = ProductPriceTier::with('priceTier')->where('product_id', '=', $porduct_id)
                            ->whereHas('priceTier', function($query) use($key){
                                $query->where('name_ar', 'like', '%' . $key . '%')
                                    ->orWhere('name_en', 'like', '%' . $key . '%');
                            })
                            ->take(10)
                            ->get();
        }
        if ($request->has('modifier_id')) {
            $prod = Modifier::find($modifier_id);
            $priceTiers = ModifierPriceTier::with('priceTier')->where('modifier_id', '=', $modifier_id)
                            ->whereHas('priceTier', function($query) use($key){
                                $query->where('name_ar', 'like', '%' . $key . '%')
                                    ->orWhere('name_en', 'like', '%' . $key . '%');
                            })
                            ->take(10)
                            ->get();
        }
        foreach($priceTiers as $priceTier){
            $priceTier->name_ar = $priceTier->priceTier->name_ar;
            $priceTier->name_en = $priceTier->priceTier->name_en;
            $priceTier->price_with_tax = $priceTier->price + ($prod->tax ? TaxHelper::getTax($priceTier->price, $prod->tax->amount) : 0);
        }
        return response()->json($priceTiers);
    }

}
