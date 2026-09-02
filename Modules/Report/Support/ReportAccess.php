<?php

namespace Modules\Report\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Employee\Support\DashboardAccess;

/**
 * General reports web authorization: EMS dashboard permissions.
 */
final class ReportAccess
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
