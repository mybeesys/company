<?php

namespace Modules\Zatca\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require one of the given EMS dashboard permissions (comma-separated = OR).
 *
 * Usage: middleware('zatca.perm:zatca.Settings.show,zatca.Operations.show')
 */
class EnsureZatcaPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        if (! $user || ! method_exists($user, 'hasDashboardPermission')) {
            abort(403);
        }

        $flat = [];
        foreach ($permissions as $chunk) {
            foreach (explode(',', (string) $chunk) as $name) {
                $name = trim($name);
                if ($name !== '') {
                    $flat[] = $name;
                }
            }
        }

        foreach ($flat as $permission) {
            if ($user->hasDashboardPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, __('zatca::lang.permission_denied'));
    }
}
