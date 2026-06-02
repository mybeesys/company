<?php

namespace Modules\Franchise\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class FranchiseHubService
{
    public function visibleTabs(?Authenticatable $user = null): array
    {
        $user = $user ?? auth()->user();
        $tabs = [];

        foreach (config('franchise_hub_tabs', []) as $tab) {
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

        return (string) ($visibleTabs[0]['id'] ?? 'companies');
    }

    protected function userCanSeeTab(?Authenticatable $user, array $tab): bool
    {
        $permission = $tab['permission'] ?? null;

        if ($permission === null || $permission === '') {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $user->hasDashboardPermission($permission);
    }

    protected function prepareTab(array $tab): array
    {
        return $tab;
    }
}
