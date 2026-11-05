<?php

namespace App\Http\Middleware;

use App\Support\SubscriptionAccess;
use Closure;
use Illuminate\Http\Request;
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
        if ($request->is(
            'api/verify-token',
            'api/verify-socket-token',
            'api/company-login',
            'api/company-logout',
            'api/internal/realtime/*',
        )) {
            return $next($request);
        }

        $excludedRoutes = [
            'login',
            'login.postLogin',
            'logout',
            'register',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'auth.tenant-switch',
            'set_locale',
            // Allow renew / invoice print even when the plan is expired or missing.
            'subscription',
            'subscription.manage',
            'subscription.invoice.print',
            'subscription.cards.add',
            'subscription.cards.default',
            'subscription.cards.update',
            'subscription.cards.delete',
            // Public menu / QR: no subscription DB round-trip on every guest page load
            'reservation.menu',
            'reservation.menuSimple',
            'reservation.menuCover',
            'reservation.menuSimple.feedback',
            'order.products',
        ];

        if ($request->routeIs(...$excludedRoutes)) {
            return $next($request);
        }

        $status = app(SubscriptionAccess::class)->status();

        return match ($status) {
            'ok' => $next($request),
            'no_company' => $this->handleCompanyNotFound($request),
            'no_subscription', 'expired' => $this->handleLapsed($request, $next, $status),
            default => $this->handleCompanyNotFound($request),
        };
    }

    protected function handleCompanyNotFound(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('employee::responses.company_not_found'),
            ], 403);
        }
        auth()->logout();

        return redirect()->route('login')
            ->withInput()
            ->withErrors(['company' => __('employee::responses.company_not_found')]);
    }

    /**
     * Keep the session. Web users land on the dashboard and may only renew.
     * API clients stay blocked without signing the browser session out.
     */
    protected function handleLapsed(Request $request, Closure $next, string $status): Response
    {
        // Login itself is not authenticated yet. Blocking it sends the form
        // to the dashboard, then straight back to the login page.
        if (! $request->user() && ! $request->expectsJson() && ! $request->is('api/*')) {
            return $next($request);
        }

        if (app(SubscriptionAccess::class)->allowsLapsedWeb($request)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            $key = $status === 'expired' ? 'subscription_expired' : 'no_subscription_found';

            return response()->json([
                'message' => __('employee::responses.'.$key),
            ], 403);
        }

        return redirect()->route('dashboard');
    }
}
