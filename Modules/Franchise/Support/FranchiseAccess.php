<?php

namespace Modules\Franchise\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Employee\Support\DashboardAccess;

/**
 * Franchise web authorization: EMS dashboard permissions.
 */
final class FranchiseAccess
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
    public static function authorize(string|array $permissions, ?Authenticatable $user = null): void
    {
        DashboardAccess::authorize($user ?? auth()->user(), $permissions);
    }
}
