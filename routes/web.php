<?php

use App\Http\Middleware\LocalizationMiddleware;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/set-locale/{locale}', function ($locale) {
        session()->put('locale', $locale);
        app()->setLocale($locale);
        return redirect()->back();
    })->withoutMiddleware(LocalizationMiddleware::class)->name('set_locale');

});
