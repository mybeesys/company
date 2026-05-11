<?php

if (! function_exists('brand_legal_name')) {
    /**
     * الاسم الكامل حسب الترخيص / الوثائق الرسمية (APP_NAME أو APP_LEGAL_NAME).
     */
    function brand_legal_name(): string
    {
        return (string) (config('branding.legal_name') ?: config('app.name'));
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
    function get_company_id()
    {
        $subDomain = tenant('id');

        return DB::connection('mysql')->table('tenants')->find($subDomain)?->company_id;
    }
}

if (! function_exists('tenant_public_storage_url_for_db_path')) {
    /**
     * URL for a tenant public-disk file (cover, allergy PDF, etc.) using the **current** request host
     * so assets load from the same subdomain as the menu (e.g. test1.my-bee.info).
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
            $generated = '/storage/'.$path;
        }

        $urlPath = parse_url((string) $generated, PHP_URL_PATH);
        if (! is_string($urlPath) || $urlPath === '') {
            $urlPath = '/storage/'.$path;
        }

        // Stancl: files live under storage/{suffix_base}{tenant_id}/…; URLs must include that segment
        // or nginx returns 403 (e.g. /storage/menu-covers/… instead of /storage/tenanttest1/menu-covers/…).
        if (function_exists('tenancy') && tenancy()->tenant) {
            $suffixBase = (string) config('tenancy.filesystem.suffix_base', 'tenant');
            $tenantKey = (string) tenant('id');
            $tenantSeg = $suffixBase.$tenantKey;
            if ($tenantSeg !== '' && preg_match('#^/storage/(?!'.preg_quote($tenantSeg, '#').'/)(.+)$#', $urlPath, $m)) {
                $urlPath = '/storage/'.$tenantSeg.'/'.$m[1];
            }
        }

        if ($host === '') {
            return asset($urlPath);
        }

        return $host.$urlPath;
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

        return $base.'/storage/'.$path;
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
