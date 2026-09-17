<?php

namespace Modules\Product\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Modules\Product\Models\CustomMenu;
use Modules\Product\Models\Product;

/**
 * Catalog quality and growth signals shared with the executive dashboard.
 * Does not mutate product records; queries match /product-dashboard.
 */
class ProductCatalogHealthService
{
    public const LIST_LIMIT = 8;

    /**
     * @return array<string, mixed>
     */
    public function snapshot(int $listLimit = self::LIST_LIMIT): array
    {
        $months = collect(range(5, 0))->map(fn (int $i) => Carbon::now()->subMonths($i)->format('Y-m'))->values();
        $since = Carbon::now()->subMonths(5)->startOfMonth();

        $productsMonthlyRaw = Product::query()
            ->where('type', 'product')
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COUNT(*) as count")
            ->groupBy('month_key')
            ->pluck('count', 'month_key');

        $menusMonthlyRaw = CustomMenu::query()
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COUNT(*) as count")
            ->groupBy('month_key')
            ->pluck('count', 'month_key');

        $currentMonth = (int) $this->sellableProducts()
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();
        $previousMonth = (int) $this->sellableProducts()
            ->whereBetween('created_at', [
                Carbon::now()->subMonthNoOverflow()->startOfMonth(),
                Carbon::now()->subMonthNoOverflow()->endOfMonth(),
            ])
            ->count();

        $zeroQuery = $this->zeroPriceQuery();
        $lossQuery = $this->negativeMarginQuery();

        return [
            'products_count' => (int) $this->sellableProducts()->count(),
            'menus_count' => (int) CustomMenu::query()->count(),
            'zero_price_count' => (int) (clone $zeroQuery)->count(),
            'zero_price_products' => (clone $zeroQuery)
                ->latest('created_at')
                ->take($listLimit)
                ->get(['id', 'name_ar', 'name_en', 'price_with_tax', 'created_at']),
            'negative_margin_count' => (int) (clone $lossQuery)->count(),
            'negative_margin_products' => (clone $lossQuery)
                ->select('id', 'name_ar', 'name_en', 'cost', 'price_with_tax', 'created_at')
                ->latest('created_at')
                ->take($listLimit)
                ->get(),
            'current_month_added' => $currentMonth,
            'previous_month_added' => $previousMonth,
            'growth_percent' => $previousMonth > 0
                ? round((($currentMonth - $previousMonth) / $previousMonth) * 100, 2)
                : ($currentMonth > 0 ? 100.0 : 0.0),
            'months' => $months->all(),
            'products_monthly' => $months->map(fn ($month) => (int) ($productsMonthlyRaw[$month] ?? 0))->all(),
            'menus_monthly' => $months->map(fn ($month) => (int) ($menusMonthlyRaw[$month] ?? 0))->all(),
        ];
    }

    public function sellableProducts(): Builder
    {
        return Product::query()->where('type', 'product');
    }

    public function zeroPriceQuery(): Builder
    {
        return $this->sellableProducts()->where(function (Builder $query) {
            $query->whereNull('price_with_tax')->orWhere('price_with_tax', '<=', 0);
        });
    }

    public function negativeMarginQuery(): Builder
    {
        return $this->sellableProducts()
            ->whereNotNull('cost')
            ->whereNotNull('price_with_tax')
            ->whereColumn('cost', '>', 'price_with_tax');
    }
}
