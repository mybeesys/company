@unless(request()->boolean('embed'))
    @inject('dashboardHubService', 'Modules\Employee\Services\DashboardHubService')
    @php
        $dashboardTabs = $dashboardHubService->visibleTabs();
        $activeDashboardTab = $activeDashboardTab ?? $dashboardHubService->resolveActiveTabFromRoute();
    @endphp
    @if (count($dashboardTabs) > 1)
        <nav class="nav nav-pills dashboard-hub-tabs mb-4" id="dashboardHubTabs" role="tablist">
            @foreach ($dashboardTabs as $tab)
                @php
                    $labelKey = $tab['label'];
                    $tabLabel = __($labelKey);
                    if ($tabLabel === $labelKey) {
                        $tabLabel = $tab['id'];
                    }
                    $isActive = $activeDashboardTab === $tab['id'];
                    $tabUrl = $tab['url'] ?? route('dashboard');
                @endphp
                <a href="{{ $tabUrl }}"
                    class="nav-link {{ $isActive ? 'active' : '' }}"
                    role="tab"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}">
                    <i class="{{ $tab['icon'] }} me-1 opacity-75"></i>{{ $tabLabel }}
                </a>
            @endforeach
        </nav>
    @endif
@endunless
