<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EntitlementGate
{
    /**
     * Per-request memo (avoid tenant-tagged Redis cache returning stale packages).
     *
     * @var array<int|string, array{
     *     modules: list<string>,
     *     employees_quota: int|null,
     *     establishments_quota: int|null,
     *     screen_devices_quota: int|null,
     *     pos_devices_quota: int|null,
     *     waiter_seats_quota: int|null,
     *     kitchen_devices_quota: int|null,
     *     legacy: bool
     * }>
     */
    protected array $memo = [];

    /** @var array<string, array{module: string, quota_key: string, label_ar: string, label_en: string}> */
    public const EST_POS_TYPE_MAP = [
        'cashier' => [
            'module' => 'cashier_pos',
            'quota_key' => 'pos_devices',
            'label_ar' => 'أجهزة الكاشير',
            'label_en' => 'POS devices',
        ],
        'waiters' => [
            'module' => 'waiter_app',
            'quota_key' => 'waiter_seats',
            'label_ar' => 'أجهزة / مقاعد الويتر',
            'label_en' => 'Waiter devices',
        ],
        'kitchen_screen' => [
            'module' => 'kitchen_app',
            'quota_key' => 'kitchen_devices',
            'label_ar' => 'أجهزة المطبخ',
            'label_en' => 'Kitchen devices',
        ],
    ];

    /**
     * @return array{
     *     modules: list<string>,
     *     employees_quota: int|null,
     *     establishments_quota: int|null,
     *     screen_devices_quota: int|null,
     *     pos_devices_quota: int|null,
     *     waiter_seats_quota: int|null,
     *     kitchen_devices_quota: int|null,
     *     legacy: bool
     * }
     */
    public function forCompany(?int $companyId = null): array
    {
        $companyId = $companyId ?: (function_exists('get_company_id') ? get_company_id() : null);

        if (! $companyId) {
            // Inside a tenant request without company: fail closed (platform only).
            if (function_exists('tenancy') && tenancy()->initialized) {
                return $this->empty(legacy: false);
            }

            // Outside tenancy (CLI/central): keep legacy open only when configured.
            return $this->empty(legacy: (bool) config('entitlements.legacy_unrestricted', true));
        }

        if (isset($this->memo[$companyId])) {
            return $this->memo[$companyId];
        }

        $connection = $this->centralConnection();

        try {
            $row = DB::connection($connection)
                ->table('company_entitlements')
                ->where('company_id', $companyId)
                ->first();
        } catch (\Throwable) {
            // Fail closed for known tenants — never open every module on DB errors.
            return $this->memo[$companyId] = $this->empty(legacy: false);
        }

        if (! $row) {
            return $this->memo[$companyId] = $this->empty(
                legacy: (bool) config('entitlements.legacy_unrestricted', true)
            );
        }

        $modules = json_decode($row->modules ?? '[]', true);
        if (! is_array($modules)) {
            $modules = [];
        }

        return $this->memo[$companyId] = [
            'modules' => array_values(array_unique(array_merge(['platform'], $modules))),
            'employees_quota' => (int) $row->employees_quota,
            'establishments_quota' => (int) $row->establishments_quota,
            'screen_devices_quota' => (int) ($row->screen_devices_quota ?? 0),
            'pos_devices_quota' => (int) ($row->pos_devices_quota ?? 0),
            'waiter_seats_quota' => (int) ($row->waiter_seats_quota ?? 0),
            'kitchen_devices_quota' => (int) ($row->kitchen_devices_quota ?? 0),
            'legacy' => false,
        ];
    }

    /**
     * Expand commercial packages into the legacy/internal keys menus & routes still use.
     * e.g. finance_business → sales, purchases, accounting, expenses.
     *
     * @param  list<string>  $modules
     * @return list<string>
     */
    protected function expandGrantedModules(array $modules): array
    {
        $grantsMap = config('entitlements.module_grants', [
            'finance_business' => ['sales', 'purchases', 'accounting', 'expenses'],
        ]);

        $expanded = $modules;

        foreach ($modules as $module) {
            foreach ($grantsMap[$module] ?? [] as $granted) {
                $expanded[] = $granted;
            }
        }

        return array_values(array_unique($expanded));
    }

    public function allows(string|array $moduleKeys, ?int $companyId = null): bool
    {
        $state = $this->forCompany($companyId);

        if ($state['legacy']) {
            return true;
        }

        $entitled = $this->expandGrantedModules($state['modules']);
        $keys = is_array($moduleKeys) ? $moduleKeys : [$moduleKeys];

        foreach ($keys as $key) {
            if ($key === 'platform' || in_array($key, $entitled, true)) {
                return true;
            }
        }

        return false;
    }

    public function screenDevicesQuota(?int $companyId = null): ?int
    {
        return $this->moduleQuota(
            module: 'digital_screens',
            quotaField: 'screen_devices_quota',
            companyId: $companyId,
        );
    }

    public function posDevicesQuota(?int $companyId = null): ?int
    {
        return $this->moduleQuota(
            module: 'cashier_pos',
            quotaField: 'pos_devices_quota',
            companyId: $companyId,
            includedFallback: 1,
        );
    }

    public function waiterSeatsQuota(?int $companyId = null): ?int
    {
        return $this->moduleQuota(
            module: 'waiter_app',
            quotaField: 'waiter_seats_quota',
            companyId: $companyId,
            includedFallback: 1,
        );
    }

    public function kitchenDevicesQuota(?int $companyId = null): ?int
    {
        return $this->moduleQuota(
            module: 'kitchen_app',
            quotaField: 'kitchen_devices_quota',
            companyId: $companyId,
            includedFallback: 1,
        );
    }

    /**
     * @return int|null null = legacy unrestricted
     */
    protected function moduleQuota(
        string $module,
        string $quotaField,
        ?int $companyId = null,
        int $includedFallback = 0,
    ): ?int {
        $state = $this->forCompany($companyId);

        if ($state['legacy']) {
            return null;
        }

        if (! in_array($module, $state['modules'], true)) {
            return 0;
        }

        $quota = (int) ($state[$quotaField] ?? 0);

        if ($quota <= 0 && $includedFallback > 0) {
            return $includedFallback;
        }

        return $quota;
    }

    public function canAddScreenDevice(?int $companyId = null): bool
    {
        $quota = $this->screenDevicesQuota($companyId);

        if ($quota === null) {
            return true; // legacy unrestricted
        }

        if ($quota <= 0) {
            return false;
        }

        $count = \Modules\Screen\Models\Device::query()->count();

        return $count < $quota;
    }

    public function normalizeEstPosType(string $type): string
    {
        $type = strtolower(trim($type));

        return match ($type) {
            'kitchen screen', 'kitchen_screen', 'kitchen' => 'kitchen_screen',
            'cashier', 'pos' => 'cashier',
            'waiters', 'waiter' => 'waiters',
            default => $type,
        };
    }

    /**
     * @return list<string>
     */
    public function estPosTypeAliases(string $normalizedType): array
    {
        return match ($this->normalizeEstPosType($normalizedType)) {
            'kitchen_screen' => ['kitchen screen', 'kitchen_screen', 'kitchen'],
            'cashier' => ['cashier', 'pos'],
            'waiters' => ['waiters', 'waiter'],
            default => [$normalizedType],
        };
    }

    public function countEstPosDevices(string $type): int
    {
        $aliases = $this->estPosTypeAliases($type);

        return (int) \Modules\Establishment\Models\EstPos::query()
            ->whereIn('type', $aliases)
            ->count();
    }

    public function estPosQuotaForType(string $type, ?int $companyId = null): ?int
    {
        $normalized = $this->normalizeEstPosType($type);

        return match ($normalized) {
            'cashier' => $this->posDevicesQuota($companyId),
            'waiters' => $this->waiterSeatsQuota($companyId),
            'kitchen_screen' => $this->kitchenDevicesQuota($companyId),
            default => 0,
        };
    }

    public function canAddEstPosDevice(string $type, ?int $companyId = null): bool
    {
        $normalized = $this->normalizeEstPosType($type);
        $meta = self::EST_POS_TYPE_MAP[$normalized] ?? null;

        if (! $meta) {
            return false;
        }

        $state = $this->forCompany($companyId);

        if ($state['legacy']) {
            return true;
        }

        if (! in_array($meta['module'], $state['modules'], true)) {
            return false;
        }

        $quota = $this->estPosQuotaForType($normalized, $companyId);

        if ($quota === null) {
            return true;
        }

        if ($quota <= 0) {
            return false;
        }

        return $this->countEstPosDevices($normalized) < $quota;
    }

    /**
     * Snapshot used by the devices UI to hide/disable types over quota.
     *
     * @return array{
     *     legacy: bool,
     *     types: array<string, array{
     *         allowed: bool,
     *         module: string,
     *         module_ok: bool,
     *         quota: int|null,
     *         used: int,
     *         remaining: int|null,
     *         label: string
     *     }>
     * }
     */
    public function estPosEntitlementSnapshot(?int $companyId = null): array
    {
        $state = $this->forCompany($companyId);
        $localeIsAr = app()->getLocale() === 'ar';
        $types = [];

        foreach (self::EST_POS_TYPE_MAP as $type => $meta) {
            $moduleOk = $state['legacy'] || in_array($meta['module'], $state['modules'], true);
            $quota = $this->estPosQuotaForType($type, $companyId);
            $used = $this->countEstPosDevices($type);
            $remaining = $quota === null ? null : max(0, $quota - $used);
            $allowed = $state['legacy']
                || ($moduleOk && ($quota === null || ($quota > 0 && $used < $quota)));

            $types[$type] = [
                'allowed' => $allowed,
                'module' => $meta['module'],
                'module_ok' => $moduleOk,
                'quota' => $quota,
                'used' => $used,
                'remaining' => $remaining,
                'label' => $localeIsAr ? $meta['label_ar'] : $meta['label_en'],
            ];
        }

        return [
            'legacy' => (bool) $state['legacy'],
            'types' => $types,
        ];
    }

    public function estPosDenialMessage(string $type, ?int $companyId = null): string
    {
        $normalized = $this->normalizeEstPosType($type);
        $meta = self::EST_POS_TYPE_MAP[$normalized] ?? null;
        $localeIsAr = app()->getLocale() === 'ar';

        if (! $meta) {
            return $localeIsAr
                ? 'نوع الجهاز غير مدعوم في الاشتراك.'
                : 'This device type is not supported by the subscription.';
        }

        $state = $this->forCompany($companyId);
        $label = $localeIsAr ? $meta['label_ar'] : $meta['label_en'];

        if (! $state['legacy'] && ! in_array($meta['module'], $state['modules'], true)) {
            return $localeIsAr
                ? "وحدة «{$label}» غير مشمولة في باقتك. رقِّ الاشتراك لإضافتها من إدارة الاشتراك."
                : "The «{$label}» module is not in your plan. Upgrade from subscription management.";
        }

        $quota = $this->estPosQuotaForType($normalized, $companyId) ?? 0;
        $used = $this->countEstPosDevices($normalized);
        $addonUrl = url('/subscription/manage?mode=addon');

        return $localeIsAr
            ? "وصلت للحد الأقصى من {$label} في باقتك ({$used}/{$quota}). اشترِ حصصاً إضافية دون إعادة دفع الوحدات: {$addonUrl}"
            : "You reached your {$label} limit ({$used}/{$quota}). Buy extra quotas without re-paying modules: {$addonUrl}";
    }

    public function screenDevicesDenialMessage(?int $companyId = null): string
    {
        $localeIsAr = app()->getLocale() === 'ar';
        $quota = $this->screenDevicesQuota($companyId) ?? 0;
        $used = \Modules\Screen\Models\Device::query()->count();
        $addonUrl = url('/subscription/manage?mode=addon');

        if ($quota <= 0) {
            return $localeIsAr
                ? "تطبيق الشاشات غير مشمول في باقتك. رقِّ الاشتراك من إدارة الاشتراك."
                : 'Digital screens are not in your plan. Upgrade from subscription management.';
        }

        return $localeIsAr
            ? "وصلت للحد الأقصى من أجهزة الشاشات ({$used}/{$quota}). اشترِ حصصاً إضافية: {$addonUrl}"
            : "You reached your screen devices limit ({$used}/{$quota}). Buy extras: {$addonUrl}";
    }

    public function denies(string|array $moduleKeys, ?int $companyId = null): bool
    {
        return ! $this->allows($moduleKeys, $companyId);
    }

    public function menuAllowed(?string $menuName, ?int $companyId = null): bool
    {
        if ($menuName === null || $menuName === '') {
            return true;
        }

        if (in_array($menuName, config('entitlements.always_menu_keys', []), true)) {
            return true;
        }

        // Reports hub only makes sense with at least one data-producing module.
        if ($menuName === 'reports_module') {
            if ($this->denies('reports', $companyId)) {
                return false;
            }

            return $this->allows([
                'finance_business',
                'sales',
                'purchases',
                'inventory',
                'manufacturing',
                'cashier_pos',
                'accounting',
            ], $companyId);
        }

        $map = config('entitlements.menu_entitlements', []);

        if (! array_key_exists($menuName, $map)) {
            return true;
        }

        return $this->allows($map[$menuName], $companyId);
    }

    public function settingAllowed(string $section, ?int $companyId = null): bool
    {
        $map = config('entitlements.settings_sections', []);

        if (! array_key_exists($section, $map)) {
            return true;
        }

        return $this->allows($map[$section], $companyId);
    }

    public function prefixTypeAllowed(?string $prefixType, ?int $companyId = null): bool
    {
        if ($prefixType === null || $prefixType === '') {
            return true;
        }

        $map = config('entitlements.settings_prefix_entitlements', []);

        if (! array_key_exists($prefixType, $map)) {
            return true;
        }

        return $this->allows($map[$prefixType], $companyId);
    }

    public function notificationTypeAllowed(?string $notificationType, ?int $companyId = null): bool
    {
        if ($notificationType === null || $notificationType === '') {
            return true;
        }

        $map = config('entitlements.settings_notification_entitlements', []);

        if (! array_key_exists($notificationType, $map)) {
            return $this->settingAllowed('notifications', $companyId);
        }

        return $this->allows($map[$notificationType], $companyId);
    }

    /**
     * Map a shared Transaction.type to the commercial module that may access it.
     *
     * @return string|list<string>|null null = no commercial gate (platform/shared)
     */
    public function moduleForTransactionType(?string $type): string|array|null
    {
        $type = (string) $type;

        return match ($type) {
            'sell', 'sell-return', 'quotation' => 'sales',
            'purchases', 'purchases-return', 'purchases-order', 'purchase', 'purchase-order' => 'purchases',
            default => null,
        };
    }

    public function transactionTypeAllowed(?string $type, ?int $companyId = null): bool
    {
        $required = $this->moduleForTransactionType($type);

        if ($required === null) {
            return true;
        }

        return $this->allows($required, $companyId);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>|iterable<int, object>  $prefixes
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function filterPrefixes(iterable $prefixes, ?int $companyId = null)
    {
        return collect($prefixes)->filter(
            fn ($prefix) => $this->prefixTypeAllowed($prefix->type ?? null, $companyId)
        )->values();
    }

    /**
     * @return list<string>
     */
    public function entitledModules(?int $companyId = null): array
    {
        $state = $this->forCompany($companyId);

        if ($state['legacy']) {
            return ['*'];
        }

        return $state['modules'];
    }

    public function apiPathAllowed(string $path, ?int $companyId = null): bool
    {
        $path = ltrim($path, '/');
        $map = config('entitlements.api_entitlements', []);
        $required = null;
        $bestLength = -1;

        foreach ($map as $prefix => $module) {
            $prefix = ltrim((string) $prefix, '/');
            if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix))) {
                $length = strlen($prefix);
                if ($length > $bestLength) {
                    $bestLength = $length;
                    $required = $module;
                }
            }
        }

        if ($required === null) {
            return true;
        }

        return $this->allows($required, $companyId);
    }

    public function forgetMemo(?int $companyId = null): void
    {
        if ($companyId === null) {
            $this->memo = [];

            return;
        }

        unset($this->memo[$companyId]);
    }

    protected function centralConnection(): string
    {
        $connection = (string) config('tenancy.database.central_connection', 'central');

        if ($connection !== '' && config("database.connections.{$connection}")) {
            return $connection;
        }

        return 'mysql';
    }

    /**
     * @return array{
     *     modules: list<string>,
     *     employees_quota: null,
     *     establishments_quota: null,
     *     screen_devices_quota: null,
     *     pos_devices_quota: null,
     *     waiter_seats_quota: null,
     *     kitchen_devices_quota: null,
     *     legacy: bool
     * }
     */
    protected function empty(bool $legacy): array
    {
        return [
            'modules' => $legacy ? ['*'] : ['platform'],
            'employees_quota' => null,
            'establishments_quota' => null,
            'screen_devices_quota' => null,
            'pos_devices_quota' => null,
            'waiter_seats_quota' => null,
            'kitchen_devices_quota' => null,
            'legacy' => $legacy,
        ];
    }
}
