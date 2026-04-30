<div id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false"
    class="app-sidebar-menu-primary menu menu-column menu-rounded menu-sub-indention menu-state-bullet-primary px-0 mb-5">
    @php
        $isPeriodicPolicyEnabled = \Modules\General\Models\Setting::isPeriodicInventory();
    @endphp
    @foreach (config('menu') as $menuItem)
    @php
    $hasMenuPermission = function ($permission) {
    if (!isset($permission) || $permission === '' || $permission === null) {
    return true;
    }

    return is_array($permission)
    ? collect($permission)->contains(fn($perm) => auth()->user()->hasDashboardPermission($perm))
    : auth()->user()->hasDashboardPermission($permission);
    };

    $visibleSubmenuItems = collect($menuItem['subMenu'])->filter(function ($submenuItem) {
    $hasMenuPermission = function ($permission) {
    if (!isset($permission) || $permission === '' || $permission === null) {
    return true;
    }

    return is_array($permission)
    ? collect($permission)->contains(fn($perm) => auth()->user()->hasDashboardPermission($perm))
    : auth()->user()->hasDashboardPermission($permission);
    };

    if (!array_key_exists('subMenu', $submenuItem)) {
    return $hasMenuPermission($submenuItem['permission'] ?? null);
    } else {
    return collect($submenuItem['subMenu'])->contains(function ($item) {
    if (!array_key_exists('permission', $item) || $item['permission'] === '' || $item['permission'] === null) {
    return true;
    }

    return is_array($item['permission'])
    ? collect($item['permission'])->contains(fn($permission) => auth()->user()->hasDashboardPermission($permission))
    : auth()->user()->hasDashboardPermission($item['permission']);
    });
    }
    });

    $isSubmenuActive = $visibleSubmenuItems->contains(
    fn($submenuItem) => request()->is($submenuItem['url']) || request()->is($submenuItem['url'] . '/*')
    );
    @endphp

    @if ($visibleSubmenuItems->isNotEmpty() || $hasMenuPermission($menuItem['permission'] ?? null))
    <x-sidebar.main-menu :isSubmenuActive="$isSubmenuActive">
        @if ($visibleSubmenuItems->isNotEmpty())
            <x-sidebar.menu-link :name="$menuItem['name']" :icon="$menuItem['icon']" :subMenuCount="1" />
        <x-sidebar.submenu>
            @foreach ($menuItem['subMenu'] as $submenuItem)
            @if (!array_key_exists('subMenu', $submenuItem))
            @if (array_key_exists('permission', $submenuItem))
            @php
            $hasPermission = (!isset($submenuItem['permission']) || $submenuItem['permission'] === '' || $submenuItem['permission'] === null)
            ? true
            : (is_array($submenuItem['permission'])
            ? collect($submenuItem['permission'])->contains(fn($permission) => auth()->user()->hasDashboardPermission($permission))
            : auth()->user()->hasDashboardPermission($submenuItem['permission']));
            @endphp

            @if ($hasPermission)
                @php
                    $isPeriodicMenuItem = ($submenuItem['name'] ?? '') === 'periodic';
                @endphp
                @if ($isPeriodicMenuItem && !$isPeriodicPolicyEnabled)
                    <div class="menu-item">
                        <div class="menu-link disabled periodic-policy-disabled-link d-flex align-items-center justify-content-between gap-2"
                            data-bs-toggle="tooltip" data-bs-placement="left"
                            title="@lang('general.periodic_inventory_requires_periodic_policy')">
                            <div class="d-flex align-items-center">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title fs-7">{{ __('menuItemLang.periodic') }}</span>
                                <span class="badge badge-light-warning text-warning-emphasis ms-2">@lang('general.policy_locked')</span>
                            </div>
                            <a href="{{ url('/general-setting') }}" class="btn btn-sm btn-light-primary py-1 px-2 periodic-policy-open-settings">
                                @lang('general.open')
                            </a>
                        </div>
                    </div>
                @else
                    <x-sidebar.menu-item :url="$submenuItem['url']" :name="$submenuItem['name']" />
                @endif
            @endif
            @endif
            @else
            @php
            $visibleSubsubmenuItems = collect($submenuItem['subMenu'])->filter(function ($item) {
            if (!array_key_exists('permission', $item)) {
            return true;
            }

            return is_array($item['permission'])
            ? collect($item['permission'])->contains(fn($permission) => auth()->user()->hasDashboardPermission($permission))
            : auth()->user()->hasDashboardPermission($item['permission']);
            });

            $isSubsubmenuActive = $visibleSubsubmenuItems->contains(
            fn($item) => request()->is($item['url']) || request()->is($item['url'] . '/*')
            );
            @endphp

            @if ($visibleSubsubmenuItems->isNotEmpty())
            <x-sidebar.main-menu :isSubmenuActive="$isSubsubmenuActive">
                <x-sidebar.menu-link :name="$submenuItem['name']" :subMenuCount="1" />
                <x-sidebar.submenu>
                    @foreach ($submenuItem['subMenu'] as $item)
                    @if (array_key_exists('permission', $item))
                    @php
                    $hasPermission = (!isset($item['permission']) || $item['permission'] === '' || $item['permission'] === null)
                    ? true
                    : (is_array($item['permission'])
                    ? collect($item['permission'])->contains(fn($permission) => auth()->user()->hasDashboardPermission($permission))
                    : auth()->user()->hasDashboardPermission($item['permission']));
                    @endphp

                    @if ($hasPermission)
                    <x-sidebar.menu-item :url="$item['url']" :name="$item['name']" />
                    @endif
                    @endif
                    @endforeach
                </x-sidebar.submenu>
            </x-sidebar.main-menu>
            @endif

            @endif
            @endforeach
        </x-sidebar.submenu>
        @endif
          @if ($visibleSubmenuItems->isEmpty())
                <x-sidebar.main-menu-item :url="$menuItem['url']" :icon="$menuItem['icon']" :name="$menuItem['name']" />
            @endif
    </x-sidebar.main-menu>
    @endif

    @endforeach
</div>
<style>
    .periodic-policy-disabled-link {
        opacity: .8;
        cursor: not-allowed;
        pointer-events: auto;
    }

    .periodic-policy-open-settings {
        pointer-events: auto;
        z-index: 1;
    }
</style>
