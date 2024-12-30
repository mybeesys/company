<div id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false"
    class="app-sidebar-menu-primary menu menu-column menu-rounded menu-sub-indention menu-state-bullet-primary px-0 mb-5">
    @foreach (config('menu') as $menuItem)
        @php
            $visibleSubmenuItems = collect($menuItem['subMenu'])->filter(function ($submenuItem) {
                if (!array_key_exists('subMenu', $submenuItem)) {
                    if (!array_key_exists('permission', $submenuItem)) {
                        return true;
                    }

                    return is_array($submenuItem['permission'])
                        ? collect($submenuItem['permission'])->contains(
                            fn($permission) => auth()->user()->hasDashboardPermission($permission),
                        )
                        : auth()
                            ->user()
                            ->hasDashboardPermission($submenuItem['permission']);
                } else {
                    return collect($submenuItem['subMenu'])->contains(function ($item) {
                        if (!array_key_exists('permission', $item)) {
                            return true;
                        }

                        return is_array($item['permission'])
                            ? collect($item['permission'])->contains(
                                fn($permission) => auth()->user()->hasDashboardPermission($permission),
                            )
                            : auth()
                                ->user()
                                ->hasDashboardPermission($item['permission']);
                    });
                }
            });

            $isSubmenuActive = $visibleSubmenuItems->contains(
                fn($submenuItem) => request()->is($submenuItem['url']) || request()->is($submenuItem['url'] . '/*'),
            );
        @endphp

        {{-- @if ($visibleSubmenuItems->isNotEmpty()) --}}
        <x-sidebar.main-menu :isSubmenuActive=$isSubmenuActive>
            <x-sidebar.menu-link :name="$menuItem['name']" :icon="$menuItem['icon']" :subMenuCount="/* $visibleSubmenuItems->count() */1" />
            <x-sidebar.submenu>
                @foreach ($menuItem['subMenu'] as $submenuItem)
                    @if (!array_key_exists('subMenu', $submenuItem))
                        @if (array_key_exists('permission', $submenuItem))
                            @php
                                $hasPermission = is_array($submenuItem['permission'])
                                    ? collect($submenuItem['permission'])->contains(
                                        fn($permission) => auth()->user()->hasDashboardPermission($permission),
                                    )
                                    : auth()
                                        ->user()
                                        ->hasDashboardPermission($submenuItem['permission']);
                            @endphp

                            {{-- @if ($hasPermission)
                                    <x-sidebar.menu-item :url="$submenuItem['url']" :name="$submenuItem['name']" />
                                @endif --}}
                            <x-sidebar.menu-item :url="$submenuItem['url']" :name="$submenuItem['name']" />
                        @endif
                    @else
                        @php
                            $visibleSubsubmenuItems = collect($submenuItem['subMenu'])->filter(function ($item) {
                                if (!array_key_exists('permission', $item)) {
                                    return true;
                                }

                                return is_array($item['permission'])
                                    ? collect($item['permission'])->contains(
                                        fn($permission) => auth()->user()->hasDashboardPermission($permission),
                                    )
                                    : auth()
                                        ->user()
                                        ->hasDashboardPermission($item['permission']);
                            });

                            $isSubsubmenuActive = $visibleSubsubmenuItems->contains(
                                fn($item) => request()->is($item['url']) || request()->is($item['url'] . '/*'),
                            );
                        @endphp

                        @if ($visibleSubsubmenuItems->isNotEmpty())
                            <x-sidebar.main-menu :isSubmenuActive=$isSubsubmenuActive>
                                <x-sidebar.menu-link :name="$submenuItem['name']" :subMenuCount="/* $visibleSubsubmenuItems->count() */1" />
                                <x-sidebar.submenu>
                                    @foreach ($submenuItem['subMenu'] as $item)
                                        @if (array_key_exists('permission', $item))
                                            @php
                                                $hasPermission = is_array($item['permission'])
                                                    ? collect($item['permission'])->contains(
                                                        fn($permission) => auth()
                                                            ->user()
                                                            ->hasDashboardPermission($permission),
                                                    )
                                                    : auth()
                                                        ->user()
                                                        ->hasDashboardPermission($item['permission']);
                                            @endphp

                                            {{-- @if ($hasPermission)
                                                    <x-sidebar.menu-item :url="$item['url']" :name="$item['name']" />
                                                @endif --}}
                                        @endif
                                        <x-sidebar.menu-item :url="$item['url']" :name="$item['name']" />
                                    @endforeach
                                </x-sidebar.submenu>
                            </x-sidebar.main-menu>
                        @endif
                    @endif
                @endforeach
            </x-sidebar.submenu>
        </x-sidebar.main-menu>
        {{-- @endif --}}
    @endforeach
</div>
