<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Models\ProductInventory;
use Modules\Product\Models\Product;

use function Laravel\Prompts\error;
use DB;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Transaction;

class InventoryDashboardController extends Controller
{
    public function index()
    {
        $warehousesCount = Establishment::where("is_main", 0)->count();

        $transferCount = Transaction::with(['establishment', 'items.product'])
            ->where('type', 'TRANSFER')
            ->where('status', 'approved')
            ->where('parent_id', null)
            ->distinct()
            ->count();
        $wasteCount = Transaction::with(['establishment', 'items.product'])
            ->where('type', 'WASTE')
            ->where('status', 'approved')
            ->distinct()
            ->count();
        $prepCount = Transaction::with(['establishment', 'items.product'])
            ->where('type', 'PREP')
            ->where('status', 'approved')
            ->where('parent_id', null)
            ->distinct()
            ->count();
        $warehouses = Establishment::where('is_main', 0)->get();

        foreach ($warehouses as $warehouse) {
            $mostStockedProductData = DB::table('product_inventories')
                ->where('establishment_id', $warehouse->id)
                ->orderBy('qty', 'desc')
                ->first();

            $leastStockedProductData = DB::table('product_inventories')
                ->where('establishment_id', $warehouse->id)
                ->orderBy('qty', 'asc')
                ->first();

            $warehouse->mostStockedProduct = $mostStockedProductData ? Product::find($mostStockedProductData->product_id) : null;
            $warehouse->mostStockedQuantity = $mostStockedProductData ? $mostStockedProductData->qty : 0;

            $warehouse->leastStockedProduct = $leastStockedProductData ? Product::find($leastStockedProductData->product_id) : null;
            $warehouse->leastStockedQuantity = $leastStockedProductData ? $leastStockedProductData->qty : 0;
        }

        return view(
            'inventory::dashboard.dashboard',
            compact(
                'warehousesCount',
                'warehouses',
                'transferCount',
                'wasteCount',
                'prepCount'
            )
        );
    }
}
