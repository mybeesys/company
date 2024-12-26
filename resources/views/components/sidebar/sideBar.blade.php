<div id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false"
    class="app-sidebar-menu-primary menu menu-column menu-rounded menu-sub-indention menu-state-bullet-primary px-0 mb-5">
    @foreach (config('menu') as $menuItem)
        @php
            $isSubmenuActive = collect($menuItem['subMenu'])->contains(
                fn($submenuItem) => request()->is($submenuItem['url']) || request()->is($submenuItem['url'] . '/*'),
            );
        @endphp
        <x-sidebar.main-menu :isSubmenuActive=$isSubmenuActive>
            <x-sidebar.menu-link :name="$menuItem['name']" :icon="$menuItem['icon']" :subMenuCount="count($menuItem['subMenu'])" />
            <x-sidebar.submenu>
                @foreach ($menuItem['subMenu'] as $submenuItem)
                    @if (!array_key_exists('subMenu', $submenuItem))
                        @if (array_key_exists('permission_model', $submenuItem))
                        <x-sidebar.menu-item :url="$submenuItem['url']" :name="$submenuItem['name']" />
                            {{-- @can('viewAny', $submenuItem['permission_model'])
                            @endcan --}}
                        @endif
                    @else
                        @php
                            $isSubsubmenuActive = collect($submenuItem['subMenu'])->contains(
                                fn($submenuItem) => request()->is($submenuItem['url']) ||
                                    request()->is($submenuItem['url'] . '/*'),
                            );
                        @endphp
                        <x-sidebar.main-menu :isSubmenuActive=$isSubsubmenuActive>
                            <x-sidebar.menu-link :name="$submenuItem['name']" :subMenuCount="true" />
                            <x-sidebar.submenu>
                                @foreach ($submenuItem['subMenu'] as $item)
                                    @if (array_key_exists('permission_model', $item))
                                    <x-sidebar.menu-item :url="$item['url']" :name="$item['name']" />
                                        {{-- @can('viewAny', $item['permission_model'])
                                        @endcan --}}
                                    @endif
                                @endforeach
                            </x-sidebar.submenu>
                        </x-sidebar.main-menu>
                    @endif
                @endforeach
            </x-sidebar.submenu>
        </x-sidebar.main-menu>
    @endforeach
</div>
