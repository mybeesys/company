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

    /**
     * Module sections only (embedded panels) — excludes the inline overview.
     *
     * @return list<array<string, mixed>>
     */
    public function visibleEmbedSections(?Authenticatable $user = null): array
    {
        return array_values(array_filter(
            $this->visibleTabs($user),
            static fn (array $tab): bool => ($tab['type'] ?? '') !== 'inline'
        ));
    }

    public function resolveActiveTab(array $visibleTabs, ?Request $request = null): string
    {
        $request = $request ?? request();
        $requested = $request->query('tab');

        // Support #section-sales style passed as tab query value.
        if (is_string($requested) && str_starts_with($requested, 'section-')) {
            $requested = substr($requested, strlen('section-'));
        }

        if ($requested && collect($visibleTabs)->contains(fn (array $t) => $t['id'] === $requested)) {
            return (string) $requested;
        }

        return (string) ($visibleTabs[0]['id'] ?? 'overview');
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

        if (is_array($permission)) {
            return collect($permission)->contains(
                fn (string $perm) => $user->hasDashboardPermission($perm)
            );
        }

        return $user->hasDashboardPermission($permission);
    }

    protected function prepareTab(array $tab): array
    {
        $params = $this->filterParams();

        if (($tab['type'] ?? '') === 'inline') {
            $tab['url'] = route('dashboard', $params);
            $tab['embed_url'] = null;
            $tab['section_id'] = 'section-'.$tab['id'];
            $tab['description_key'] = 'employee::main.dashboard_section_'.$tab['id'].'_desc';

            return $tab;
        }

        $tab['url'] = route($tab['route'], $params);
        $tab['embed_url'] = route($tab['route'], array_merge($params, ['embed' => 1]));
        $tab['section_id'] = 'section-'.$tab['id'];
        $tab['description_key'] = 'employee::main.dashboard_section_'.$tab['id'].'_desc';

        return $tab;
    }

    /**
     * Shared date / cost-center filters for hub + embeds.
     *
     * @return array<string, mixed>
     */
    public function filterParams(?Request $request = null): array
    {
        $request = $request ?? request();
        $params = [];

        if ($request->filled('start_date')) {
            $params['start_date'] = $request->input('start_date');
        }
        if ($request->filled('end_date')) {
            $params['end_date'] = $request->input('end_date');
        }

        $costCenters = $request->input('choose_cost_center_select');
        if (is_array($costCenters) && $costCenters !== []) {
            $params['choose_cost_center_select'] = array_values($costCenters);
        }

        return $params;
    }

    public function resolveActiveTabFromRoute(?Request $request = null): string
    {
        $request = $request ?? request();
        $routeName = $request->route()?->getName();

        foreach (config('dashboard_tabs', []) as $tab) {
            if (! empty($tab['route']) && $tab['route'] === $routeName) {
                return (string) $tab['id'];
            }
        }

        return 'overview';
    }

    /**
     * @deprecated Hub no longer redirects to module pages; kept for callers that may still reference it.
     */
    public function fullPageUrlForTab(string $tabId, array $visibleTabs): ?string
    {
        return null;
    }
}
