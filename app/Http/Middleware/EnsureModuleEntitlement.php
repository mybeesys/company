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
        // Tenancy middleware is prioritized first; if it did not run, do not
        // soft-open commercial modules via the legacy fallback.
        if (function_exists('tenancy') && ! tenancy()->initialized) {
            return $next($request);
        }

        if ($modules !== []) {
            if ($this->gate->allows($modules)) {
                return $next($request);
            }

            return $this->forbidden($request, $modules);
        }

        if ($request->is('api/*')) {
            $required = $this->resolveApiRequirement($request->path());
            if ($required !== null && $this->gate->denies($required)) {
                return $this->forbidden($request, is_array($required) ? $required : [$required]);
            }

            return $next($request);
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
        return $this->longestMatch(
            config('entitlements.route_entitlements', []),
            array_values(array_filter([$routeName, $path], fn ($value) => filled($value)))
        );
    }

    /**
     * @return string|list<string>|null
     */
    protected function resolveApiRequirement(string $path): string|array|null
    {
        $path = ltrim($path, '/');

        return $this->longestMatch(
            config('entitlements.api_entitlements', []),
            [$path]
        );
    }

    /**
     * Pick the longest matching pattern so specific routes win over short prefixes.
     *
     * @param  array<string, string|list<string>>  $map
     * @param  list<string>  $candidates
     * @return string|list<string>|null
     */
    protected function longestMatch(array $map, array $candidates): string|array|null
    {
        $bestPattern = null;
        $bestModule = null;
        $bestLength = -1;

        foreach ($map as $pattern => $module) {
            $pattern = (string) $pattern;
            if ($pattern === '') {
                continue;
            }

            foreach ($candidates as $candidate) {
                if ($candidate === $pattern || str_starts_with($candidate, $pattern)) {
                    $length = strlen($pattern);
                    if ($length > $bestLength) {
                        $bestLength = $length;
                        $bestPattern = $pattern;
                        $bestModule = $module;
                    }
                }
            }
        }

        return $bestModule;
    }

    /**
     * @return string|list<string>|null
     */
    protected function resolveReportSourceRequirement(?string $routeName, string $path): string|array|null
    {
        $map = config('entitlements.report_source_entitlements', []);
        $haystack = trim(($routeName ?? '').' '.$path);
        $bestFragment = null;
        $bestModule = null;
        $bestLength = -1;

        foreach ($map as $fragment => $sourceModule) {
            $fragment = (string) $fragment;
            if ($fragment !== '' && str_contains($haystack, $fragment)) {
                $length = strlen($fragment);
                if ($length > $bestLength) {
                    $bestLength = $length;
                    $bestFragment = $fragment;
                    $bestModule = $sourceModule;
                }
            }
        }

        return $bestModule;
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
