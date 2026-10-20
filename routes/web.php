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
        session()->save();
        app()->setLocale($locale);

        $target = url('/');
        $redirect = request()->query('redirect');
        if (is_string($redirect) && $redirect !== '') {
            $parts = parse_url($redirect);
            $host = $parts['host'] ?? null;
            // Same-host only (safe against open redirects).
            if ($host === null || $host === request()->getHost()) {
                $path = $parts['path'] ?? '/';
                if ($path === '') {
                    $path = '/';
                }
                $query = isset($parts['query']) ? '?'.$parts['query'] : '';
                $target = $path.$query;
            }
        }

        return redirect()->to($target)->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    })->withoutMiddleware(LocalizationMiddleware::class)->name('set_locale');

});
