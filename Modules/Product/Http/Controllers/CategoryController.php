<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\Category;
use Modules\Product\Models\Subcategory;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        dd("hiiii");
      return view('product::category.index' ); 
    }

    public function getCategories()
    {
       $categories = Category::all(); // or use query with pagination if needed
       return response()->json([
        'data' => $categories
    ]);
    }

    
    public function getsubCategories($id)
    {
        // Find the category by its ID
        $category = Category::findOrFail($id);

        // Get all subcategories for this category
        $subcategories = $category->subcategories;

        return response()->json([
            'data' => $subcategories
        ]);
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
        //
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
