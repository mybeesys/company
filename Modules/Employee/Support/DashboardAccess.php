<?php

namespace Modules\Employee\Support;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Shared EMS dashboard permission checks (wildcard-aware via Employee::hasDashboardPermission).
 */
final class DashboardAccess
{
    /**
     * @param  string|list<string>  $permissions  One permission, or OR-list.
     */
    public static function allows(?Authenticatable $user, string|array $permissions): bool
    {
        if (! $user || ! method_exists($user, 'hasDashboardPermission')) {
            return false;
        }

        foreach (self::flatten($permissions) as $permission) {
            if ($user->hasDashboardPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string|list<string>  $permissions
     */
    public static function authorize(?Authenticatable $user, string|array $permissions): void
    {
        if (! self::allows($user, $permissions)) {
            abort(403, __('employee::responses.permission_denied'));
        }
    }

    /**
     * @param  string|list<string>  $permissions
     * @return list<string>
     */
    public static function flatten(string|array $permissions): array
    {
        $flat = [];

        foreach (is_array($permissions) ? $permissions : [$permissions] as $chunk) {
            foreach (explode(',', (string) $chunk) as $name) {
                $name = trim($name);
                if ($name !== '') {
                    $flat[] = $name;
                }
            }
        }

        return array_values(array_unique($flat));
    }
}
