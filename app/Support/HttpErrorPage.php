<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Copy and safe exits for HTML HTTP error pages (403, …).
 */
final class HttpErrorPage
{
    /**
     * @return array{title: string, body: string, hint: string}
     */
    public static function forbiddenCopy(?Throwable $exception): array
    {
        $raw = trim((string) ($exception?->getMessage() ?? ''));

        if ($raw !== '' && ! self::isGenericForbiddenMessage($raw)) {
            return [
                'title' => __('errors.forbidden_custom_title'),
                'body' => $raw,
                'hint' => __('errors.forbidden_hint'),
            ];
        }

        return [
            'title' => __('errors.forbidden_title'),
            'body' => __('errors.forbidden_body'),
            'hint' => __('errors.forbidden_hint'),
        ];
    }

    public static function isGenericForbiddenMessage(string $message): bool
    {
        $normalized = trim($message);
        if ($normalized === '') {
            return true;
        }

        $generic = [
            'Forbidden',
            'This action is unauthorized.',
            'ليست لديك صلاحية لتنفيذ هذا الإجراء.',
            'You do not have permission to perform this action.',
        ];

        foreach ($generic as $item) {
            if (strcasecmp($normalized, $item) === 0) {
                return true;
            }
        }

        return false;
    }

    public static function firstAccessibleUrl(?Authenticatable $user, ?string $currentUrl = null): ?string
    {
        if (! $user || ! method_exists($user, 'hasDashboardPermission')) {
            return null;
        }

        try {
            $items = config('menu', []);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($items)) {
            return null;
        }

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $path = self::firstPathInItem($item, $user);
            if ($path === null) {
                continue;
            }

            $url = self::toUrl($path);
            if (self::sameUrl($url, $currentUrl)) {
                continue;
            }

            return $url;
        }

        return null;
    }

    public static function safeExitUrl(?Authenticatable $user, ?string $currentUrl = null): ?string
    {
        $accessible = self::firstAccessibleUrl($user, $currentUrl);
        if ($accessible) {
            return $accessible;
        }

        if ($user && \Illuminate\Support\Facades\Route::has('profile.edit')) {
            return route('profile.edit');
        }

        if (! $user && \Illuminate\Support\Facades\Route::has('login')) {
            return route('login');
        }

        return null;
    }

    public static function statusCode(?Throwable $exception, int $fallback = 403): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        return $fallback;
    }

    private static function firstPathInItem(array $item, Authenticatable $user): ?string
    {
        $children = $item['subMenu'] ?? [];
        if (is_array($children) && $children !== []) {
            foreach ($children as $child) {
                if (! is_array($child)) {
                    continue;
                }
                $found = self::firstPathInItem($child, $user);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        if (! self::allows($user, $item['permission'] ?? null)) {
            return null;
        }

        $url = trim((string) ($item['url'] ?? ''));

        return $url !== '' ? $url : null;
    }

    private static function allows(Authenticatable $user, mixed $permission): bool
    {
        if ($permission === null || $permission === '' || $permission === []) {
            return true;
        }

        $names = is_array($permission) ? $permission : [$permission];
        foreach ($names as $name) {
            if (! is_string($name) || $name === '') {
                continue;
            }
            if ($user->hasDashboardPermission($name)) {
                return true;
            }
        }

        return false;
    }

    private static function toUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url('/'.ltrim($path, '/'));
    }

    private static function sameUrl(string $left, ?string $right): bool
    {
        if ($right === null || $right === '') {
            return false;
        }

        return self::normalizePath($left) === self::normalizePath($right);
    }

    private static function normalizePath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : $url;

        return '/'.trim($path, '/');
    }
}
