<?php

namespace App\Http\Middleware;

use App\Support\SanctumBearerValidator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CentralAppAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();
        if (empty($bearerToken)) {
            return $this->unauthenticatedResponse($request);
        }

        if (Cache::has($bearerToken) || SanctumBearerValidator::isValid($bearerToken)) {
            Cache::put($bearerToken, true, 86400 /* One day */);

            return $next($request);
        }

        return $this->unauthenticatedResponse($request);
    }

    protected function unauthenticatedResponse(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return to_route('login');
    }
}
