<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Category;
use Modules\Product\Models\Subcategory;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::category.index' ); 
    }
    
    public function getminicategorylist()
    {
       $categories = Category::all();
       return response()->json($categories);
    }

    public function getminisubcategorylist($id)
    {
       $subcategories = Category::find($id);
       return response()->json($subcategories->subcategories);
    }

    public function getCategories()
    {
        $TreeBuilder = new TreeBuilder();
        $categories = Category::all();
        $tree = $TreeBuilder->buildTree($categories ,null, 'category', null, null, null);
        return response()->json($tree);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'order' => 'nullable|numeric',
            'active' => 'nullable|boolean',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);
  
        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $subCategory = Subcategory::where([['category_id', '=', $validated['id']]])->first();
            if($subCategory != null)
                return response()->json(["message"=>"CHILD_EXIST"]);

            $category = Category::find($validated['id']);
            $category->delete();
            return response()->json(["message"=>"Done"]);
        }

        if(!isset($validated['id']))
        {
            $category = Category::where('order', $validated['order'])->first();
            if($category != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $category = Category::where('name_ar', $validated['name_ar'])->first();
            if($category != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $category = Category::where('name_en', $validated['name_en'])->first();
            if($category != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            $category = Category::create($validated);
        }
         else
         {
            $categories = Category::where('order', $validated['order'])->where('id', '!=', $validated['id'])->first();
            if($categories != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $categories = Category::where('name_ar', $validated['name_ar'])->where('id', '!=', $validated['id'])->first();
            if($categories != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $categories = Category::where('name_en', $validated['name_en'])->where('id', '!=', $validated['id'])->first();
            if($categories != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

            $category = Category::find($validated['id']);
            $category->name_ar = $validated['name_ar'];
            $category->name_en = $validated['name_en'];
            $category->order = $validated['order'];
            $category->active = $validated['active'];
            $category->order = $validated['order'] ?? null;
            $category->save();
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
