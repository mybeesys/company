<?php

if (! function_exists('brand_legal_name')) {
    /**
     * اسم المنتج في PDF والبريد وحقوق النشر — موحّد مع brand_short_name().
     */
    function brand_legal_name(): string
    {
        return brand_short_name();
    }
}

if (! function_exists('brand_short_name')) {
    /**
     * الاسم المختصر لواجهة المستخدم (تبويبات، هيدر، تسجيل دخول…).
     */
    function brand_short_name(): string
    {
        return app()->getLocale() === 'ar'
            ? (string) config('branding.short_name_ar')
            : (string) config('branding.short_name_en');
    }
}

if (! function_exists('get_company_id')) {
    /**
     * Company id for the current tenant. Uses loaded tenant data when available
     * to avoid an extra central DB query on every request.
     */
    function get_company_id(): ?int
    {
        static $companyId = null;
        static $resolved = false;

        if ($resolved) {
            return $companyId;
        }

        $resolved = true;

        if (function_exists('tenancy') && tenancy()->initialized) {
            $fromTenant = tenant('company_id');
            if ($fromTenant !== null && $fromTenant !== '') {
                $companyId = (int) $fromTenant;

                return $companyId;
            }
        }

        $tenantId = tenant('id');
        if ($tenantId) {
            $found = \Illuminate\Support\Facades\DB::connection('mysql')
                ->table('tenants')
                ->where('id', $tenantId)
                ->value('company_id');
            $companyId = $found !== null ? (int) $found : null;
        }

        return $companyId;
    }
}

if (! function_exists('company_header_name')) {
    /**
     * Display name for the current company in the app header (cached per locale).
     */
    function company_header_name(): ?string
    {
        $companyId = get_company_id();
        if (! $companyId) {
            return null;
        }

        $locale = app()->getLocale();

        return \Illuminate\Support\Facades\Cache::remember(
            "company_header_name:{$companyId}:{$locale}",
            now()->addHours(6),
            function () use ($companyId, $locale) {
                $connection = (string) config('tenancy.database.central_connection', 'mysql');
                if ($connection === '' || ! config("database.connections.{$connection}")) {
                    $connection = 'mysql';
                }

                $columns = ['name'];
                if (\Illuminate\Support\Facades\Schema::connection($connection)->hasColumn('companies', 'name_ar')) {
                    $columns[] = 'name_ar';
                }
                if (\Illuminate\Support\Facades\Schema::connection($connection)->hasColumn('companies', 'tax_name')) {
                    $columns[] = 'tax_name';
                }

                $row = \Illuminate\Support\Facades\DB::connection($connection)
                    ->table('companies')
                    ->select($columns)
                    ->where('id', $companyId)
                    ->first();

                if (! $row) {
                    return null;
                }

                $name = trim((string) ($row->name ?? ''));
                $nameAr = trim((string) ($row->name_ar ?? ''));
                $taxName = trim((string) ($row->tax_name ?? ''));

                if ($locale === 'ar') {
                    return $nameAr !== '' ? $nameAr : ($name !== '' ? $name : ($taxName !== '' ? $taxName : null));
                }

                return $name !== '' ? $name : ($nameAr !== '' ? $nameAr : ($taxName !== '' ? $taxName : null));
            }
        );
    }
}

if (! function_exists('forget_company_header_name_cache')) {
    function forget_company_header_name_cache(?int $companyId = null): void
    {
        $companyId ??= get_company_id();
        if (! $companyId) {
            return;
        }

        foreach (['ar', 'en'] as $locale) {
            \Illuminate\Support\Facades\Cache::forget("company_header_name:{$companyId}:{$locale}");
        }
    }
}

if (! function_exists('tenant_public_storage_url_for_db_path')) {
    /**
     * URL for a tenant public-disk file (cover, allergy PDF, etc.) using the **current** request host
     * so assets load from the same subdomain as the menu (e.g. test1.mybeesystem.net).
     */
    function tenant_public_storage_url_for_db_path(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        if (str_starts_with($path, '/storage/')) {
            $path = ltrim(substr($path, 9), '/');
        } elseif (str_starts_with($path, 'storage/')) {
            $path = ltrim(substr($path, 8), '/');
        }
        $path = ltrim($path, '/');

        $host = '';
        if (function_exists('request') && request()) {
            $host = rtrim((string) request()->getSchemeAndHttpHost(), '/');
        }

        try {
            $generated = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        } catch (\Throwable $e) {
            $generated = '/storage/' . $path;
        }

        $urlPath = parse_url((string) $generated, PHP_URL_PATH);
        if (! is_string($urlPath) || $urlPath === '') {
            $urlPath = '/storage/' . $path;
        }

        // Stancl: files live under storage/{suffix_base}{tenant_id}/…; URLs must include that segment
        // or nginx returns 403 (e.g. /storage/menu-covers/… instead of /storage/tenanttest1/menu-covers/…).
        if (function_exists('tenancy') && tenancy()->tenant) {
            $suffixBase = (string) config('tenancy.filesystem.suffix_base', 'tenant');
            $tenantKey = (string) tenant('id');
            $tenantSeg = $suffixBase . $tenantKey;
            if ($tenantSeg !== '' && preg_match('#^/storage/(?!' . preg_quote($tenantSeg, '#') . '/)(.+)$#', $urlPath, $m)) {
                $urlPath = '/storage/' . $tenantSeg . '/' . $m[1];
            }
        }

        if ($host === '') {
            return asset($urlPath);
        }

        return $host . $urlPath;
    }
}

if (! function_exists('central_public_storage_url_for_path')) {
    /**
     * Absolute URL on the central application (APP_URL) for shared storage paths such as
     * company logos (companies/logos/...) served from the main domain while the menu opens on a tenant host.
     */
    function central_public_storage_url_for_path(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        if (str_starts_with($path, '/storage/')) {
            $path = ltrim(substr($path, 9), '/');
        } elseif (str_starts_with($path, 'storage/')) {
            $path = ltrim(substr($path, 8), '/');
        }
        $path = ltrim($path, '/');

        $base = rtrim((string) config('app.url'), '/');

        return $base . '/storage/' . $path;
    }
}

if (! function_exists('convertToHoursMinutesHelper')) {
    function convertToHoursMinutesHelper($totalMinutes)
    {
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}

if (! function_exists('convertToDecimalFormatHelper')) {
    function convertToDecimalFormatHelper($time, bool $minutes)
    {
        $time = explode(':', $time);
        $totalMinutes = $time[0] * 60 + $time[1];

        return $minutes ? $totalMinutes : round($totalMinutes / 60, 2);
    }
}

if (! function_exists('get_name_by_lang')) {
    function get_name_by_lang()
    {
        $name = session('locale') === 'ar' ? 'name' : 'name_en';

        return $name;
    }
}

if (! function_exists('is_embed_request')) {
    function is_embed_request(): bool
    {
        return request()->boolean('embed');
    }
}

if (! function_exists('app_layout')) {
    /**
     * Full app shell or minimal embed layout (hub iframe / ?embed=1).
     */
    function app_layout(string $default = 'layouts.app'): string
    {
        return is_embed_request() ? 'layouts.embed' : $default;
    }
}

if (! function_exists('embed_url')) {
    /**
     * Append embed=1 when the current request is embedded.
     */
    function embed_url(string $url): string
    {
        if (! is_embed_request()) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        if (str_contains($url, 'embed=1')) {
            return $url;
        }

        return $url . $separator . 'embed=1';
    }
}

if (! function_exists('menu_hub_is_active')) {
    /**
     * True when the current page belongs to a hub module (sales, purchases, franchise).
     */
    function menu_hub_is_active(?string $menuName): bool
    {
        if ($menuName === null || $menuName === '') {
            return false;
        }

        $hub = config("menu_hubs.{$menuName}");
        if (! is_array($hub)) {
            return false;
        }

        if (! empty($hub['path_prefix'])) {
            $prefix = ltrim((string) $hub['path_prefix'], '/');
            if (request()->is($prefix) || request()->is($prefix . '/*')) {
                return true;
            }
        }

        foreach ($hub['path_prefixes'] ?? [] as $prefix) {
            $prefix = ltrim((string) $prefix, '/');
            if ($prefix !== '' && (request()->is($prefix) || request()->is($prefix . '/*'))) {
                return true;
            }
        }

        $routeName = request()->route()?->getName();
        if ($routeName === null || $routeName === '') {
            return false;
        }

        $tabRoutes = collect(config($hub['tabs_config'] ?? [], []))
            ->pluck('route')
            ->filter();

        $allRoutes = $tabRoutes->merge($hub['extra_routes'] ?? []);

        if ($allRoutes->contains($routeName)) {
            return true;
        }

        foreach ($hub['route_prefixes'] ?? [] as $prefix) {
            if (str_starts_with($routeName, (string) $prefix)) {
                return true;
            }
        }

        return false;
    }
}
