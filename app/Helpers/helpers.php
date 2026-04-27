<?php


if (!function_exists('get_company_id')) {
    function get_company_id()
    {
        $subDomain = tenant('id');
        return DB::connection('mysql')->table('tenants')->find($subDomain)?->company_id;
    }
}

if (!function_exists('tenant_public_storage_url_for_db_path')) {
    /**
     * Build a root-relative URL for a file on the public disk (/storage/...).
     *
     * Always returns a path starting with /storage/ so the browser uses the **current** host
     * (tenant subdomain, etc.) and never embeds APP_URL from env / filesystems config.
     *
     * Uses Storage::disk('public')->url() only to resolve the correct path segment under tenancy;
     * any scheme+host from that call is stripped.
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

        try {
            $generated = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        } catch (\Throwable $e) {
            $generated = '/storage/' . $path;
        }

        if (preg_match('#^https?://#i', (string) $generated)) {
            $urlPath = parse_url((string) $generated, PHP_URL_PATH);
        } else {
            $urlPath = '/' . ltrim((string) $generated, '/');
        }

        if (! is_string($urlPath) || $urlPath === '' || $urlPath === '/') {
            $urlPath = '/storage/' . $path;
        }

        return '/' . ltrim($urlPath, '/');
    }
}

if (!function_exists('convertToHoursMinutesHelper')) {
    function convertToHoursMinutesHelper($totalMinutes)
    {
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }
}

if (!function_exists('convertToDecimalFormatHelper')) {
    function convertToDecimalFormatHelper($time, bool $minutes)
    {
        $time = explode(':', $time);
        $totalMinutes = $time[0] * 60 + $time[1];
        return $minutes ? $totalMinutes : round($totalMinutes / 60, 2);
    }
}

if (!function_exists('get_name_by_lang')) {
    function get_name_by_lang()
    {
        $name = session('locale') === 'ar' ? 'name' : 'name_en';
        return $name;
    }
}