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
        $locale = in_array($locale, ['ar', 'en'], true) ? $locale : config('app.locale', 'ar');
        session()->put('locale', $locale);
        app()->setLocale($locale);

        $redirect = request()->query('redirect');
        if (is_string($redirect) && $redirect !== '') {
            $parts = parse_url($redirect);
            $host = $parts['host'] ?? null;
            $path = $parts['path'] ?? '/';
            $query = isset($parts['query']) ? '?'.$parts['query'] : '';
            // Same-host relative redirect only (safe against open redirects).
            if ($host === null || $host === request()->getHost()) {
                return redirect()->to($path.$query);
            }
        }

        return redirect()->to(url()->previous(url('/')));
    })->withoutMiddleware(LocalizationMiddleware::class)->name('set_locale');

});
