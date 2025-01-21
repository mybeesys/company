<?php

namespace App\Http\Middleware;

use Log;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Client\RequestException;

class CentralAppAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $bearerToken = $request->bearerToken();
            if (empty($bearerToken)) {
                $response_success = false;
            } elseif (Cache::has($bearerToken)) {
                $response_success = true;
            } else {
                $response = Http::withToken($bearerToken)->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json', ])->get(env('APP_URL') . '/api/verify-token');
                $response_success = $response->successful();
            }
            if ($response_success) {
                Cache::put($bearerToken, true, 86400 /* One day */);
                return $next($request);
            } else {
                return $this->unauthenticatedResponse($request);
            }
        } catch (RequestException $e) {
            Log::error('HTTP request failed: ' . $e->getMessage());

            return response()->json([
                "message" => "Unable to verify token. Please try again later."
            ], 500);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred: ' . $e->getMessage());

            return response()->json([
                "message" => "An unexpected error occurred. Please try again later."
            ], 500);
        }

    }

    protected function unauthenticatedResponse(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                "message" => "Unauthenticated."
            ], 401);
        } else {
            return to_route('login');
        }
    }
}
