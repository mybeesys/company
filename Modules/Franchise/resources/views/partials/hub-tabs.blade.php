@php
    /** @var \Modules\Franchise\Services\FranchiseHubService $hubService */
    $hubService = app(\Modules\Franchise\Services\FranchiseHubService::class);
    $franchiseTabs = $hubService->visibleTabs();

    $currentRoute = optional(request()->route())->getName();
    $activeFranchiseTab = null;

    foreach ($franchiseTabs as $t) {
        if (($t['route'] ?? null) === $currentRoute) {
            $activeFranchiseTab = $t['id'];
            break;
        }
    }

    if (! $activeFranchiseTab) {
        $activeFranchiseTab = $hubService->resolveActiveTab($franchiseTabs, request());
    }
@endphp

<nav class="nav nav-pills franchise-hub-tabs mb-4" role="tablist">
    @foreach ($franchiseTabs as $tab)
        @php
            $labelKey = $tab['label'];
            $tabLabel = __($labelKey);
            if ($tabLabel === $labelKey) {
                $tabLabel = $tab['id'];
            }
            $tabUrl = route($tab['route']);
        @endphp
        <a href="{{ $tabUrl }}"
            class="nav-link {{ $activeFranchiseTab === $tab['id'] ? 'active' : '' }}"
            role="tab"
            aria-selected="{{ $activeFranchiseTab === $tab['id'] ? 'true' : 'false' }}">
            <i class="{{ $tab['icon'] }} me-1 opacity-75"></i>{{ $tabLabel }}
        </a>
    @endforeach
</nav>

<style>
    .franchise-hub-tabs {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #eef1f7;
        padding: 6px 8px;
        box-shadow: 0 2px 12px rgba(62, 57, 107, 0.05);
        gap: 4px;
        flex-wrap: nowrap;
        overflow-x: auto;
        scrollbar-width: thin;
    }
    .franchise-hub-tabs .nav-link {
        white-space: nowrap;
        border-radius: 8px;
        color: #5e6278;
        font-weight: 600;
        font-size: 13px;
        padding: 8px 14px;
        border: 0;
        margin: 0;
    }
    .franchise-hub-tabs .nav-link.active {
        background: var(--bs-primary-light);
        color: var(--bs-primary);
    }
    .franchise-hub-tabs .nav-link:not(.active):hover {
        background: #f8f9fc;
        color: #181c32;
    }
</style>
