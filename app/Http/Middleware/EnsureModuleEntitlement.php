<?php

namespace App\Http\Middleware;

use App\Services\EntitlementGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEntitlement
{
    public function __construct(
        protected EntitlementGate $gate,
    ) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        if ($modules !== []) {
            if ($this->gate->allows($modules)) {
                return $next($request);
            }

            return $this->forbidden($request, $modules);
        }

        if ($request->is('api/*')) {
            if ($this->gate->apiPathAllowed($request->path())) {
                return $next($request);
            }

            return $this->forbidden($request, []);
        }

        $routeName = $request->route()?->getName();
        $path = trim($request->path(), '/');
        $required = $this->resolveWebRequirement($routeName, $path);

        if ($required !== null && $this->gate->denies($required)) {
            return $this->forbidden($request, is_array($required) ? $required : [$required]);
        }

        $source = $this->resolveReportSourceRequirement($routeName, $path);
        if ($source !== null) {
            if ($this->gate->denies('reports')) {
                return $this->forbidden($request, ['reports']);
            }

            if ($this->gate->denies($source)) {
                return $this->forbidden($request, is_array($source) ? $source : [$source]);
            }
        }

        return $next($request);
    }

    /**
     * @return string|list<string>|null
     */
    protected function resolveWebRequirement(?string $routeName, string $path): string|array|null
    {
        $map = config('entitlements.route_entitlements', []);

        foreach ($map as $pattern => $module) {
            if ($routeName && (str_starts_with($routeName, $pattern) || $routeName === $pattern)) {
                return $module;
            }

            if ($path !== '' && (str_starts_with($path, $pattern) || $path === $pattern)) {
                return $module;
            }
        }

        return null;
    }

    /**
     * @return string|list<string>|null
     */
    protected function resolveReportSourceRequirement(?string $routeName, string $path): string|array|null
    {
        $map = config('entitlements.report_source_entitlements', []);
        $haystack = trim(($routeName ?? '').' '.$path);

        foreach ($map as $fragment => $sourceModule) {
            if ($fragment !== '' && str_contains($haystack, $fragment)) {
                return $sourceModule;
            }
        }

        return null;
    }

    protected function forbidden(Request $request, array $modules): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => __('responses.entitlement_forbidden'),
                'required_modules' => $modules,
            ], 403);
        }

        abort(403, __('responses.entitlement_forbidden'));
    }
}
