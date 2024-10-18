<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\TreeCategoryBuilder;
use Modules\Product\Models\Subcategory;
use Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Builder;;

class SubCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    
    }
    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product::create');
    }

    private function findSubCategory($validated, $field){
        $whereCond = null;
        if(!isset($validated['id'])){
            $whereCond = [
                    ['category_id', '=', $validated['category_id']],
                    [$field, '=', $validated[$field]]
                ];
        }
        else{
            $whereCond = [
                    ['id', '!=', $validated['id']],
                    ['category_id', '=', $validated['category_id']],
                    [$field, '=', $validated[$field]]
                ];
        }
        $parentId = $validated['parent_id'];
        if(!isset($parentId)){
            $subcategory = SubCategory::where($whereCond)
            ->when($parentId, function (Builder $query, string $parentId) {
                $query->WhereNull('parent_id');
            })->first();
        }
        else{
            $subcategory = SubCategory::where($whereCond)
            ->when($parentId, function (Builder $query, string $parentId) {
                $query->Where('parent_id', '=', $parentId);
            })->first();
        }
        return $subcategory;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'order' => 'required|numeric',
            'category_id' => 'required|numeric',
            'parent_id' => 'nullable|numeric',
            'active' => 'nullable|boolean',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);


        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            
            $product = Product::where([['subcategory_id', '=', $validated['id']]])->first();
            if($product != null)
                return response()->json(["message"=>"CHILD_EXIST"]);
            
            $subCategory = SubCategory::where([['id', '=', $validated['id']]])->first();
            $subCategory->delete();
            return response()->json(["message"=>"Done"]);
        }


        if(!isset($validated['id']))
        {
            $subcategory = $this->findSubCategory($validated, 'order');
            if($subcategory != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $subcategory = $this->findSubCategory($validated, 'name_ar');
            if($subcategory != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $subcategory = $this->findSubCategory($validated, 'name_en');
            if($subcategory != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

             $subcategory = SubCategory::create($validated);
        }
        else
        {
            $subcategory = $this->findSubCategory($validated, 'order');
            if($subcategory != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $subcategory = $this->findSubCategory($validated, 'name_ar');
            if($subcategory != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $subcategory = $this->findSubCategory($validated, 'name_en');
            if($subcategory != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

            $subcategory = SubCategory::find($validated['id']);
            $subcategory->name_ar = $validated['name_ar'];
            $subcategory->name_en = $validated['name_en'];
            $subcategory->order = $validated['order'];
            $subcategory->active = $validated['active'];
            $subcategory->save();
        }

        return response()->json(["message"=>"Done"]);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('product::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('product::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
