<?php

namespace App\Services;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SubscriptionOverviewService
{
    /**
     * @return array{
     *     company: Company,
     *     owner: object|null,
     *     entitlement: array|null,
     *     current_subscription: object|null,
     *     history: list<array>,
     *     invoices: list<array>,
     *     modules: list<array{key: string, name: string}>,
     *     quotas: list<array{key: string, name: string, value: int}>,
     *     manage_url: string,
     *     status: string,
     *     days_remaining: int|null
     * }
     */
    public function forCompany(?int $companyId = null): array
    {
        $companyId = $companyId ?: (function_exists('get_company_id') ? get_company_id() : null);
        $company = Company::query()->findOrFail($companyId);

        $owner = DB::connection('mysql')
            ->table('users')
            ->where('id', $company->user_id)
            ->first(['id', 'email', 'name']);

        $entitlementRow = $this->entitlementRow((int) $company->id);
        $catalog = $this->catalogLabels();
        $localeIsAr = app()->getLocale() === 'ar' || session('locale') === 'ar';

        $subscriptions = DB::connection('mysql')
            ->table('subscriptions')
            ->where('subscriber_type', Company::class)
            ->where('subscriber_id', $company->id)
            ->orderByDesc('started_at')
            ->get();

        $planIds = $subscriptions->pluck('plan_id')->filter()->unique()->values();
        $plans = $planIds->isEmpty()
            ? collect()
            : DB::connection('mysql')->table('plans')->whereIn('id', $planIds)->get()->keyBy('id');

        $currentSubscription = $subscriptions->first(function ($sub) {
            if ($sub->suppressed_at ?? null) {
                return false;
            }

            if (! $sub->expired_at) {
                return true;
            }

            return Carbon::parse($sub->expired_at)->endOfDay()->gte(now());
        }) ?? $subscriptions->first();

        $entitlement = $entitlementRow ? $this->normalizeEntitlement($entitlementRow, $catalog, $localeIsAr) : null;

        $modules = $entitlement['modules_display'] ?? [];
        if ($modules === [] && $currentSubscription) {
            $modules = $this->legacyPlanFeatures($currentSubscription, $localeIsAr);
        }

        $quotas = $entitlement['quotas'] ?? [];

        $history = $subscriptions->map(function ($sub) use ($plans, $localeIsAr, $currentSubscription) {
            $plan = $plans->get($sub->plan_id);

            return [
                'id' => $sub->id,
                'plan_name' => $this->planName($plan, $localeIsAr),
                'started_at' => $sub->started_at,
                'expired_at' => $sub->expired_at,
                'suppressed_at' => $sub->suppressed_at ?? null,
                'is_current' => $currentSubscription && (int) $currentSubscription->id === (int) $sub->id,
            ];
        })->values()->all();

        $invoices = $this->buildInvoices($subscriptions, $plans, $entitlement, $currentSubscription, $localeIsAr);
        $status = $this->status($currentSubscription);
        $currentPlan = $plans->get($currentSubscription?->plan_id);
        $daysRemaining = null;

        if ($currentSubscription?->expired_at) {
            $daysRemaining = (int) now()->startOfDay()->diffInDays(
                Carbon::parse($currentSubscription->expired_at)->startOfDay(),
                false
            );
        }

        return [
            'company' => $company,
            'owner' => $owner,
            'entitlement' => $entitlement,
            'current_subscription' => $currentSubscription,
            'history' => $history,
            'invoices' => $invoices,
            'modules' => $modules,
            'quotas' => $quotas,
            'manage_url' => $this->manageUrl(),
            'status' => $status,
            'days_remaining' => $daysRemaining,
            'plan_name' => $entitlement
                ? ($localeIsAr ? 'باقة مخصصة' : 'Custom package')
                : $this->planName($currentPlan, $localeIsAr),
            'display_price' => $entitlement
                ? (float) $entitlement['period_total']
                : (float) ($currentPlan?->price_after_discount ?? $currentPlan?->price ?? 0),
            'display_currency' => $entitlement['currency'] ?? 'SAR',
            'display_period' => $entitlement['period']
                ?? ($currentPlan?->periodicity_type ?? null),
        ];
    }

    public function manageUrl(): string
    {
        return url('/subscription/manage');
    }

    protected function entitlementRow(int $companyId): ?object
    {
        if (! $this->centralTableExists('company_entitlements')) {
            return null;
        }

        return DB::connection('mysql')
            ->table('company_entitlements')
            ->where('company_id', $companyId)
            ->first();
    }

    /**
     * @return array<string, array{name_en: string, name_ar: string}>
     */
    protected function catalogLabels(): array
    {
        if (! $this->centralTableExists('entitlement_products')) {
            return [];
        }

        return DB::connection('mysql')
            ->table('entitlement_products')
            ->get(['key', 'name_en', 'name_ar'])
            ->keyBy('key')
            ->map(fn ($row) => [
                'name_en' => (string) $row->name_en,
                'name_ar' => (string) $row->name_ar,
            ])
            ->all();
    }

    /**
     * @param  array<string, array{name_en: string, name_ar: string}>  $catalog
     * @return array{
     *     modules: list<string>,
     *     modules_display: list<array{key: string, name: string}>,
     *     quotas: list<array{key: string, name: string, value: int}>,
     *     period: string,
     *     currency: string,
     *     monthly_subtotal: float,
     *     period_total: float,
     *     line_items: list<array>,
     *     discount: float,
     *     coupon_code: string|null
     * }
     */
    protected function normalizeEntitlement(object $row, array $catalog, bool $localeIsAr): array
    {
        $modules = json_decode($row->modules ?? '[]', true);
        if (! is_array($modules)) {
            $modules = [];
        }

        $lineItems = json_decode($row->line_items ?? '[]', true);
        if (! is_array($lineItems)) {
            $lineItems = [];
        }

        $meta = json_decode($row->meta ?? '{}', true);
        if (! is_array($meta)) {
            $meta = [];
        }

        $modulesDisplay = collect(array_values(array_unique(array_merge(['platform'], $modules))))
            ->map(fn (string $key) => [
                'key' => $key,
                'name' => $this->labelFor($key, $catalog, $localeIsAr),
            ])
            ->all();

        $quotas = [];
        foreach ([
            'employees_quota' => 'employees',
            'establishments_quota' => 'establishments',
            'screen_devices_quota' => 'screen_devices',
        ] as $column => $key) {
            $value = (int) ($row->{$column} ?? 0);
            if ($key === 'screen_devices' && $value <= 0 && ! in_array('digital_screens', $modules, true)) {
                continue;
            }

            $quotas[] = [
                'key' => $key,
                'name' => $this->labelFor($key, $catalog, $localeIsAr),
                'value' => $value,
            ];
        }

        return [
            'modules' => $modules,
            'modules_display' => $modulesDisplay,
            'quotas' => $quotas,
            'period' => (string) ($row->period ?? 'Month'),
            'currency' => (string) ($row->currency ?? 'SAR'),
            'monthly_subtotal' => (float) ($row->monthly_subtotal ?? 0),
            'period_total' => (float) ($row->period_total ?? 0),
            'line_items' => $lineItems,
            'discount' => (float) ($meta['discount'] ?? 0),
            'coupon_code' => $meta['coupon_code'] ?? null,
        ];
    }

    /**
     * @param  array<string, array{name_en: string, name_ar: string}>  $catalog
     */
    protected function labelFor(string $key, array $catalog, bool $localeIsAr): string
    {
        if (isset($catalog[$key])) {
            return $localeIsAr
                ? ($catalog[$key]['name_ar'] ?: $catalog[$key]['name_en'])
                : ($catalog[$key]['name_en'] ?: $catalog[$key]['name_ar']);
        }

        $fallback = [
            'platform' => ['en' => 'Core platform', 'ar' => 'المنصة الأساسية'],
            'employees' => ['en' => 'Employees', 'ar' => 'الموظفون'],
            'establishments' => ['en' => 'Branches', 'ar' => 'الفروع'],
            'screen_devices' => ['en' => 'Screen devices', 'ar' => 'أجهزة الشاشات'],
        ];

        if (isset($fallback[$key])) {
            return $localeIsAr ? $fallback[$key]['ar'] : $fallback[$key]['en'];
        }

        return str_replace('_', ' ', $key);
    }

    protected function planName(?object $plan, bool $localeIsAr): string
    {
        if (! $plan) {
            return $localeIsAr ? 'غير محدد' : 'Unspecified';
        }

        return $localeIsAr
            ? ((string) ($plan->name_ar ?: $plan->name))
            : ((string) ($plan->name ?: $plan->name_ar));
    }

    /**
     * @return list<array{key: string, name: string}>
     */
    protected function legacyPlanFeatures(object $subscription, bool $localeIsAr): array
    {
        if (! $this->centralTableExists('feature_plan') || ! $this->centralTableExists('features')) {
            return [];
        }

        $featureIds = DB::connection('mysql')
            ->table('feature_plan')
            ->where('plan_id', $subscription->plan_id)
            ->pluck('feature_id');

        if ($featureIds->isEmpty()) {
            return [];
        }

        return DB::connection('mysql')
            ->table('features')
            ->whereIn('id', $featureIds)
            ->get()
            ->map(fn ($feature) => [
                'key' => (string) ($feature->name ?? $feature->id),
                'name' => $localeIsAr
                    ? (string) ($feature->name_ar ?: $feature->name)
                    : (string) ($feature->name_en ?: $feature->name),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, object>  $subscriptions
     * @param  Collection<int, object>  $plans
     * @param  array|null  $entitlement
     * @return list<array>
     */
    protected function buildInvoices(Collection $subscriptions, Collection $plans, ?array $entitlement, ?object $currentSubscription, bool $localeIsAr): array
    {
        $invoices = [];

        foreach ($subscriptions as $sub) {
            $plan = $plans->get($sub->plan_id);
            $isCurrent = $currentSubscription && (int) $currentSubscription->id === (int) $sub->id;
            $amount = $isCurrent && $entitlement
                ? (float) $entitlement['period_total']
                : (float) ($plan->price_after_discount ?? $plan->price ?? 0);
            $currency = $isCurrent && $entitlement
                ? ($entitlement['currency'] ?? 'SAR')
                : 'SAR';
            $period = $isCurrent && $entitlement
                ? ($entitlement['period'] ?? null)
                : ($plan->periodicity_type ?? null);

            $expired = $sub->expired_at && Carbon::parse($sub->expired_at)->endOfDay()->lt(now());
            $status = $expired
                ? 'expired'
                : (($sub->suppressed_at ?? null) ? 'cancelled' : 'paid');

            $invoices[] = [
                'id' => 'SUB-'.$sub->id,
                'subscription_id' => $sub->id,
                'label' => $isCurrent && $entitlement
                    ? ($localeIsAr ? 'باقة مخصصة' : 'Custom package')
                    : $this->planName($plan, $localeIsAr),
                'amount' => $amount,
                'currency' => $currency,
                'period' => $period,
                'status' => $status,
                'date' => $sub->started_at,
                'line_items' => $isCurrent && $entitlement ? ($entitlement['line_items'] ?? []) : [],
            ];
        }

        return $invoices;
    }

    protected function status(?object $subscription): string
    {
        if (! $subscription) {
            return 'none';
        }

        if ($subscription->suppressed_at ?? null) {
            return 'cancelled';
        }

        if (! $subscription->expired_at) {
            return 'active';
        }

        $expiredAt = Carbon::parse($subscription->expired_at)->endOfDay();

        if ($expiredAt->lt(now())) {
            return 'expired';
        }

        if ($expiredAt->lte(now()->addDays(14))) {
            return 'expiring';
        }

        return 'active';
    }

    protected function centralTableExists(string $table): bool
    {
        try {
            return Schema::connection('mysql')->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
