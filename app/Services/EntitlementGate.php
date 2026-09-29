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
     *     legacy: bool
     * }>
     */
    protected array $memo = [];

    /**
     * @return array{
     *     modules: list<string>,
     *     employees_quota: int|null,
     *     establishments_quota: int|null,
     *     screen_devices_quota: int|null,
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
            'legacy' => false,
        ];
    }

    public function allows(string|array $moduleKeys, ?int $companyId = null): bool
    {
        $state = $this->forCompany($companyId);

        if ($state['legacy']) {
            return true;
        }

        $keys = is_array($moduleKeys) ? $moduleKeys : [$moduleKeys];

        foreach ($keys as $key) {
            if ($key === 'platform' || in_array($key, $state['modules'], true)) {
                return true;
            }
        }

        return false;
    }

    public function screenDevicesQuota(?int $companyId = null): ?int
    {
        $state = $this->forCompany($companyId);

        if ($state['legacy']) {
            return null;
        }

        if (! in_array('digital_screens', $state['modules'], true)) {
            return 0;
        }

        return $state['screen_devices_quota'] ?? 0;
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
                'sales',
                'purchases',
                'inventory',
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
     * @return array{modules: list<string>, employees_quota: null, establishments_quota: null, screen_devices_quota: null, legacy: bool}
     */
    protected function empty(bool $legacy): array
    {
        return [
            'modules' => $legacy ? ['*'] : ['platform'],
            'employees_quota' => null,
            'establishments_quota' => null,
            'screen_devices_quota' => null,
            'legacy' => $legacy,
        ];
    }
}
