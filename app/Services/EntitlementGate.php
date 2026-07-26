<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EntitlementGate
{
    /**
     * @return array{
     *     modules: list<string>,
     *     employees_quota: int|null,
     *     establishments_quota: int|null,
     *     legacy: bool
     * }
     */
    public function forCompany(?int $companyId = null): array
    {
        $companyId = $companyId ?: (function_exists('get_company_id') ? get_company_id() : null);

        if (! $companyId) {
            return $this->empty(legacy: true);
        }

        return Cache::remember(
            "tenant_entitlements:{$companyId}",
            now()->addMinutes(10),
            function () use ($companyId) {
                $row = DB::connection('mysql')
                    ->table('company_entitlements')
                    ->where('company_id', $companyId)
                    ->first();

                if (! $row) {
                    return $this->empty(legacy: (bool) config('entitlements.legacy_unrestricted', true));
                }

                $modules = json_decode($row->modules ?? '[]', true);
                if (! is_array($modules)) {
                    $modules = [];
                }

                return [
                    'modules' => array_values(array_unique(array_merge(['platform'], $modules))),
                    'employees_quota' => (int) $row->employees_quota,
                    'establishments_quota' => (int) $row->establishments_quota,
                    'screen_devices_quota' => (int) ($row->screen_devices_quota ?? 0),
                    'legacy' => false,
                ];
            }
        );
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

        foreach ($map as $prefix => $module) {
            if (str_starts_with($path, ltrim($prefix, '/'))) {
                return $this->allows($module, $companyId);
            }
        }

        return true;
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
