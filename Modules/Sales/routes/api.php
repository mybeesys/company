<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Models\Product;
use Modules\Sales\Http\Controllers\SellApiController;
use Modules\Sales\Http\Controllers\SellReturnApiController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;



Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    // 'auth-central',
])->group(function () {
    Route::get('sales-invoices', [SellApiController::class, 'index'])->name('sales-invoices');
    Route::post('stor-sales-invoice', [SellApiController::class, 'store'])->name('stor-sales-invoice');



    Route::post('stor-sell-return', [SellReturnApiController::class, 'store'])->name('stor-sell-return');




        Route::get('products-for-sale', function () {
            $products = Product::where([['active', '=', 1], ['for_sell', '=', 1]])
                ->whereIn('type', ['product', 'variation'])
                ->with(['unitTransfers' => function ($query) {
                    $query->whereNull('unit2');
                }])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        });
});
