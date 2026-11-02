<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Logo of the tenant company (general settings), for documents the company issues.
 * Never falls back to the My Bee product mark.
 */
final class CompanyDocumentLogo
{
    public static function companyRow(): ?object
    {
        if (! function_exists('get_company_id')) {
            return null;
        }

        $id = (int) (get_company_id() ?: 0);
        if ($id <= 0) {
            return null;
        }

        try {
            $row = DB::connection('mysql')->table('companies')->find($id);
        } catch (\Throwable) {
            return null;
        }

        return $row ?: null;
    }

    public static function relativePath(?object $company): ?string
    {
        if (! $company) {
            return null;
        }

        foreach (['logo', 'logo_path', 'image', 'company_logo'] as $col) {
            if (empty($company->{$col})) {
                continue;
            }
            $raw = trim((string) $company->{$col});
            if ($raw === '') {
                continue;
            }
            if (preg_match('#^https?://#i', $raw)) {
                return $raw;
            }
            $normalized = TenantPublicImage::normalizeRelative($raw);

            return $normalized ?: $raw;
        }

        return null;
    }

    public static function absolutePath(?object $company): ?string
    {
        $relative = self::relativePath($company);
        if ($relative === null || preg_match('#^https?://#i', $relative)) {
            return null;
        }

        $located = TenantPublicImage::locate($relative);
        if ($located) {
            return $located;
        }

        $public = public_path(ltrim($relative, '/'));
        if (is_file($public)) {
            return $public;
        }

        return null;
    }

    public static function src(?object $company, bool $forPdf = false): ?string
    {
        $relative = self::relativePath($company);
        if ($relative === null) {
            return null;
        }

        if (preg_match('#^https?://#i', $relative)) {
            return $relative;
        }

        if ($forPdf) {
            return self::absolutePath($company);
        }

        if (function_exists('resolve_public_storage_url')) {
            $url = resolve_public_storage_url($relative);
            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        $absolute = self::absolutePath($company);

        return $absolute ?: null;
    }

    public static function currentSrc(bool $forPdf = false): ?string
    {
        return self::src(self::companyRow(), $forPdf);
    }

    public static function currentPdfBase64(): string
    {
        $path = self::absolutePath(self::companyRow());
        if (! $path || ! is_readable($path)) {
            return '';
        }

        $binary = @file_get_contents($path);
        if ($binary === false || $binary === '') {
            return '';
        }

        return base64_encode($binary);
    }
}
