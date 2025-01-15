<!--begin::Navbar-->
<div class="app-navbar flex-grow-1 justify-content-end" id="kt_app_header_navbar">
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
    <!--begin::Notifications-->
    <div class="app-navbar-item ms-2 ms-lg-6">
        <div class="btn btn-icon btn-custom btn-color-gray-600 btn-active-color-primary w-35px h-35px w-md-40px h-md-40px position-relative"
            data-kt-menu-trigger="{default: 'click'}" data-kt-menu-attach="parent"
            data-kt-menu-placement="{{ $menu_placement_y }}">
            <i class="ki-outline ki-notification-on fs-1 notification_btn"></i>
            <span
                class="position-absolute top-0 start-100 translate-middle badge badge-circle badge-danger w-15px h-15px ms-n4 mt-3 pb-1 read-notification-count">{{ auth()->user()->unreadNotifications->count() }}</span>
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
    <div class="app-navbar-item ms-2 ms-lg-6" id="kt_header_user_menu_toggle">
        <!--begin::Menu wrapper-->
        <div class="cursor-pointer symbol symbol-circle symbol-30px symbol-lg-45px"
            data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent"
            data-kt-menu-placement="{{ $menu_placement_y }}">
            <img src="{{ auth()->user()->image ? asset('storage/tenant' . tenancy()->tenant->id . '/' . auth()->user()->image) : url('/assets/media/avatars/blank.png') }}"
                alt="user" />
        </div>
        <!--begin::User account menu-->
        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px"
            data-kt-menu="true">
            <!--begin::Menu item-->
            <div class="menu-item px-3">
                <div class="menu-content d-flex align-items-center px-3">
                    <div class="symbol symbol-50px me-5">
                        <img alt="Logo"
                            src="{{ auth()->user()->image ? asset('storage/tenant' . tenancy()->tenant->id . '/' . auth()->user()->image) : url('/assets/media/avatars/blank.png') }}" />
                    </div>
                    <div class="d-flex flex-column">
                        <div class="fw-bold d-flex align-items-center fs-5">{{ auth()->user()->user_name }}
                            <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">Pro</span>
                        </div>
                        <a href="#"
                            class="fw-semibold text-muted text-hover-primary fs-7">{{ auth()->user()->email }}</a>
                    </div>
                </div>
            </div>
            <div class="separator my-2"></div>
            <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                data-kt-menu-placement="{{ $menu_placement_x }}" data-kt-menu-offset="-15px, 0">
                <a href="#" class="menu-link px-5">
                    <span class="menu-title">My Subscription</span>
                    <span class="menu-arrow"></span>
                </a>
                <!--be  gin::Menu sub-->
                <div class="menu-sub menu-sub-dropdown w-175px py-4">
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="/account/referrals.html" class="menu-link px-5">Referrals</a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="/account/billing.html" class="menu-link px-5">Billing</a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="/account/statements.html" class="menu-link px-5">Payments</a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="/account/statements.html" class="menu-link d-flex flex-stack px-5">Statements
                            <span class="ms-2 lh-0" data-bs-toggle="tooltip" title="View your statements">
                                <i class="ki-outline ki-information-5 fs-5"></i>
                            </span></a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu separator-->
                    <div class="separator my-2"></div>
                    <!--end::Menu separator-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <div class="menu-content px-3">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input w-30px h-20px" type="checkbox" value="1"
                                    checked="checked" name="notifications" />
                                <span class="form-check-label text-muted fs-7">Notifications</span>
                            </label>
                        </div>
                    </div>
                    <!--end::Menu item-->
                </div>
                <!--end::Menu sub-->
            </div>
            <!--end::Menu item-->
            <!--begin::Menu separator-->
            <div class="separator my-2"></div>
            <!--end::Menu separator-->
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
                <a href="{{ route('logout') }}" class="menu-link px-5">@lang('general.sign_out')</a>
            </div>
            <!--end::Menu item-->
        </div>
        <!--end::User account menu-->
        <!--end::Menu wrapper-->
    </div>
    <!--end::User menu-->
    <!--begin::Action-->
    <div class="app-navbar-item ms-2 ms-lg-6 me-lg-6">
        <!--begin::Link-->
        <a href="{{ route('logout') }}"
            class="btn btn-icon btn-custom btn-color-gray-600 btn-active-color-primary w-35px h-35px w-md-40px h-md-40px">
            <i class="ki-outline ki-exit-right fs-1"></i>
        </a>
        <!--end::Link-->
    </div>
    <!--end::Action-->
    <!--begin::Header menu toggle-->
    <div class="app-navbar-item ms-2 ms-lg-6 ms-n2 me-3 d-flex d-lg-none">
        <div class="btn btn-icon btn-custom btn-color-gray-600 btn-active-color-primary w-35px h-35px w-md-40px h-md-40px"
            id="kt_app_aside_mobile_toggle">
            <i class="ki-outline ki-burger-menu-2 fs-2"></i>
        </div>
    </div>
    <!--end::Header menu toggle-->
</div>
<style>
    .notification-body {
        transition: background-color 0.5s ease-out;
    }
</style>
<script>
    let unreadNotificationCount = "{{ auth()->user()->notifications->count() }}";
    $(document).ready(function() {
        setInterval(fetchNotifications, 120000);
        $('.notification_btn').on('click', function(e) {
            if (unreadNotificationCount > 0) {
                ajaxRequest("{{ route('notification-mark-all-as-read') }}", "POST", {}, false, false, false)
                    .done(
                        function(response) {
                            $('.notification-mark-as-read-btn').hide();
                            $('.read-notification-count').html(0);
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
            $('.read-notification-count').html(response.data);
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
                unreadNotificationCount = response.unread_count
                $('.read-notification-count').html(unreadNotificationCount);
                $('.notification-count').html(response.all_count)
                KTMenu.init();
                KTMenu.createInstances();
            }
        });
    }
</script>
