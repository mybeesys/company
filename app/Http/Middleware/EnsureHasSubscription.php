<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $excludedRoutes = [
            'login',
            'logout',
            'register',
            // Public menu / QR: no subscription DB round-trip on every guest page load
            'reservation.menu',
            'reservation.menuSimple',
            'reservation.menuSimple.feedback',
            'order.products',
        ];

        if ($request->routeIs(...$excludedRoutes)) {
            return $next($request);
        }

        $companyId = get_company_id();
        if (! $companyId) {
            return $this->handleCompanyNotFound($request);
        }

        $status = Cache::remember(
            "tenant_subscription_status:{$companyId}",
            now()->addMinutes(10),
            fn () => $this->resolveSubscriptionStatus($companyId)
        );

        return match ($status) {
            'ok' => $next($request),
            'no_company' => $this->handleCompanyNotFound($request),
            'no_subscription' => $this->handleNoSubscription($request),
            'expired' => $this->handleExpiredSubscription($request),
            default => $this->handleCompanyNotFound($request),
        };
    }

    protected function resolveSubscriptionStatus(int $companyId): string
    {
        $company = Company::find($companyId);
        if (! $company) {
            return 'no_company';
        }

        if (! $company->subscription) {
            return 'no_subscription';
        }

        if ($company->subscription->expired_at && $company->subscription->expired_at < now()) {
            return 'expired';
        }

        return 'ok';
    }

    protected function handleCompanyNotFound(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('company_not_found'),
            ], 403);
        }
        auth()->logout();

        return redirect()->route('login')
            ->withInput()
            ->withErrors(['company' => __('company_not_found')]);
    }

    protected function handleNoSubscription(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('no_subscription_found'),
            ], 403);
        }
        auth()->logout();

        return redirect()->route('login')
            ->withInput()
            ->withErrors(['subscription' => __('no_subscription_found')]);
    }

    protected function handleExpiredSubscription(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('subscription_expired'),
            ], 403);
        }
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withInput()
            ->withErrors(['subscription' => __('subscription_expired')]);
    }
}
