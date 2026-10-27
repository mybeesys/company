<?php

namespace Modules\Employee\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Employee\Services\UserActivityLogger;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivityMiddleware
{
    public function __construct(protected UserActivityLogger $logger) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('employee.activity_log.enabled', true)) {
            return $response;
        }

        $statusCode = $response->getStatusCode();

        app()->terminating(function () use ($request, $statusCode): void {
            try {
                $this->logger->logFromRequest($request, $statusCode);
            } catch (\Throwable) {
                // Never break the app because audit logging failed.
            }
        });

        return $response;
    }
}
