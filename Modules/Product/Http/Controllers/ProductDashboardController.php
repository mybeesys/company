<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Enums\ServiceFeeType;
use Modules\Product\Models\Attribute;
use Modules\Product\Models\Category;
use Modules\Product\Models\CustomMenu;
use Modules\Product\Models\Discount;
use Modules\Product\Models\PriceTier;
use Modules\Product\Models\Product;
use Modules\Product\Models\ServiceFee;
use Modules\Product\Models\TypesOfService;

class ProductDashboardController extends Controller
{
    public function index()
    {
        $productsCount = Product::where('type', 'product')->count();
        $ingredintsCount = Product::where('type', 'ingredint')->count();
        $servicesCount = CustomMenu::count();

        $modifiersCount = Attribute::count();
        $variantsCount = Product::where('type', 'modifier')->count();
        $serviceFeesCount = ServiceFee::count();
        $serviceTypesCount = TypesOfService::count();
        $discountsCount = Discount::count();
        $pricingsCount = PriceTier::count();

        $productsMonthlyData = Product::selectRaw('COUNT(*) as count, MONTH(created_at) as month')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count')
            ->toArray();

        $servicesMonthlyData = CustomMenu::selectRaw('COUNT(*) as count, MONTH(created_at) as month')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count')
            ->toArray();

        $latestProducts = Product::where('type','product')->latest()->take(5)->get();
        $latestServices = CustomMenu::latest()->take(5)->get();

        return view('product::dashboard.product-dashboard', compact(
            'productsCount',
            'ingredintsCount',
            'servicesCount',
            'modifiersCount',
            'variantsCount',
            'serviceFeesCount',
            'serviceTypesCount',
            'discountsCount',
            'pricingsCount',
            'productsMonthlyData',
            'servicesMonthlyData',
            'latestProducts',
            'latestServices'
        ));
    }
}
