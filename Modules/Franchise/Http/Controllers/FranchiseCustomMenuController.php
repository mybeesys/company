<?php

namespace Modules\Franchise\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Franchise\Models\FranchiseCompanies;
use Modules\Product\Models\CustomMenu;

class FranchiseCustomMenuController extends Controller
{
    public function index()
    {
        $franchises = FranchiseCompanies::all();
        return view('franchise::custom_menus.index', compact('franchises'));
    }

   
    public function getFranchiseData($id)
    {
        $allowedProductIds = DB::table('franchise_product_permissions')
            ->where('franchise_id', $id)
            ->where('permitted_type', 'product')
            ->pluck('permitted_id')->toArray();

        $allowedMenuIds = DB::table('franchise_custom_menu_permissions')
            ->where('franchise_id', $id)
            ->pluck('custom_menu_id')->toArray();

        $allMenus = CustomMenu::with(['products.product'])->get();

        $data = $allMenus->map(function ($menu) use ($allowedProductIds, $allowedMenuIds) {
            $missingProducts = [];
            $missingIds = [];

            foreach ($menu->products as $item) {
                if (!in_array($item->product_id, $allowedProductIds)) {
                    $missingIds[] = $item->product_id;
                    $missingProducts[] = app()->getLocale() == 'ar'
                        ? ($item->product->name_ar ?? 'منتج غير معروف')
                        : ($item->product->name_en ?? 'Unknown Product');
                }
            }

            return [
                'id' => $menu->id,
                'name' => app()->getLocale() == 'ar' ? $menu->name_ar : $menu->name_en,
                'is_active' => in_array($menu->id, $allowedMenuIds),
                'total_items' => $menu->products->count(),
                'missing_items_names' => $missingProducts,
                'missing_items_ids' => $missingIds,
                'missing_count' => count($missingProducts),
                'has_warning' => count($missingProducts) > 0,
            ];
        });

        return response()->json($data);
    }

    public function update(Request $request)
    {
        $franchiseId = $request->franchise_id;
        $menuIds = $request->menu_ids ?? [];
        $productsToGrant = $request->products_to_grant ?? [];

        DB::transaction(function () use ($franchiseId, $menuIds, $productsToGrant) {
            DB::table('franchise_custom_menu_permissions')->where('franchise_id', $franchiseId)->delete();
            $insertMenus = [];
            foreach ($menuIds as $menuId) {
                $insertMenus[] = [
                    'franchise_id' => $franchiseId,
                    'custom_menu_id' => $menuId,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            if (!empty($insertMenus)) DB::table('franchise_custom_menu_permissions')->insert($insertMenus);

            if (!empty($productsToGrant)) {
                foreach ($productsToGrant as $pId) {
                    DB::table('franchise_product_permissions')->updateOrInsert(
                        ['franchise_id' => $franchiseId, 'permitted_id' => $pId, 'permitted_type' => 'product'],
                        ['updated_at' => now(), 'created_at' => now()]
                    );
                }
            }
        });

        return response()->json(['message' => app()->getLocale() == 'ar' ? 'تم تحديث القوائم ومنح صلاحيات المنتجات المرتبطة تلقائياً' : 'Menus updated and product permissions granted automatically']);
    }
}
