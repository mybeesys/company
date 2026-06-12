<?php

namespace App\Http\Middleware;

use App\Support\SanctumBearerValidator;
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
        $bearerToken = $this->resolveBearerToken($request);
        if ($bearerToken === null || $bearerToken === '') {
            return $this->unauthenticatedResponse($request);
        }

        if (Cache::has($bearerToken) || SanctumBearerValidator::isValid($bearerToken)) {
            Cache::put($bearerToken, true, 86400 /* One day */);

            return $next($request);
        }

        if ($this->verifyViaCentralHttp($bearerToken)) {
            Cache::put($bearerToken, true, 86400 /* One day */);

            return $next($request);
        }

        return $this->unauthenticatedResponse($request);
    }

    private function verifyViaCentralHttp(string $bearerToken): bool
    {
        $url = rtrim((string) config('app.url'), '/').'/api/verify-token';

        try {
            $response = Http::withToken($bearerToken)
                ->acceptJson()
                ->timeout(5)
                ->get($url);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /** Bearer header أو Authorization خام (بعض clients Flutter بدون كلمة Bearer). */
    private function resolveBearerToken(Request $request): ?string
    {
        $token = $request->bearerToken();
        if ($token !== null && $token !== '') {
            return $token;
        }

        $auth = trim((string) $request->header('Authorization', ''));
        if ($auth === '') {
            return null;
        }

        if (stripos($auth, 'Bearer ') === 0) {
            return trim(substr($auth, 7));
        }

        return $auth;
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
