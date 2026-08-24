<?php

use Illuminate\Support\Facades\Route;
use Modules\Zatca\Http\Controllers\ZatcaSettingController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::middleware(['auth'])->group(function () {
        Route::get('zatca-settings', [ZatcaSettingController::class, 'edit'])->name('zatca.settings.edit');
        Route::put('zatca-settings', [ZatcaSettingController::class, 'update'])->name('zatca.settings.update');
        Route::post('zatca-settings/regenerate', [ZatcaSettingController::class, 'regenerate'])->name('zatca.settings.regenerate');
        Route::post('zatca-settings/sync-sell', [ZatcaSettingController::class, 'syncSell'])->name('zatca.settings.sync-sell');
    });
});
