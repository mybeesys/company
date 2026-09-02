<?php

namespace Modules\Employee\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Employee\Support\DashboardAccess;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require one of the given EMS dashboard permissions (comma-separated = OR).
 *
 * Usage: middleware('dashboard.perm:sales.Quotations.show,sales.Sell invoices.show')
 */
class EnsureDashboardPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        DashboardAccess::authorize($request->user(), $permissions);

        return $next($request);
    }
}
