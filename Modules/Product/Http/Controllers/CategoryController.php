<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\TreeCategoryBuilder;
use Modules\Product\Models\Category;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::category.index' ); 
    }
    

    public function getCategories()
    {
        $TreeCategoryBuilder = new TreeCategoryBuilder();
        $tree = $TreeCategoryBuilder->buildCategoryTree();
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
            'order' => 'required|numeric',
            'active' => 'nullable|boolean',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);
  
        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $category = Category::find($validated['id']); 
            if(count($category->subcategories)==0)
            {
               $category->delete();
               return response()->json(["message"=>"Done"]);
            }
            else
            {
                return response()->json(["message"=>"Please first delete subcategories of this category"]);
            }

        }

        if(!isset($validated['id']))
        {
            $categories = Category::where('order', $validated['order'])->first();

            if($categories == null)
               $category = Category::create($validated);
            else
               return response()->json(["message"=>"The order is already exist!"]);
            
        }
         else
         {
            $categories = Category::where('order', $validated['order'])->where('id', '!=', $validated['id'])->first();
            if($categories == null)
            {
                $category = Category::find($validated['id']);
                $category->name_ar = $validated['name_ar'];
                $category->name_en = $validated['name_en'];
                $category->order = $validated['order'];
                $category->active = $validated['active'];
                $category->save();
            }
            else
              return response()->json(["message"=>"The order is already exist!"]);
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
