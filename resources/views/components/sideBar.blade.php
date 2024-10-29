<!--begin::Sidebar menu-->
<div id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false"
    class="app-sidebar-menu-primary menu menu-column menu-rounded menu-sub-indention menu-state-bullet-primary px-0 mb-5">
    <!--begin:Menu item-->
    @foreach (config('menu') as $menuItem)
        @php
            $isSubmenuActive = collect($menuItem['subMenu'])->contains(function ($submenuItem) {
                return (request()->is($submenuItem['url']) || request()->is($submenuItem['url'] . '/*'));
            });
        @endphp
        <div data-kt-menu-trigger="click" @class(['menu-item here menu-accordion', 'show' => $isSubmenuActive])>
            <!--begin:Menu link-->
            <span class="menu-link">
                <span class="menu-icon">
                    <i class='{{ $menuItem['icon'] }}'></i>
                </span>
                <span class="menu-title">{{ __('menuItemLang.' . $menuItem['name']) }}</span>
                @if (count($menuItem['subMenu']) > 0)
                    <span class="menu-arrow"></span>
                @endif
            </span>
            <!--end:Menu link-->
            <!--begin:Menu sub-->
            <div class="menu-sub menu-sub-accordion">
                @foreach ($menuItem['subMenu'] as $submenuItem)
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a @class(['menu-link', 'active' => request()->is($submenuItem['url']) || request()->is($submenuItem['url'] . '/*')])  href='/{{ $submenuItem['url'] }}'>
                            <span class="menu-bullet">
                                <span class="bullet bullet-dot"></span>
                            </span>
                            <span class="menu-title">{{ __('menuItemLang.' . $submenuItem['name']) }}</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                @endforeach
            </div>
            <!--end:Menu sub-->
        </div>
        <!--end:Menu item-->
    @endforeach
    <!--end:Menu item-->
</div>
<!--end::Sidebar menu-->
