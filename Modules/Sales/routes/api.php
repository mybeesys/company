<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\Api\CouponApiController;
use Modules\Sales\Http\Controllers\SellApiController;
use Modules\Sales\Http\Controllers\SellReturnApiController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('sales-invoices', [SellApiController::class, 'index'])->name('sales-invoices');
    Route::post('stor-sales-invoice', [SellApiController::class, 'store'])->name('stor-sales-invoice');

    Route::post('stor-sell-return', [SellReturnApiController::class, 'store'])->name('stor-sell-return');

    Route::middleware(['auth-central'])->prefix('v1/coupons')->name('v1.coupons.')->group(function () {
        Route::get('settings', [CouponApiController::class, 'settings'])->name('settings');
        Route::post('validate', [CouponApiController::class, 'validateCoupon'])->name('validate');
        Route::get('by-code/{code}', [CouponApiController::class, 'showByCode'])->name('by-code');
        Route::get('{coupon}', [CouponApiController::class, 'show'])->whereNumber('coupon')->name('show');
        Route::get('/', [CouponApiController::class, 'index'])->name('index');
    });
});
