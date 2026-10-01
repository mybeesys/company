<div id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false"
    class="app-sidebar-menu-primary menu menu-column menu-rounded menu-sub-indention menu-state-bullet-primary px-0 mb-5">
    @php
        $isPeriodicPolicyEnabled = \Modules\General\Models\Setting::isPeriodicInventory();
        $upgradeUrl = route('subscription.manage');

        $menuUrlIsActive = function (?string $url): bool {
            if ($url === null || $url === '') {
                return false;
            }

            return request()->is($url) || request()->is($url.'/*');
        };

        $menuItemIsActive = function (array $item) use (&$menuItemIsActive, $menuUrlIsActive): bool {
            if (! empty($item['name'] ?? '') && menu_hub_is_active($item['name'])) {
                return true;
            }

            if (! empty($item['url'] ?? '') && $menuUrlIsActive($item['url'])) {
                return true;
            }

            foreach ($item['subMenu'] ?? [] as $child) {
                if ($menuItemIsActive($child)) {
                    return true;
                }
            }

            return false;
        };

        $hasMenuPermission = function ($permission): bool {
            if (! isset($permission) || $permission === '' || $permission === null) {
                return true;
            }

            return is_array($permission)
                ? collect($permission)->contains(fn ($perm) => auth()->user()->hasDashboardPermission($perm))
                : auth()->user()->hasDashboardPermission($permission);
        };

        $hasMenuEntitlement = function (array $item): bool {
            return tenant_menu_entitled($item['name'] ?? null);
        };

        $isMenuLocked = function (array $item) use ($hasMenuEntitlement): bool {
            return ! $hasMenuEntitlement($item);
        };
    @endphp

    @foreach (config('menu') as $menuItem)
        @php
            $parentLocked = $isMenuLocked($menuItem);
        @endphp

        {{-- Locked commercial module: visible teaser + golden upgrade tag --}}
        @if ($parentLocked)
            <x-sidebar.locked-main-menu-item
                :name="$menuItem['name']"
                :icon="$menuItem['icon'] ?? null"
                :upgradeUrl="$upgradeUrl"
            />
            @continue
        @endif

        @php
            $visibleSubmenuItems = collect($menuItem['subMenu'] ?? [])->filter(function ($submenuItem) use ($hasMenuPermission, $isMenuLocked) {
                // Locked children stay visible for upsell.
                if ($isMenuLocked($submenuItem)) {
                    return true;
                }

                if (! array_key_exists('subMenu', $submenuItem)) {
                    return $hasMenuPermission($submenuItem['permission'] ?? null);
                }

                return collect($submenuItem['subMenu'])->contains(function ($item) use ($hasMenuPermission, $isMenuLocked) {
                    if ($isMenuLocked($item)) {
                        return true;
                    }

                    return $hasMenuPermission($item['permission'] ?? null);
                });
            });

            $isSubmenuActive = $visibleSubmenuItems->contains(
                fn ($submenuItem) => $menuItemIsActive($submenuItem)
            );
        @endphp

        @if ($visibleSubmenuItems->isNotEmpty() || (empty($menuItem['subMenu']) && $hasMenuPermission($menuItem['permission'] ?? null)))
            @if ($visibleSubmenuItems->isEmpty())
                <x-sidebar.main-menu-item :url="$menuItem['url']" :icon="$menuItem['icon']" :name="$menuItem['name']" />
            @else
                <x-sidebar.main-menu :isSubmenuActive="$isSubmenuActive">
                    <x-sidebar.menu-link :name="$menuItem['name']" :icon="$menuItem['icon']" :subMenuCount="1" />
                    <x-sidebar.submenu>
                        @foreach ($menuItem['subMenu'] as $submenuItem)
                            @php
                                $subLocked = $isMenuLocked($submenuItem);
                            @endphp

                            @if (! array_key_exists('subMenu', $submenuItem))
                                @if ($subLocked)
                                    <x-sidebar.locked-menu-item
                                        :name="$submenuItem['name']"
                                        :upgradeUrl="$upgradeUrl"
                                    />
                                    @continue
                                @endif

                                @if (! array_key_exists('permission', $submenuItem) || $hasMenuPermission($submenuItem['permission'] ?? null))
                                    @php
                                        $isPeriodicMenuItem = ($submenuItem['name'] ?? '') === 'periodic';
                                    @endphp
                                    @if ($isPeriodicMenuItem && ! $isPeriodicPolicyEnabled)
                                        <div class="menu-item">
                                            <div class="menu-link disabled periodic-policy-disabled-link d-flex align-items-center justify-content-between gap-2"
                                                data-bs-toggle="tooltip" data-bs-placement="left"
                                                title="@lang('general.periodic_inventory_requires_periodic_policy')">
                                                <div class="d-flex align-items-center">
                                                    <span class="menu-bullet">
                                                        <span class="bullet bullet-dot"></span>
                                                    </span>
                                                    <span class="menu-title fs-7">{{ __('menuItemLang.periodic') }}</span>
                                                    <span class="ms-2 text-warning"><i class="ki-outline ki-lock-2 fs-6"></i></span>
                                                </div>
                                                <a href="{{ url('/general-setting#inventory_policy_tab') }}" class="btn btn-sm btn-light-primary py-1 px-2 periodic-policy-open-settings">
                                                    @lang('general.open')
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <x-sidebar.menu-item :url="$submenuItem['url']" :name="$submenuItem['name']" />
                                    @endif
                                @endif
                            @else
                                @php
                                    $visibleSubsubmenuItems = collect($submenuItem['subMenu'])->filter(function ($item) use ($hasMenuPermission, $isMenuLocked) {
                                        if ($isMenuLocked($item)) {
                                            return true;
                                        }

                                        return $hasMenuPermission($item['permission'] ?? null);
                                    });

                                    $isSubsubmenuActive = $visibleSubsubmenuItems->contains(
                                        fn ($item) => $menuItemIsActive($item)
                                    );
                                @endphp

                                @if ($subLocked)
                                    <x-sidebar.locked-menu-item
                                        :name="$submenuItem['name']"
                                        :upgradeUrl="$upgradeUrl"
                                    />
                                @elseif ($visibleSubsubmenuItems->isNotEmpty())
                                    <x-sidebar.main-menu :isSubmenuActive="$isSubsubmenuActive">
                                        <x-sidebar.menu-link :name="$submenuItem['name']" :subMenuCount="1" />
                                        <x-sidebar.submenu>
                                            @foreach ($submenuItem['subMenu'] as $item)
                                                @if ($isMenuLocked($item))
                                                    <x-sidebar.locked-menu-item
                                                        :name="$item['name']"
                                                        :upgradeUrl="$upgradeUrl"
                                                    />
                                                @elseif ($hasMenuPermission($item['permission'] ?? null))
                                                    <x-sidebar.menu-item :url="$item['url']" :name="$item['name']" />
                                                @endif
                                            @endforeach
                                        </x-sidebar.submenu>
                                    </x-sidebar.main-menu>
                                @endif
                            @endif
                        @endforeach
                    </x-sidebar.submenu>
                </x-sidebar.main-menu>
            @endif
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

    .entitlement-locked-link {
        display: flex !important;
        align-items: center;
        gap: 0.25rem;
        opacity: 0.82;
        cursor: pointer;
        pointer-events: auto;
        position: relative;
        text-decoration: none !important;
    }

    .entitlement-locked-link:hover {
        opacity: 1;
        background: rgba(235, 184, 30, 0.08) !important;
    }

    .entitlement-locked-icon {
        position: relative;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
    }

    .entitlement-locked-icon > i,
    .entitlement-locked-icon > .bullet {
        opacity: 0.55;
        filter: grayscale(0.35);
    }

    .entitlement-locked-badge {
        position: absolute;
        top: -0.15rem;
        inset-inline-end: -0.2rem;
        width: 0.55rem;
        height: 0.62rem;
        background: linear-gradient(145deg, #f5d76e 0%, #ebb81e 48%, #b88912 100%);
        clip-path: polygon(25% 6%, 75% 6%, 100% 50%, 75% 94%, 25% 94%, 0 50%);
        box-shadow: 0 0 0 1.5px #fff, 0 1px 4px rgba(184, 137, 18, 0.45);
        pointer-events: none;
    }

    .entitlement-locked-title {
        color: #99a1b7 !important;
        flex: 1 1 auto;
        min-width: 0;
    }

    .entitlement-upgrade-tooltip {
        --bs-tooltip-bg: #1a1a1a;
        --bs-tooltip-color: #fffdf7;
        --bs-tooltip-opacity: 1;
        font-family: inherit;
        font-size: 0.75rem;
        font-weight: 600;
        max-width: 16rem;
        text-align: start;
        line-height: 1.45;
    }

    .entitlement-upgrade-tooltip .tooltip-inner {
        padding: 0.55rem 0.7rem;
        border-radius: 0.65rem;
        border: 1px solid rgba(235, 184, 30, 0.35);
        box-shadow: 0 10px 28px rgba(26, 26, 26, 0.18);
    }

    [data-kt-app-sidebar-minimize="on"] .entitlement-locked-badge {
        top: 0.05rem;
        inset-inline-end: 0.15rem;
        width: 0.5rem;
        height: 0.56rem;
    }

    [data-bs-theme="dark"] .entitlement-locked-title {
        color: #6d758a !important;
    }

    [data-bs-theme="dark"] .entitlement-locked-badge {
        box-shadow: 0 0 0 1.5px #1e1e2d, 0 1px 4px rgba(184, 137, 18, 0.55);
    }

    [data-bs-theme="dark"] .entitlement-locked-link:hover {
        background: rgba(235, 184, 30, 0.14) !important;
    }
</style>
