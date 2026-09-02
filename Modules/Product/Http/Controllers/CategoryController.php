<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Modules\Product\Models\Category;
use Modules\Product\Models\Subcategory;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Support\AuthorizesProductPages;
use Modules\Product\Support\ProductAccess;

class CategoryController extends Controller implements HasMiddleware
{
    use AuthorizesProductPages;

    protected static function productAuthEntity(): string
    {
        return 'category';
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $product_permission = auth()->user()->franchise?->product_permission ?? 'absolute';

        return view('product::category.index', compact('product_permission'));
    }

    public function getminicategorylist()
    {
        $categories = Category::query()
            ->orderBy('order')
            ->get()
            ->map(static function (Category $category) {
                $attrs = $category->getAttributes();

                return [
                    'id' => $category->id,
                    'name_ar' => (string) ($attrs['name_ar'] ?? ''),
                    'name_en' => (string) ($attrs['name_en'] ?? ''),
                    'parent_id' => $category->parent_id,
                    'active' => (int) ($attrs['active'] ?? 0),
                    'order' => (int) ($attrs['order'] ?? 0),
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at,
                    'deleted_at' => $category->deleted_at,
                ];
            })
            ->values()
            ->all();

        return response()->json($categories, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function getminisubcategorylist($id)
    {
        $category = Category::find($id);

        if ($category === null) {
            return response()->json([], 200, [], JSON_UNESCAPED_UNICODE);
        }

        $rows = $category->subcategories->map(static function (Subcategory $sub) {
            $attrs = $sub->getAttributes();

            return [
                'id' => $sub->id,
                'name_ar' => (string) ($attrs['name_ar'] ?? ''),
                'name_en' => (string) ($attrs['name_en'] ?? ''),
                'category_id' => $sub->category_id,
                'parent_id' => $sub->parent_id,
                'active' => (int) ($attrs['active'] ?? 0),
                'order' => (int) ($attrs['order'] ?? 0),
                'created_at' => $sub->created_at,
                'updated_at' => $sub->updated_at,
                'deleted_at' => $sub->deleted_at,
            ];
        })->values()->all();

        return response()->json($rows, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function getCategories()
    {
        $TreeBuilder = new TreeBuilder;
        $categories = Category::all();
        $categories = Category::restrictByFranchise()
            ->get();
        $tree = $TreeBuilder->buildTrees($categories, null, 'category', null, null, null);

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
        ProductAccess::authorizeMutation($request, 'category');
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'order' => 'nullable|numeric',
            'active' => 'nullable|boolean',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string',
        ]);

        if (isset($validated['method']) && ($validated['method'] == 'delete')) {
            $subCategory = Subcategory::where([['category_id', '=', $validated['id']]])->first();
            if ($subCategory != null) {
                return response()->json(['message' => 'CHILD_EXIST']);
            }

            $category = Category::find($validated['id']);
            $category->delete();

            return response()->json(['message' => 'Done']);
        }

        if (! isset($validated['id'])) {
            if (isset($validated['order'])) {
                $category = Category::where('order', $validated['order'])->first();
                if ($category != null) {
                    return response()->json(['message' => 'ORDER_EXIST']);
                }
            }
            $category = Category::where('name_ar', $validated['name_ar'])->first();
            if ($category != null) {
                return response()->json(['message' => 'NAME_AR_EXIST']);
            }
            $category = Category::where('name_en', $validated['name_en'])->first();
            if ($category != null) {
                return response()->json(['message' => 'NAME_EN_EXIST']);
            }
            $category = Category::create($validated);
        } else {
            if (isset($validated['order'])) {
                $categories = Category::where('order', $validated['order'])->where('id', '!=', $validated['id'])->first();
                if ($categories != null) {
                    return response()->json(['message' => 'ORDER_EXIST']);
                }
            }
            $categories = Category::where('name_ar', $validated['name_ar'])->where('id', '!=', $validated['id'])->first();
            if ($categories != null) {
                return response()->json(['message' => 'NAME_AR_EXIST']);
            }
            $categories = Category::where('name_en', $validated['name_en'])->where('id', '!=', $validated['id'])->first();
            if ($categories != null) {
                return response()->json(['message' => 'NAME_EN_EXIST']);
            }

            $category = Category::find($validated['id']);
            $category->name_ar = $validated['name_ar'];
            $category->name_en = $validated['name_en'];
            $category->order = $validated['order'];
            $category->active = $validated['active'];
            $category->order = $validated['order'] ?? null;
            $category->save();
        }

        return response()->json(['message' => 'Done']);
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
