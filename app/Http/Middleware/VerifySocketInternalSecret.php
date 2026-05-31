<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySocketInternalSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('realtime.internal_secret');
        if (empty($secret)) {
            return response()->json(['message' => 'Socket internal API not configured'], 503);
        }

        if ($request->header('X-Socket-Secret') !== $secret) {
            return response()->json(['message' => 'UNAUTHORIZED'], 401);
        }

        $tenantId = $request->header('X-Tenant-Id');
        if ($tenantId && ! tenant()) {
            tenancy()->initialize($tenantId);
        }

        return $next($request);
    }
}
