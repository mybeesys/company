<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Discount;
use Modules\Product\Models\DiscountItem;
use Modules\Product\Models\DiscountTime;
use Modules\Product\Models\DiscountTimeDetail;
use Modules\Product\Models\ModifierClass;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Modifier;

class DiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::discount.index' ); 
    }

    public function getDiscounts()
    {
        $TreeBuilder = new TreeBuilder();
        $discount = Discount::all();
        $tree = $TreeBuilder->buildTree($discount, null, 'discount', null, null, null);
        return response()->json($tree);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'function_id' => 'required|numeric',
            'discount_type' => 'required|numeric',
            'amount' => 'nullable|numeric',
            'qualification' => 'nullable|numeric',
            'qualification_type' => 'nullable|numeric',
            'auto_apply' => 'required|boolean',
            'item_level' => 'required|boolean',
            'required_product_count' => 'nullable|numeric',
            'minimum_amount' => 'nullable|numeric',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);

        if (isset($validated['method']) && ($validated['method'] == "delete")) {
            $customMenu = Discount::find($validated['id']);
            $customMenu->delete();
            return response()->json(["message" => "Done"]);
        }

        if (!isset($validated['id'])) {
            //try {
                $cDiscount = Discount::where('name_ar', $validated['name_ar'])->first();
                if($cDiscount != null)
                    return response()->json(["message"=>"NAME_AR_EXIST"]);
                $cDiscount = Discount::where('name_en', $validated['name_en'])->first();
                if($cDiscount != null)
                    return response()->json(["message"=>"NAME_EN_EXIST"]);
                DB::transaction(function () use ($validated, $request) {
                    $discount = Discount::create($validated);
                    if (isset($request["dates"])) {
                        $discountDate = $request["dates"];
                        $discountDate['discount_id'] = $discount->id;
                        $result = DiscountTime::create($discountDate);
                        foreach ($discountDate['times'] as $timed) {
                            $dated['discount_time_id'] = $result->id;
                            $dated['day_no'] = $timed['day_no'];
                            $dated['from_time'] = $timed['from_time'];
                            $dated['to_time'] = $timed['to_time'];
                            $dated['active'] = false;
                            $result1 = DiscountTimeDetail::create($dated);
                        }
                        if(isset($request['items'])){
                            DiscountItem::where('discount_id', '=', $discount->id)->delete();
                            foreach ($request['items'] as $newItem) {
                                if(isset($newItem)){
                                    $item = new DiscountItem();
                                    $item->item_id = $newItem['item_id'];
                                    $item->discount_id = $discount->id;
                                    $prod = $item->save();   
                                }
                            }
                        }
                    }
                });
            //} catch (QueryException $e) {
            //    return response()->json(["message" => "ERROR_SAVING"]);
            //}
        } else {
            $cDiscount = Discount::where([
                ['id', '!=', $validated['id']],
                ['name_ar', '=', $validated['name_ar']]])->first();
            if($cDiscount != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $cDiscount = Discount::where([
                    ['id', '!=', $validated['id']],
                    ['name_en', '=', $validated['name_en']]])->first();
            if($cDiscount != null)
                    return response()->json(["message"=>"NAME_EN_EXIST"]);
            $discount = Discount::find($validated['id']);
            $discount->name_ar = $validated['name_ar'];
            $discount->name_en = $validated['name_en'];
            $discount->function_id = $validated['function_id'];
            $discount->discount_type = $validated['discount_type'];
            $discount->amount = $validated['amount'];
            $discount->qualification = $validated['qualification'];
            $discount->qualification_type = $validated['qualification_type'];
            $discount->auto_apply = $validated['auto_apply'];
            $discount->item_level = $validated['item_level'];
            $discount->required_product_count = $validated['required_product_count'];
            $discount->minimum_amount = $validated['minimum_amount'];
            try {
                DB::transaction(function () use ($discount, $request) {
                    $discount->save();
                    if(isset($request['dates'])){
                        $newDated = $request['dates'];
                        $dated = DiscountTime::find($newDated['id']);
                        $dated['from_date'] = $newDated['from_date'];
                        $dated['to_date'] = $newDated['to_date'];
                        $dated->save();
                        if(isset($newDated['times'])){
                            foreach ($newDated['times'] as $newTime) {
                                $timed = DiscountTimeDetail::find($newTime['id']);
                                $timed['from_time'] = $newTime['from_time'];
                                $timed['to_time'] = $newTime['to_time'];
                                $timed['active'] = $newTime['active'];
                                $timed->save();
                            }
                        }
                    }
                    if(isset($request['items'])){
                        DiscountItem::where('discount_id', '=', $discount->id)->delete();
                        foreach ($request['items'] as $newItem) {
                            if(isset($newItem)){
                                $item = new DiscountItem();
                                $item->item_id = $newItem['item_id'];
                                $item->discount_id = $discount->id;
                                $prod = $item->save();   
                            }
                        }
                    }
                });
            } catch (QueryException $e) {
               return response()->json(["message" => "ERROR_SAVING"]);
            }
        }
        return response()->json(["message" => "Done"]);
    }

    public function edit($id)
    {
        $discount  = Discount::find($id);
        $discount->items = $discount->items;
        $discount->dates = $discount->dates;
        foreach ($discount->dates as $d) {
            $d->times = $d->times;
        }
        return view('product::discount.edit', compact('discount'));
    }

    public function create()
    {
        $discount  = new Discount();
        $discount->function_id = 0;
        $discount->discount_type = 0;
        $discount->qualification = 0;
        $discount->auto_apply = 0;
        $discount->item_level = 0;
        return view('product::discount.create', compact('discount'));
    }

}
?>