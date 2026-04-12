<?php

namespace Modules\Franchise\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Product\Models\Category;
use Modules\Franchise\Models\FranchiseCompanies;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\AttributeClass;
use Modules\Product\Models\ModifierClass;
use Modules\Product\Models\Product;

class FranchiseProductsController extends Controller
{

    // public function index()
    // {
    //     $franchises = FranchiseCompanies::all();

    //     $categories = Category::with(['subcategories' => function ($q) {
    //         $q->where('active', 1)->with(['products' => function ($p) {
    //             $p->whereIn('type', ['product', 'variable'])->where('active', 1);
    //         }]);
    //     }])->where('active', 1)->get();

    //     $ingredients = Product::where('type', 'ingredint')->where('active', 1)->get();

    //     $modifierClasses = ModifierClass::with(['children' => function ($q) {
    //         $q->where('active', 1);
    //     }])->where('active', 1)->get();

    //      $attributeClasses = AttributeClass::with(['children' => function ($q) {
    //         $q->where('active', 1);
    //     }])->where('active', 1)->get();

    //     return view('franchise::products.manage', compact('franchises', 'categories', 'ingredients', 'modifierClasses', 'attributeClasses'));
    // }

    public function index()
    {
        $franchises = FranchiseCompanies::all();

        $categories = Category::with(['subcategories' => function ($q) {
            $q->where('active', 1)->with(['products' => function ($p) {
                $p->whereIn('type', ['product', 'variable'])
                    ->where('active', 1)
                    ->whereNull('franchise_id'); // أضف هذا السطر: المانح يمنح المنتجات الأساسية فقط
            }]);
        }])->where('active', 1)->get();

        // المكونات الأساسية فقط
        $ingredients = Product::where('type', 'ingredint')
            ->where('active', 1)
            ->whereNull('franchise_id') // أضف هذا السطر
            ->get();

        $modifierClasses = ModifierClass::with(['children' => function ($q) {
            $q->where('active', 1);
        }])->where('active', 1)->get();

        $attributeClasses = AttributeClass::with(['children' => function ($q) {
            $q->where('active', 1);
        }])->where('active', 1)->get();

        return view('franchise::products.manage', compact('franchises', 'categories', 'ingredients', 'modifierClasses', 'attributeClasses'));
    }


    public function getFranchiseProducts($id)
    {
        $permissions = DB::table('franchise_product_permissions')
            ->where('franchise_id', $id)
            ->get();

        $data = [
            'product'    => $permissions->where('permitted_type', 'product')->pluck('permitted_id')->toArray(),
            'ingredient' => $permissions->where('permitted_type', 'ingredient')->pluck('permitted_id')->toArray(),
            'modifier'   => $permissions->where('permitted_type', 'modifier')->pluck('permitted_id')->toArray(),
            'attribute'  => $permissions->where('permitted_type', 'attribute')->pluck('permitted_id')->toArray(), // إضافة الـ attribute
        ];

        return response()->json($data);
    }

    public function getPermissions($id)
    {
        $permissions = DB::table('franchise_product_permissions')
            ->where('franchise_id', $id)
            ->get()
            ->groupBy('permitted_type')
            ->map(function ($items) {
                return $items->pluck('permitted_id');
            });

        return response()->json($permissions);
    }

    public function update(Request $request)
    {
        DB::transaction(function () use ($request) {
            // DB::table('franchise_product_permissions')->where('franchise_id', $request->franchise_id)->delete();

            DB::table('franchise_product_permissions')->where('franchise_id', $request->franchise_id)->delete();
            if ($request->has('permissions')) {
                $insertData = [];
                foreach ($request->permissions as $p) {
                    $insertData[] = [
                        'franchise_id' => $request->franchise_id,
                        'permitted_id' => $p['id'],
                        'permitted_type' => $p['type'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                DB::table('franchise_product_permissions')->insert($insertData);
            }
        });
        return response()->json(['message' => 'تم حفظ جميع الصلاحيات بنجاح']);
    }

    public function getPendingProducts($id)
    {
        $products = Product::with('category')
            ->where('franchise_id', $id)
            ->where('status', 'pending')
            ->select('id', 'name_ar', 'name_en', 'price', 'image', 'SKU', 'category_id')
            ->get();

        return response()->json($products);
    }

    public function handleApprovalAction(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:product_products,id',
            'status' => 'required|in:approve,reject',
            'reason' => 'nullable|string'
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($request->status == 'approve') {
            $product->update([
                'status' => 'approved',
                'active' => 1,
                'rejection_reason' => null
            ]);
            return response()->json(['success' => true, 'message' => 'تم قبول المنتج وتفعيله بنجاح']);
        } else {
            $product->update([
                'status' => 'rejected',
                'active' => 0,
                'rejection_reason' => $request->reason
            ]);
            return response()->json(['success' => true, 'message' => 'تم رفض المنتج وإبلاغ الفرع']);
        }
    }
}
