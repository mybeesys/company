<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Company logo and menu cover must live in public/storage/tenant{id}/…
 * because that is the folder Apache serves. A missing file falls through
 * to Laravel and is shown as the generic forbidden page.
 */
final class TenantPublicImage
{
    public static function store(UploadedFile $file, string $directory, int $companyId): string
    {
        $directory = self::normalizeRelative($directory);
        if ($directory === null) {
            throw new \RuntimeException('Invalid image directory.');
        }

        $extension = self::extensionFromUpload($file);
        $name = 'company-'.$companyId.'-'.time().'.'.$extension;
        $relative = $directory.'/'.$name;
        $absoluteDir = self::webDirectory($directory);

        if (! is_dir($absoluteDir) && ! mkdir($absoluteDir, 0755, true) && ! is_dir($absoluteDir)) {
            throw new \RuntimeException('Unable to create the public image directory.');
        }

        $file->move($absoluteDir, $name);
        $absolute = $absoluteDir.DIRECTORY_SEPARATOR.$name;
        if (! is_file($absolute)) {
            throw new \RuntimeException('The image was not stored.');
        }

        @chmod($absolute, 0644);

        return $relative;
    }

    public static function delete(?string $relative): void
    {
        $relative = self::normalizeRelative((string) $relative);
        if ($relative === null) {
            return;
        }

        foreach (self::candidateFiles($relative) as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public static function locate(string $relative): ?string
    {
        $relative = self::normalizeRelative($relative);
        if ($relative === null) {
            return null;
        }

        foreach (self::candidateFiles($relative) as $path) {
            if (self::detectedMime($path) !== null) {
                return $path;
            }
        }

        return null;
    }

    public static function detectedMime(string $absolute): ?string
    {
        if (! is_file($absolute) || ! is_readable($absolute)) {
            return null;
        }

        if (class_exists(\finfo::class)) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($absolute);
            if ($mime === 'image/jpg') {
                $mime = 'image/jpeg';
            }
            if (in_array($mime, ['image/jpeg', 'image/png'], true)) {
                return $mime;
            }
        }

        $head = (string) file_get_contents($absolute, false, null, 0, 8);
        if (str_starts_with($head, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (str_starts_with($head, "\x89PNG\x0D\x0A\x1A\x0A")) {
            return 'image/png';
        }

        return null;
    }

    public static function normalizeRelative(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return null;
        }
        if (str_starts_with($path, '/storage/')) {
            $path = substr($path, 9);
        } elseif (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }
        $path = ltrim($path, '/');

        $suffix = self::tenantSuffix();
        if ($suffix !== '' && str_starts_with($path, $suffix.'/')) {
            $path = substr($path, strlen($suffix) + 1);
        }

        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                return null;
            }
            $parts[] = $part;
        }

        return $parts === [] ? null : implode('/', $parts);
    }

    /**
     * @return list<string>
     */
    private static function candidateFiles(string $relative): array
    {
        $files = [];
        $filename = basename($relative);
        $directory = str_replace('\\', '/', dirname($relative));
        if ($directory === '.' || $directory === DIRECTORY_SEPARATOR) {
            $directory = '';
        }
        $stem = rtrim($filename, '.');
        $stem = preg_replace('/\.(jpe?g|png)$/i', '', $stem) ?? $stem;
        $canGlob = $stem !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $stem) === 1;

        foreach (self::storageRoots() as $root) {
            $direct = self::joinAbsolute($root, $relative);
            if (is_file($direct)) {
                $files[] = $direct;
            }

            if (! $canGlob || ! is_dir($root)) {
                continue;
            }

            $folder = $directory === '' ? $root : self::joinAbsolute($root, $directory);
            if (! is_dir($folder)) {
                continue;
            }

            $matches = glob($folder.DIRECTORY_SEPARATOR.$stem.'*') ?: [];
            foreach ($matches as $match) {
                if (is_file($match)) {
                    $files[] = $match;
                }
            }
        }

        $unique = [];
        foreach ($files as $file) {
            $real = realpath($file);
            if ($real === false || ! self::isInsideRoots($real)) {
                continue;
            }
            $unique[$real] = $real;
        }

        return array_values($unique);
    }

    /**
     * @return list<string>
     */
    private static function storageRoots(): array
    {
        $suffix = self::tenantSuffix();
        $roots = [];
        if ($suffix !== '') {
            $roots[] = public_path('storage'.DIRECTORY_SEPARATOR.$suffix);
            $roots[] = base_path('storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.$suffix);
            $roots[] = base_path('storage'.DIRECTORY_SEPARATOR.$suffix.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public');
            $roots[] = base_path('storage'.DIRECTORY_SEPARATOR.$suffix.DIRECTORY_SEPARATOR.'app');
        }
        $roots[] = storage_path('app'.DIRECTORY_SEPARATOR.'public');

        $unique = [];
        foreach ($roots as $root) {
            $unique[$root] = $root;
        }

        return array_values($unique);
    }

    private static function webDirectory(string $directory): string
    {
        $suffix = self::tenantSuffix();
        if ($suffix === '') {
            throw new \RuntimeException('Tenant storage is not available.');
        }

        return self::joinAbsolute(public_path('storage'.DIRECTORY_SEPARATOR.$suffix), $directory);
    }

    private static function isInsideRoots(string $realFile): bool
    {
        foreach (self::storageRoots() as $root) {
            $realRoot = realpath($root);
            if ($realRoot === false) {
                continue;
            }
            $prefix = rtrim($realRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            if ($realFile === $realRoot || str_starts_with($realFile, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private static function tenantSuffix(): string
    {
        if (! function_exists('tenancy')) {
            return '';
        }

        try {
            $tenant = tenancy()->tenant;
        } catch (\Throwable) {
            return '';
        }

        if (! $tenant) {
            return '';
        }

        $base = (string) config('tenancy.filesystem.suffix_base', 'tenant');

        return $base.(string) $tenant->getTenantKey();
    }

    private static function extensionFromUpload(UploadedFile $file): string
    {
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: $file->guessExtension()));
        $extension = preg_replace('/[^a-z0-9]+/', '', $extension) ?? '';

        return in_array($extension, ['jpg', 'jpeg', 'png'], true) ? $extension : 'jpg';
    }

    private static function joinAbsolute(string $root, string $relative): string
    {
        $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);

        return rtrim($root, '/\\').DIRECTORY_SEPARATOR.ltrim($relative, '/\\');
    }
}
