<?php

namespace Modules\Employee\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class DashboardHubService
{
    public function visibleTabs(?Authenticatable $user = null): array
    {
        $user = $user ?? auth()->user();
        $tabs = [];

        foreach (config('dashboard_tabs', []) as $tab) {
            if ($this->userCanSeeTab($user, $tab)) {
                $tabs[] = $this->prepareTab($tab);
            }
        }

        return $tabs;
    }

    public function resolveActiveTab(array $visibleTabs, ?Request $request = null): string
    {
        $request = $request ?? request();
        $requested = $request->query('tab');

        if ($requested && collect($visibleTabs)->contains(fn (array $t) => $t['id'] === $requested)) {
            return (string) $requested;
        }

        return (string) ($visibleTabs[0]['id'] ?? 'overview');
    }

    protected function userCanSeeTab(?Authenticatable $user, array $tab): bool
    {
        if (($tab['type'] ?? '') === 'inline') {
            return true;
        }

        $permission = $tab['permission'] ?? null;

        if ($permission === null || $permission === '') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if (is_array($permission)) {
            return collect($permission)->contains(
                fn (string $perm) => $user->hasDashboardPermission($perm)
            );
        }

        return $user->hasDashboardPermission($permission);
    }

    protected function prepareTab(array $tab): array
    {
        if (($tab['type'] ?? '') === 'inline') {
            return $tab;
        }

        $params = array_merge(
            request()->only(['start_date', 'end_date', 'choose_cost_center_select']),
            ['embed' => 1]
        );

        $tab['embed_url'] = route($tab['route'], $params);

        return $tab;
    }
}
