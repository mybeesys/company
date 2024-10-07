<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\TreeCategoryBuilder;
use Modules\Product\Models\Subcategory;

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
            $subcategory = SubCategory::find($validated['id']); 
            if(count($subcategory->children)==0 && count($subcategory->products)==0)
            {
               $subcategory->delete();
               return response()->json(["message"=>"Done"]);
            }
            else
            {
                return response()->json(["message"=>"Please first delete subcategories of this category"]);
            }

        }


        if(!isset($validated['id']))
        {
          $subcategories = SubCategory::where([['category_id', '=', $validated['category_id']],
          ['parent_id', '=', $validated['parent_id']] ,
          ['order', '=', $validated['order']]])->first();

          if($subcategories == null)
             $subcategory = SubCategory::create($validated);
          else
             return response()->json(["message"=>"The order is already exist!"]);
        }
        else
        {
            $subcategories = SubCategory::where([['category_id', '=', $validated['category_id']],
            ['parent_id', '=', $validated['parent_id']] ,
            ['order', '=', $validated['order']],
            ['id', '!=', $validated['id']]])->first();

           if($subcategories == null)
           {
                $subcategory = SubCategory::find($validated['id']);
                $subcategory->name_ar = $validated['name_ar'];
                $subcategory->name_en = $validated['name_en'];
                $subcategory->order = $validated['order'];
                $subcategory->active = $validated['active'];
                $subcategory->save();
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
