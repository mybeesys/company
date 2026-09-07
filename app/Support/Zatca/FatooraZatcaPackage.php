<?php

namespace App\Support\Zatca;

/**
 * Resolves which on-disk Fatoora ZATCA package tree to load.
 *
 * Sandbox (local/simulation): packages/fatoora-zatca
 * Production: packages/fatoora-zatca-production (vendor production build + clients.txt)
 *
 * Environment is controlled exclusively via ZATCA_ENVIRONMENT in .env —
 * never by UI — so a tenant cannot accidentally switch package/licence.
 */
final class FatooraZatcaPackage
{
    public const VARIANT_SANDBOX = 'sandbox';

    public const VARIANT_PRODUCTION = 'production';

    public static function environment(): string
    {
        $env = (string) config('zatca.app.environment', 'local');

        return in_array($env, ['local', 'simulation', 'production'], true)
            ? $env
            : 'local';
    }

    public static function isProduction(): bool
    {
        return self::environment() === 'production';
    }

    public static function variant(): string
    {
        return self::isProduction()
            ? self::VARIANT_PRODUCTION
            : self::VARIANT_SANDBOX;
    }

    public static function rootPath(): string
    {
        $paths = config('zatca.packages', []);
        $variant = self::variant();
        $configured = $paths[$variant] ?? null;

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return $variant === self::VARIANT_PRODUCTION
            ? base_path('packages/fatoora-zatca-production')
            : base_path('packages/fatoora-zatca');
    }

    public static function srcPath(): string
    {
        return rtrim(self::rootPath(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'src';
    }

    public static function appKey(): ?string
    {
        $key = config('zatca.app.key');

        if (! is_string($key)) {
            return null;
        }

        $key = trim($key);

        return $key !== '' ? $key : null;
    }

    /**
     * Register a prepended PSR-4 loader for the production package when needed.
     * Composer still maps Bl\FatooraZatca\ → sandbox; production wins via prepend.
     */
    public static function registerAutoload(): void
    {
        if (! self::isProduction()) {
            return;
        }

        $src = self::srcPath();
        if (! is_dir($src)) {
            return;
        }

        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        spl_autoload_register(static function (string $class) use ($src): void {
            $prefix = 'Bl\\FatooraZatca\\';
            if (! str_starts_with($class, $prefix)) {
                return;
            }

            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = $src.DIRECTORY_SEPARATOR.$relative.'.php';
            if (is_file($file)) {
                require $file;
            }
        }, true, true);
    }
}
