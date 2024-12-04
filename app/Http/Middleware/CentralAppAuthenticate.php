<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
        if (Cache::has($request->bearerToken())) {
            $response_success = true;
        } else {
            $response = Http::withToken($request->bearerToken())->get(env('APP_URL') . '/api/verify-token');
            $response_success = $response->successful();
        }
        if ($response_success) {
            Cache::put($request->bearerToken(), true, 86400 /* One day */);
            return $next($request);
        } else {
            if ($request->expectsJson()) {
                return response()->json([
                    "message" => "Unauthenticated."
                ], 401);
            } else {
                return to_route('login');
            }
        }
    }
}
