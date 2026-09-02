<?php

namespace Modules\Employee\Support;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Employees web authorization: EMS dashboard permissions.
 */
final class EmployeeAccess
{
    /**
     * @param  string|list<string>  $permissions
     */
    public static function can(string|array $permissions, ?Authenticatable $user = null): bool
    {
        return DashboardAccess::allows($user ?? auth()->user(), $permissions);
    }

    /**
     * @param  string|list<string>  $permissions
     */
    public static function allows(?Authenticatable $user, string|array $permissions): bool
    {
        return DashboardAccess::allows($user, $permissions);
    }

    /**
     * @param  string|list<string>  $permissions
     */
    public static function authorize(string|array $permissions, ?Authenticatable $user = null): void
    {
        DashboardAccess::authorize($user ?? auth()->user(), $permissions);
    }
}
