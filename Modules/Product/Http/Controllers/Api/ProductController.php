<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Transformers\Collections\CategoryCollection;
use Modules\Product\Models\Transformers\Collections\ProductCollection;
use Modules\Product\Models\TreeBuilder;

class ProductController extends Controller
{
    public function products(Request $request)
    {
        $products = Product::with(['modifiers' => function ($query) {
            $query->with(['modifiers' => function ($query) {
                $query->with('children');
            }]);
        }])->get();
        return new ProductCollection($products);
    }

    public function product($id)
    {
        
        $products = Product::where('id', $id)->with(['modifiers' => function ($query) {
            $query->with(['modifiers' => function ($query) {
                $query->with('children');
            }]);
        }])->get();
        return new ProductCollection($products);
    }

    public function categories(Request $request)
    {
        $TreeBuilder = new TreeBuilder();
        $categories = Category::all();
        $tree = $TreeBuilder->buildTree($categories ,null, 'category', null, null, null);
        return new CategoryCollection($tree);
    }
}