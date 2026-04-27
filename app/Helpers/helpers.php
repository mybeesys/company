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
     * Build URL for a file on the public disk under /storage using the current request host,
     * so images work when APP_URL differs from the tenant domain.
     *
     * Uses Storage::disk('public')->url() so Stancl tenancy (suffix on storage_path / disk root)
     * matches the real file layout. Avoids manually prepending tenant{id}/ which often breaks
     * paths like companies/logos/… when the disk is already tenant-scoped.
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

        $urlPath = parse_url($generated, PHP_URL_PATH);
        if (! is_string($urlPath) || $urlPath === '') {
            $urlPath = '/storage/' . $path;
        }

        if ($host === '') {
            return asset($urlPath);
        }

        return $host . $urlPath;
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