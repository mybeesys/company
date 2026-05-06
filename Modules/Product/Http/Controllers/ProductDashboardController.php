<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Models\Attribute;
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
        $months = collect(range(5, 0))
            ->map(fn ($offset) => now()->subMonths($offset)->format('Y-m'))
            ->push(now()->format('Y-m'))
            ->values();

        $productsCount = Product::where('type', 'product')->count();
        $ingredintsCount = Product::where('type', 'ingredint')->count();
        $servicesCount = CustomMenu::count();

        $modifiersCount = Attribute::count();
        $variantsCount = Product::where('type', 'modifier')->count();
        $serviceFeesCount = ServiceFee::count();
        $serviceTypesCount = TypesOfService::count();
        $discountsCount = Discount::count();
        $pricingsCount = PriceTier::count();

        $productsMonthlyRaw = Product::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COUNT(*) as count")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->pluck('count', 'month_key');

        $servicesMonthlyRaw = CustomMenu::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COUNT(*) as count")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->pluck('count', 'month_key');

        $productsMonthlyData = $months->map(fn ($month) => (int) ($productsMonthlyRaw[$month] ?? 0))->values()->all();
        $servicesMonthlyData = $months->map(fn ($month) => (int) ($servicesMonthlyRaw[$month] ?? 0))->values()->all();
        $monthLabels = $months->map(function ($month) {
            return \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y');
        })->values()->all();

        $latestProducts = Product::where('type', 'product')->latest()->take(5)->get();
        $latestServices = CustomMenu::latest()->take(5)->get();
        $last30DaysStart = now()->subDays(30)->startOfDay();
        $topProductsLastPeriod = Product::where('type', 'product')
            ->where('created_at', '>=', $last30DaysStart)
            ->latest('created_at')
            ->take(10)
            ->get();
        $zeroPriceProductsCount = Product::where('type', 'product')
            ->where(function ($query) {
                $query->whereNull('price_with_tax')->orWhere('price_with_tax', '<=', 0);
            })
            ->count();
        $zeroPriceProducts = Product::where('type', 'product')
            ->where(function ($query) {
                $query->whereNull('price_with_tax')->orWhere('price_with_tax', '<=', 0);
            })
            ->latest('created_at')
            ->take(5)
            ->get();
        $negativeMarginProductsCount = Product::where('type', 'product')
            ->whereNotNull('cost')
            ->whereNotNull('price_with_tax')
            ->whereColumn('cost', '>', 'price_with_tax')
            ->count();
        $negativeMarginProducts = Product::where('type', 'product')
            ->whereNotNull('cost')
            ->whereNotNull('price_with_tax')
            ->whereColumn('cost', '>', 'price_with_tax')
            ->select('id', 'name_ar', 'name_en', 'cost', 'price_with_tax', 'created_at')
            ->latest('created_at')
            ->take(10)
            ->get();

        $currentMonthProducts = Product::where('type', 'product')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $previousMonthProducts = Product::where('type', 'product')
            ->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])
            ->count();
        $productsGrowthPercent = $previousMonthProducts > 0
            ? round((($currentMonthProducts - $previousMonthProducts) / $previousMonthProducts) * 100, 2)
            : ($currentMonthProducts > 0 ? 100 : 0);

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
            'monthLabels',
            'latestProducts',
            'latestServices',
            'topProductsLastPeriod',
            'zeroPriceProductsCount',
            'zeroPriceProducts',
            'negativeMarginProductsCount',
            'negativeMarginProducts',
            'productsGrowthPercent',
            'currentMonthProducts',
            'previousMonthProducts'
        ));
    }

    public function exportLatestProductsCsv()
    {
        $latestProducts = Product::where('type', 'product')->latest()->take(5)->get();
        $rows = [['Arabic Name', 'English Name', 'Price With Tax', 'Created At']];
        foreach ($latestProducts as $product) {
            $rows[] = [
                $product->name_ar ?? '',
                $product->name_en ?? '',
                (string) ($product->price_with_tax ?? 0),
                optional($product->created_at)->format('Y-m-d H:i:s'),
            ];
        }

        return $this->csvDownloadResponse($rows, 'latest-products.csv');
    }

    public function exportLatestMenusCsv()
    {
        $latestServices = CustomMenu::latest()->take(5)->get();
        $rows = [['Name AR', 'Name EN', 'Created At']];
        foreach ($latestServices as $service) {
            $rows[] = [
                $service->name_ar ?? '',
                $service->name_en ?? '',
                optional($service->created_at)->format('Y-m-d H:i:s'),
            ];
        }

        return $this->csvDownloadResponse($rows, 'latest-custom-menus.csv');
    }

    private function csvDownloadResponse(array $rows, string $filename)
    {
        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
