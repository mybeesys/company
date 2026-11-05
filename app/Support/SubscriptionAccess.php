<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Company plan state for the signed-in tenant session.
 * Lapsed companies may sign in, see the dashboard, and renew — nothing else.
 */
final class SubscriptionAccess
{
    public function status(): string
    {
        $companyId = function_exists('get_company_id') ? get_company_id() : null;
        if (! $companyId) {
            return 'no_company';
        }

        return Cache::remember(
            "tenant_subscription_status:{$companyId}",
            now()->addMinutes(10),
            fn () => $this->resolve((int) $companyId)
        );
    }

    public function isLocked(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return in_array($this->status(), ['no_subscription', 'expired'], true);
    }

    public function reason(): ?string
    {
        return match ($this->status()) {
            'expired' => 'expired',
            'no_subscription' => 'missing',
            default => null,
        };
    }

    /**
     * Pages a lapsed company may still open: dashboard shell and subscription renewal.
     */
    public function allowsLapsedWeb(Request $request): bool
    {
        if ($request->routeIs(
            'dashboard',
            'dashboard-v2.bootstrap',
            'dashboard-v2.data',
            'dashboard-v2.widget',
            'subscription',
            'subscription.manage',
            'subscription.invoice.print',
            'subscription.cards.add',
            'subscription.cards.default',
            'subscription.cards.update',
            'subscription.cards.delete',
            'logout',
            'set_locale',
            'my-companies.index',
            'my-companies.switch',
        )) {
            return true;
        }

        return $request->path() === '/' || $request->path() === '';
    }

    protected function resolve(int $companyId): string
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
}
