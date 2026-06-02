<!--begin::Navbar-->
@php
    $navbarDate = \Illuminate\Support\Carbon::now()->locale(app()->getLocale())->isoFormat('ddd D MMM YYYY');
    $hasQuickPermission = function ($permission) {
        if (!isset($permission) || $permission === '' || $permission === null) {
            return true;
        }
        return is_array($permission)
            ? collect($permission)->contains(fn($perm) => auth()->user()->hasDashboardPermission($perm))
            : auth()->user()->hasDashboardPermission($permission);
    };

    $quickLinks = collect([
        [
            'url' => '/dashboard',
            'label' => __('general.dashboard'),
            'icon' => 'ki-outline ki-home-2',
            'permission' => null,
        ],
        [
            'url' => '/payment-reports',
            'label' => __('menuItemLang.reports_module'),
            'icon' => 'ki-outline ki-chart-simple',
            'permission' => 'reports_module.all.show',
        ],
        [
            'url' => '/accounting-reports',
            'label' => __('general.accounting_reports'),
            'icon' => 'ki-outline ki-chart-line-up',
            'permission' => 'accountingReports.all.show',
        ],
        [
            'url' => '/dashboard?tab=inventory',
            'label' => __('general.inventory_dashboard'),
            'icon' => 'ki-outline ki-package',
            'permission' => 'inventory.dashboard.show',
        ],
        [
            'url' => '/dashboard?tab=sales',
            'label' => __('general.sales_dashboard'),
            'icon' => 'ki-outline ki-dollar',
            'permission' => 'sales.all.show',
        ],
        [
            'url' => '/main',
            'label' => __('general.screens'),
            'icon' => 'ki-outline ki-screen',
            'permission' => 'screen_module.all.show',
        ],
        [
            'url' => '/general-setting',
            'label' => __('menuItemLang.general_setting'),
            'icon' => 'ki-outline ki-setting-2',
            'permission' => 'setting.General setting.show',
        ],
    ])->filter(fn($link) => $hasQuickPermission($link['permission']))->values();
    $unreadCount = auth()->user()->unreadNotifications()->count();
@endphp
<div class="app-navbar app-navbar--compact flex-grow-1 d-flex align-items-center min-w-0 pe-lg-8 pe-3" id="kt_app_header_navbar">
    <div class="app-navbar-meta d-none d-lg-flex align-items-center gap-2 text-gray-600 min-w-0 me-2">
        <span class="d-inline-flex align-items-center gap-1 fs-8 fw-semibold text-muted navbar-meta-date">
            <i class="ki-outline ki-calendar fs-6 text-gray-500"></i>
            <span>{{ $navbarDate }}</span>
        </span>
    </div>
    <div class="app-navbar-actions d-flex align-items-center gap-1 gap-lg-2 flex-shrink-0 ms-auto">
    {{-- <div class="app-navbar-item d-flex align-items-stretch flex-lg-grow-1">
        <div id="kt_header_search" class="header-search d-flex align-items-center w-lg-350px"
            data-kt-search-keypress="true" data-kt-search-min-length="2" data-kt-search-enter="enter"
            data-kt-search-layout="menu" data-kt-search-responsive="true" data-kt-menu-trigger="auto"
            data-kt-menu-permanent="true" data-kt-menu-placement="{{ $menu_placement_y }}">
            <div data-kt-search-element="toggle" class="search-toggle-mobile d-flex d-lg-none align-items-center">
                <div class="d-flex">
                    <i class="ki-outline ki-magnifier fs-1 fs-1"></i>
                </div>
            </div>
            <form data-kt-search-element="form" class="d-none d-lg-block w-100 position-relative mb-5 mb-lg-0"
                autocomplete="off">
                <i
                    class="ki-outline ki-magnifier search-icon fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-5"></i>
                <input type="text" class="search-input form-control form-control border h-lg-45px ps-13"
                    name="search" value="" placeholder="Search..." data-kt-search-element="input" />
                <span class="search-spinner position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-5"
                    data-kt-search-element="spinner">
                    <span class="spinner-border h-15px w-15px align-middle text-gray-500"></span>
                </span>
                <span
                    class="search-reset btn btn-flush btn-active-color-primary position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-4"
                    data-kt-search-element="clear">
                    <i class="ki-outline ki-cross fs-2 fs-lg-1 me-0"></i>
                </span>
                <!--end::Reset-->
            </form>
            <div data-kt-search-element="content"
                class="menu menu-sub menu-sub-dropdown py-7 px-7 overflow-hidden w-300px w-md-350px">
                <div data-kt-search-element="wrapper">
            <div data-kt-search-element="results" class="d-none">
                        <div class="scroll-y mh-200px mh-lg-350px">
                            <h3 class="fs-5 text-muted m-0 pb-5" data-kt-search-element="category-title">Users</h3>
                            <h3 class="fs-5 text-muted m-0 pt-5 pb-5" data-kt-search-element="category-title">Projects
                            </h3>
                            <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                                <!--begin::Symbol-->
                                <div class="symbol symbol-40px me-4">
                                    <span class="symbol-label bg-light">
                                        <i class="ki-outline ki-notepad fs-2 text-primary"></i>
                                    </span>
                                </div>
                                <!--end::Symbol-->
                                <!--begin::Title-->
                                <div class="d-flex flex-column">
                                    <span class="fs-6 fw-semibold">Si-Fi Project by AU
                                        Themes</span>
                                    <span class="fs-7 fw-semibold text-muted">#45670</span>
                                </div>
                                <!--end::Title-->
                            </a>
                        </div>
                    </div>
            <div class="" data-kt-search-element="main">
                        <div class="d-flex flex-stack fw-semibold mb-4">
                            <span class="text-muted fs-6 me-2">Recently Searched:</span>
                        </div>
                        <div class="scroll-y mh-200px mh-lg-325px">
                            <div class="d-flex align-items-center mb-5">
                                <div class="symbol symbol-40px me-4">
                                    <span class="symbol-label bg-light">
                                        <i class="ki-outline ki-laptop fs-2 text-primary"></i>
                                    </span>
                                </div>
                                <div class="d-flex flex-column">
                                    <a href="#"
                                        class="fs-6 text-gray-800 text-hover-primary fw-semibold">BoomApp
                                        by Keenthemes</a>
                                    <span class="fs-7 text-muted fw-semibold">#45789</span>
                                </div>
                            </div>
                        </div>
                    </div>
            <div data-kt-search-element="empty" class="text-center d-none">
                        <div class="pt-10 pb-10">
                            <i class="ki-outline ki-search-list fs-4x opacity-50"></i>
                        </div>
                    </div>
            </div>
            </div>
        </div>
    </div> --}}
    <div class="app-navbar-item">
        <a href="{{ url('/dashboard') }}" class="btn btn-icon btn-custom btn-color-gray-600 btn-active-color-primary w-32px h-32px w-md-34px h-md-34px"
            title="{{ __('general.dashboard') }}" aria-label="{{ __('general.dashboard') }}">
            <i class="ki-outline ki-home-2 fs-3"></i>
        </a>
    </div>
    @if ($hasQuickPermission('setting.General setting.show'))
        <div class="app-navbar-item">
            <a href="{{ url('/general-setting') }}" class="btn btn-icon btn-custom btn-color-gray-600 btn-active-color-primary w-32px h-32px w-md-34px h-md-34px"
                title="{{ __('general.header_help_settings') }}" aria-label="{{ __('general.header_help_settings') }}">
                <i class="ki-outline ki-information-2 fs-3"></i>
            </a>
        </div>
    @endif
    <!--begin::Notifications-->
    <div class="app-navbar-item">
        <div class="btn btn-icon btn-custom btn-color-gray-600 btn-active-color-primary w-32px h-32px w-md-34px h-md-34px position-relative notification_btn"
            data-kt-menu-trigger="{default: 'click'}" data-kt-menu-attach="parent"
            data-kt-menu-placement="{{ $menu_placement_y }}">
            <i class="ki-outline ki-notification-on fs-3"></i>
            <span @class([
                'position-absolute top-0 start-100 translate-middle badge badge-circle badge-danger w-14px h-14px p-0 d-flex align-items-center justify-content-center read-notification-count',
                'd-none' => $unreadCount === 0,
            ]) style="font-size: 0.6rem;">{{ $unreadCount > 0 ? ($unreadCount > 9 ? '9+' : $unreadCount) : '' }}</span>
        </div>
        <div class="menu menu-sub menu-sub-dropdown menu-column w-350px w-lg-375px" data-kt-menu="true"
            id="kt_menu_notifications">
            <div class="d-flex flex-column bgi-no-repeat rounded-top"
                style="background-image:url('/assets/media/misc/menu-header-bg.jpg')">
                <h3 class="text-white fw-semibold px-5 my-6">@lang('general.notifications')
                    <span
                        class="fs-8 opacity-75 ps-1 notification-count">({{ auth()->user()->notifications->count() }})</span>
                </h3>
            </div>
            <div id="kt_topbar_notifications_1" role="tabpanel">
                @if (auth()->user()->notifications->isNotEmpty())
                    <div class="scroll-y mh-325px my-5 px-5">
                        @foreach (auth()->user()->notifications as $notification)
                            @if ($notification->data['body'] && $notification->data['title'])
                                <div @class([
                                    "d-flex flex-stack py-4 notification-body notification-{$notification->id}",
                                    'bg-secondary' => !$notification['read_at'],
                                    'rounded-top' => $loop->first,
                                    'rounded-bottom' => $loop->last,
                                ])>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-35px me-4">
                                            <span class="symbol-label bg-light-primary">
                                                <i @class([
                                                    'fs-2 text-primary',
                                                    'ki-outline ' . ($notification->data['icon'] ?? 'ki-notification'),
                                                ])></i>
                                                {{-- user-square abstract-28 information briefcase abstract-12 colors-square picture color-swatch purchase discount gear delivery notification information-5 --}}
                                            </span>
                                        </div>
                                        <div class="mb-0 me-2">
                                            <a href="#"
                                                class="fs-6 text-gray-800 text-hover-primary fw-bold">{{ $notification->data['title'] ?? '' }}</a>
                                            <div class="text-gray-500 fs-7">{{ $notification->data['body'] ?? '' }}
                                            </div>
                                        </div>
                                    </div>
                                    <span
                                        class="badge badge-light fs-8">{{ $notification->created_at->diffForHumans() }}</span>
                                    <button class="btn btn-sm btn-icon btn-active-color-primary show menu-dropdown"
                                        data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                        <i class="ki-solid ki-dots-vertical fs-2x"></i>
                                    </button>
                                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3 show"
                                        data-kt-menu="true" data-popper-placement="bottom-end"
                                        style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate3d(-145.6px, 175.2px, 0px);">
                                        <div class="menu-item px-3 my-1">
                                            <a data-id="{{ $notification['id'] }}"
                                                class="menu-link px-3 notification-delete-btn">@lang('employee::fields.delete')</a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="mh-325px my-5 px-8 d-flex mx-auto">
                        <span class="text-gray-500 mx-auto py-10">@lang('general.no_notifications')</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!--begin::User menu-->
    <div class="app-navbar-item" id="kt_header_user_menu_toggle">
        <!--begin::Menu wrapper-->
        <div class="cursor-pointer symbol symbol-circle symbol-35px"
            data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent"
            data-kt-menu-placement="{{ $menu_placement_y }}">
            <img src="{{ auth()->user()->image ? asset('storage/tenant' . tenancy()->tenant->id . '/' . auth()->user()->image) : url('/assets/media/avatars/blank.png') }}"
                alt="user" />
        </div>
        <!--begin::User account menu-->
        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-350px user-quick-menu"
            data-kt-menu="true">
            <!--begin::Menu item-->
            <div class="menu-item px-3">
                <div class="menu-content d-flex align-items-center px-3">
                    <div class="symbol symbol-50px me-5">
                        <img alt="Logo"
                            src="{{ auth()->user()->image ? asset('storage/tenant' . tenancy()->tenant->id . '/' . auth()->user()->image) : url('/assets/media/avatars/blank.png') }}" />
                    </div>
                    <div class="d-flex flex-column">
                        <div class="fw-bold d-flex align-items-center fs-5">{{ auth()->user()->user_name }}</div>
                        <a href="#"
                            class="fw-semibold text-muted text-hover-primary fs-7">{{ auth()->user()->email }}</a>
                    </div>
                </div>
            </div>
            <div class="separator my-2"></div>
            <div class="menu-item px-3">
                <div class="menu-content px-3 pb-2">
                    <div class="fs-8 text-uppercase fw-bold text-muted">@lang('general.quick_access')</div>
                </div>
            </div>
            @if ($quickLinks->isNotEmpty())
                <div class="menu-item px-3">
                    <div class="menu-content px-3">
                        <div class="user-shortcuts-grid">
                            @foreach ($quickLinks as $link)
                                <a href="{{ url($link['url']) }}" class="user-shortcut-item"
                                    data-quick-link-key="{{ md5($link['url']) }}">
                                    <i class="{{ $link['icon'] }} fs-3"></i>
                                    <span>{{ $link['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            <div class="separator my-2"></div>
            <div class="menu-item px-3">
                <div class="menu-content px-3 pb-2">
                    <div class="fs-8 text-uppercase fw-bold text-muted">@lang('general.workspace')</div>
                </div>
            </div>
            <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                data-kt-menu-placement="{{ $menu_placement_x }}" data-kt-menu-offset="-15px, 0">
                <a href="{{ url('/subscription') }}" class="menu-link px-5">
                    <span class="menu-title">@lang('general.subscriptions')</span>
                </a>
            </div>
            <!--end::Menu item-->
            <!--begin::Menu separator-->
            <div class="separator my-2"></div>
            <!--end::Menu separator-->
            <div class="menu-item px-3">
                <div class="menu-content px-3 pb-2">
                    <div class="fs-8 text-uppercase fw-bold text-muted">@lang('general.preferences')</div>
                </div>
            </div>
            <!--begin::Menu item-->
            <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                data-kt-menu-placement="{{ $menu_placement_x }}" data-kt-menu-offset="-15px, 0">
                <a href="#" class="menu-link px-5">
                    <span class="menu-title position-relative">@lang('general.mode')
                        <span class="ms-5 position-absolute translate-middle-y top-50 end-0">
                            <i class="ki-outline ki-night-day theme-light-show fs-2"></i>
                            <i class="ki-outline ki-moon theme-dark-show fs-2"></i>
                        </span></span>
                </a>
                <!--begin::Menu-->
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px"
                    data-kt-menu="true" data-kt-element="theme-mode-menu">
                    <!--begin::Menu item-->
                    <div class="menu-item px-3 my-0">
                        <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                            <span class="menu-icon" data-kt-element="icon">
                                <i class="ki-outline ki-night-day fs-2"></i>
                            </span>
                            <span class="menu-title">@lang('general.light')</span>
                        </a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3 my-0">
                        <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                            <span class="menu-icon" data-kt-element="icon">
                                <i class="ki-outline ki-moon fs-2"></i>
                            </span>
                            <span class="menu-title">@lang('general.dark')</span>
                        </a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3 my-0">
                        <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                            <span class="menu-icon" data-kt-element="icon">
                                <i class="ki-outline ki-screen fs-2"></i>
                            </span>
                            <span class="menu-title">@lang('general.system')</span>
                        </a>
                    </div>
                    <!--end::Menu item-->
                </div>
                <!--end::Menu-->
            </div>
            <!--end::Menu item-->
            <!--begin::Menu item-->
            <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                data-kt-menu-placement="{{ $menu_placement_x }}" data-kt-menu-offset="-15px, 0">
                <a href="#" class="menu-link px-5">
                    <span class="menu-title position-relative">@lang('lang.Language')
                        <span
                            class="fs-8 rounded bg-light px-3 py-2 position-absolute translate-middle-y top-50 end-0">
                            {{ session('locale') == 'ar' ? 'العربية' : 'English' }}

                            <img class="w-15px h-15px rounded-1 ms-2"
                                src="/assets/media/flags/{{ session('locale') == 'ar' ? 'saudi-arabia.svg' : 'united-states.svg' }}"
                                alt="" />
                        </span>
                    </span>
                </a>
                <!--begin::Menu sub-->
                <div class="menu-sub menu-sub-dropdown w-175px py-4">
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="{{ route('set_locale', ['locale' => 'en']) }}"
                            class="menu-link d-flex px-5 {{ session('locale') == 'en' ? 'active' : '' }}">
                            <span class="symbol symbol-20px me-4">
                                <img class="rounded-1" src="/assets/media/flags/united-states.svg" alt="" />
                            </span>English</a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="{{ route('set_locale', ['locale' => 'ar']) }}"
                            class="menu-link d-flex px-5 {{ session('locale') == 'ar' ? 'active' : '' }}">
                            <span class="symbol symbol-20px me-4">
                                <img class="rounded-1" src="/assets/media/flags/saudi-arabia.svg" alt="" />
                            </span>العربية</a>
                    </div>
                    <!--end::Menu item-->

                </div>
                <!--end::Menu sub-->
            </div>
            <!--end::Menu item-->
            <!--begin::Menu item-->
            <div class="menu-item px-5">
                <a href="{{ route('logout') }}" class="menu-link px-5">
                    <i class="ki-outline ki-exit-right fs-2 me-2"></i>@lang('general.sign_out')
                </a>
            </div>
            <!--end::Menu item-->
        </div>
        <!--end::User account menu-->
        <!--end::Menu wrapper-->
    </div>
    <!--end::User menu-->
    <!--begin::Header menu toggle (mobile)-->
    <div class="app-navbar-item d-flex d-lg-none">
        <div class="btn btn-icon btn-custom btn-color-gray-600 btn-active-color-primary w-32px h-32px"
            id="kt_app_aside_mobile_toggle">
            <i class="ki-outline ki-burger-menu-2 fs-3"></i>
        </div>
    </div>
    <!--end::Header menu toggle-->
    </div>
</div>
<style>
    .notification-body {
        transition: background-color 0.5s ease-out;
    }

    .user-quick-menu {
        border-radius: 16px;
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.16);
        overflow: hidden;
        border: 1px solid #edf1f7;
        background: linear-gradient(180deg, #ffffff 0%, #fcfdff 100%);
    }

    .user-shortcuts-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .user-shortcut-item {
        border: 1px solid #edf0f4;
        border-radius: 10px;
        padding: 9px 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #3f4254;
        background: #f9fbff;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        transition: all .2s ease;
        position: relative;
        overflow: hidden;
    }

    .user-shortcut-item:hover {
        transform: translateY(-1px);
        border-color: #d6e4ff;
        background: #eef4ff;
        color: #0d6efd;
    }

    .user-shortcut-item::after {
        content: '';
        position: absolute;
        inset-inline-start: -120%;
        top: 0;
        width: 70%;
        height: 100%;
        background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.75), transparent);
        transition: inset-inline-start .45s ease;
    }

    .user-shortcut-item:hover::after {
        inset-inline-start: 140%;
    }

    .user-quick-menu .menu-content .text-muted {
        letter-spacing: .04em;
    }
</style>
<script>
    let unreadNotificationCount = "{{ $unreadCount }}";

    function updateUnreadBadgeHtml(count) {
        var el = document.querySelector('.read-notification-count');
        if (!el) return;
        var n = parseInt(count, 10) || 0;
        if (n > 0) {
            el.classList.remove('d-none');
            el.textContent = n > 9 ? '9+' : String(n);
        } else {
            el.classList.add('d-none');
            el.textContent = '';
        }
    }
    $(document).ready(function() {
        const quickLinksContainer = document.querySelector('.user-shortcuts-grid');
        if (quickLinksContainer) {
            const userId = "{{ auth()->id() }}";
            const storageKey = `user_quick_links_order_${userId}`;
            let clicksMap = {};

            try {
                clicksMap = JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
            } catch (e) {
                clicksMap = {};
            }

            const sortQuickLinks = () => {
                const cards = Array.from(quickLinksContainer.querySelectorAll('.user-shortcut-item[data-quick-link-key]'));
                cards.sort((a, b) => {
                    const aKey = a.getAttribute('data-quick-link-key');
                    const bKey = b.getAttribute('data-quick-link-key');
                    const aCount = Number(clicksMap[aKey] || 0);
                    const bCount = Number(clicksMap[bKey] || 0);
                    if (bCount !== aCount) return bCount - aCount;
                    return 0;
                });
                cards.forEach((card) => quickLinksContainer.appendChild(card));
            };

            sortQuickLinks();

            quickLinksContainer.querySelectorAll('.user-shortcut-item[data-quick-link-key]').forEach((item) => {
                item.addEventListener('click', function() {
                    const key = this.getAttribute('data-quick-link-key');
                    clicksMap[key] = Number(clicksMap[key] || 0) + 1;
                    localStorage.setItem(storageKey, JSON.stringify(clicksMap));
                });
            });
        }

        setInterval(fetchNotifications, 120000);
        $('.notification_btn').on('click', function(e) {
            if (unreadNotificationCount > 0) {
                ajaxRequest("{{ route('notification-mark-all-as-read') }}", "POST", {}, false, false, false)
                    .done(
                        function(response) {
                            $('.notification-mark-as-read-btn').hide();
                            unreadNotificationCount = 0;
                            updateUnreadBadgeHtml(0);
                            setTimeout(() => {
                                $(`.notification-body`).removeClass('bg-secondary');
                            }, 1000);
                        });
            }
        });
    });

    $(document).on('click', '.notification-delete-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let id = $(this).data('id');
        $(this).closest('.menu').removeClass('show');
        ajaxRequest("{{ route('notification-delete') }}", "POST", {
            id: id
        }, false, false).done(function(response) {
            $(`.notification-${id}`).fadeOut(300, function() {
                $(this).remove();
            });
            updateUnreadBadgeHtml(response.data);
        });
    });

    function fetchNotifications() {
        ajaxRequest("{{ route('fetch-notification') }}", "GET", {}, false, false, false).done(function(response) {
            $('#kt_topbar_notifications_1 .scroll-y').empty();
            if (response.notifications && response.notifications.length > 0) {
                response.notifications.forEach((notification, index) => {
                    const notificationHtml = `
                    <div class="d-flex flex-stack py-4 notification-${notification.id} ${!notification.read_at ? 'bg-secondary' : ''} ${index === 0 ? 'rounded-top' : ''} ${index === response.notifications.length - 1 ? 'rounded-bottom' : ''}">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px me-4">
                                <span class="symbol-label bg-light-primary">
                                    <i class="fs-2 text-primary ki-outline ${notification.icon}"></i>
                                </span>
                            </div>
                            <div class="mb-0 me-2">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-bold">${notification.title}</a>
                                <div class="text-gray-500 fs-7">${notification.body}</div>
                            </div>
                        </div>
                        <span class="badge badge-light fs-8">${notification.created_at}</span>
                        <div class="position-relative">
                            <button type="button" 
                                class="btn btn-sm btn-icon btn-active-color-primary" 
                                data-kt-menu-trigger="click" 
                                data-kt-menu-placement="bottom-end"
                                data-kt-menu-flip="top-end">
                                <i class="ki-solid ki-dots-vertical fs-2x"></i>
                            </button>
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3" 
                                data-kt-menu="true" 
                                style="z-index: 107;">
                                ${!notification.read_at ? `
                                <div class="menu-item px-3 my-1">
                                    <a href="#" class="menu-link px-3 notification-mark-as-read-btn" data-id="${notification.id}">
                                        @lang('general.mark_as_read')
                                    </a>
                                </div>` : ''}
                                <div class="menu-item px-3 my-1">
                                    <a href="#" class="menu-link px-3 notification-delete-btn" data-id="${notification.id}">
                                        @lang('employee::fields.delete')
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                    $('#kt_topbar_notifications_1 .scroll-y').append(notificationHtml);
                });
                unreadNotificationCount = response.unread_count;
                updateUnreadBadgeHtml(unreadNotificationCount);
                $('.notification-count').html(response.all_count);
                KTMenu.init();
                KTMenu.createInstances();
            } else if (typeof response.unread_count !== 'undefined') {
                unreadNotificationCount = response.unread_count;
                updateUnreadBadgeHtml(unreadNotificationCount);
                if (typeof response.all_count !== 'undefined') {
                    $('.notification-count').html(response.all_count);
                }
            }
        });
    }
</script>
