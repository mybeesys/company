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

        $allMenus = CustomMenu::with(['products.product' => function ($q) {
            $q->select('id', 'name_ar', 'name_en');
        }])->get();

        $data = $allMenus->map(function ($menu) use ($allowedProductIds, $allowedMenuIds) {
            $missingProducts = [];
            $totalItems = $menu->products->count();

            foreach ($menu->products as $item) {
                if (!in_array($item->product_id, $allowedProductIds)) {
                    $missingProducts[] = app()->getLocale() == 'ar'
                        ? ($item->product->name_ar ?? 'منتج غير معروف')
                        : ($item->product->name_en ?? 'Unknown Product');
                }
            }

            return [
                'id' => $menu->id,
                'name' => app()->getLocale() == 'ar' ? $menu->name_ar : $menu->name_en,
                'is_active' => in_array($menu->id, $allowedMenuIds),
                'total_items' => $totalItems,
                'missing_items_names' => $missingProducts,
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

        DB::transaction(function () use ($franchiseId, $menuIds) {
            DB::table('franchise_custom_menu_permissions')->where('franchise_id', $franchiseId)->delete();

            $insertData = [];
            foreach ($menuIds as $menuId) {
                $insertData[] = [
                    'franchise_id' => $franchiseId,
                    'custom_menu_id' => $menuId,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            if (!empty($insertData)) {
                DB::table('franchise_custom_menu_permissions')->insert($insertData);
            }
        });

        return response()->json(['message' => 'تم تحديث صلاحيات القوائم بنجاح']);
    }
}
