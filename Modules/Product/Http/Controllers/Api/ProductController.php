<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\Attribute;
use Modules\Product\Models\Category;
use Modules\Product\Models\ModifierClass;
use Modules\Product\Models\Product;
use Modules\Product\Models\Transformers\Collections\ModifierClassCollection;
use Modules\Product\Models\Transformers\Collections\PAttributeCollection;
use Modules\Product\Models\Transformers\Collections\ProductCollection;

class ProductController extends Controller
{
    public function products(Request $request)
    {
        // $request->validate([
        //     'establishment_id' => ['required', 'numeric']
        // ]);
        $establishment_id = $request->query('establishment_id', '');
        // $products = Product::whereHas('establishments', function ($query)use($establishment_id) {
        //     $query->where('establishment_id', $establishment_id); // Example condition on EntityTwo
        // })
        $products = Product::where([['active', '=', 1], ['for_sell', '=', 1]])
            ->with(['modifiers' => function ($query) {
                $query->with(['modifiers' => function ($query) {
                    $query->with('children');
                }]);
            }])->with(['attributes' => function ($query) {
                $query->with('attribute1');
                $query->with('attribute2');
            }])->with(['combos' => function ($query) {
                $query->with(['items' => function ($query) {
                    $query->with('product');
                }]);
            }])->with(['unitTransfers' => function ($query) {
                $query->whereNull('unit2');
            }])->with('category')->with('subcategory')->with('tax')
            ->leftJoin('product_inventories', function ($join) use ($establishment_id) {
                $join->on('product_inventories.product_id', '=', 'product_products.id')
                    ->where('establishment_id', '=', $establishment_id); // Constant condition
            })->get();
        return new ProductCollection($products);
    }

    public function product($id)
    {

        $products = Product::where('id', $id)->with(['modifiers' => function ($query) {
            $query->where('active', 1);
            $query->with(['modifiers' => function ($query) {
                $query->with(['children' => function ($query) {
                    $query->where('active', 1);
                    $query->with('tax');
                }]);
            }]);
        }])->with(['attributes' => function ($query) {
            $query->with('attribute1');
            $query->with('attribute2');
        }])->with(['combos' => function ($query) {
            $query->with(['items' => function ($query) {
                $query->with('product');
            }]);
        }])->with(['unitTransfers' => function ($query) {
            $query->whereNull('unit2');
        }])->with('category')->with('subcategory')->with('tax')->get();
        return new ProductCollection($products);
    }

    public function categories(Request $request)
    {
        $categories = Category::all();
        $mappedCategories = array_map(function ($item) {
            $newItem["id"] = $item["id"];
            $newItem["parent_id"] = null;
            $newItem["name_ar"] = $item["name_en"];
            $newItem["name_en"] = $item["name_en"];
            $newItem["type"] = "category";
            return $newItem;
        }, $categories->toArray());
        $subCategories = Category::where('level', '>', 1)->whereNotNull('parent_id')->get();
        // $subCategories = Subcategory::all();
        $mappedSubcategories = array_map(function ($item) {
            $newItem["id"] = $item["id"];
            $newItem["parent_id"] = $item["category_id"];
            $newItem["name_ar"] = $item["name_en"];
            $newItem["name_en"] = $item["name_en"];
            $newItem["type"] = "subcategory"; // Add an extra field
            return $newItem;
        }, $subCategories->toArray());
        $result = array_merge($mappedCategories, $mappedSubcategories);
        return response()->json($result);
    }

    public function modifiers(Request $request)
    {
        $modifiers = ModifierClass::with(['products' => function ($query) {
            $query->with('products');
        }])->get();
        return new ModifierClassCollection($modifiers);
    }

    public function attributes(Request $request)
    {
        $modifiers = Attribute::with(['products' => function ($query) {
            $query->with('product');
        }])->get();
        return new PAttributeCollection($modifiers);
    }
}
