<!--begin::Navbar-->
@php
    use App\Services\CentralCompanyMembershipService;

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
            'url' => route('inventory.dashboard'),
            'label' => __('general.inventory_dashboard'),
            'icon' => 'ki-outline ki-package',
            'permission' => 'inventory.dashboard.show',
        ],
        [
            'url' => route('sales-dashbord'),
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
    $linkedCompanies = collect();
    if (auth()->check() && filled(auth()->user()->email)) {
        try {
            $linkedCompanies = app(CentralCompanyMembershipService::class)
                ->companiesForEmail((string) auth()->user()->email, tenant('id'));
        } catch (\Throwable) {
            $linkedCompanies = collect();
        }
    }
    $linkedCompaniesCount = $linkedCompanies->count();
    $otherLinkedCompanies = $linkedCompanies->where('is_current', false)->values();
    $currentLinkedCompany = $linkedCompanies->firstWhere('is_current', true);
    $roleLabel = function (?string $role): string {
        $key = 'employee::my_companies.roles.' . ($role ?: 'member');

        return \Illuminate\Support\Facades\Lang::has($key) ? __($key) : (string) $role;
    };
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
            data-kt-menu-placement="{{ $menu_placement_y }}" data-kt-menu-flip="top">
            <img src="{{ auth()->user()->image ? asset('storage/tenant' . tenancy()->tenant->id . '/' . auth()->user()->image) : url('/assets/media/avatars/blank.png') }}"
                alt="user" />
        </div>
        <!--begin::User account menu-->
        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold fs-6 user-quick-menu"
            data-kt-menu="true">
            <div class="user-quick-menu-header">
                <div class="user-quick-menu-profile">
                    <div class="symbol symbol-45px flex-shrink-0">
                        <img alt="{{ auth()->user()->user_name }}"
                            src="{{ auth()->user()->image ? asset('storage/tenant' . tenancy()->tenant->id . '/' . auth()->user()->image) : url('/assets/media/avatars/blank.png') }}" />
                    </div>
                    <div class="user-quick-menu-profile-text min-w-0">
                        <div class="fw-bold fs-6 text-truncate">{{ auth()->user()->user_name }}</div>
                        <div class="text-muted fs-8 text-truncate">{{ auth()->user()->email }}</div>
                    </div>
                </div>
            </div>

            <div class="user-quick-menu-scroll">
                @if ($linkedCompaniesCount > 0)
                    <div class="user-quick-menu-section">
                        <div class="user-quick-menu-section-title">
                            <i class="ki-outline ki-abstract-26 fs-6"></i>
                            <span>@lang('general.workspace')</span>
                        </div>

                        <div class="user-companies-list @if ($linkedCompaniesCount > 3) is-scrollable @endif">
                            @if ($currentLinkedCompany)
                                <div class="user-company-chip is-current" title="{{ $currentLinkedCompany->domain }}">
                                    <span class="user-company-chip-icon">
                                        <i class="ki-outline ki-check-circle fs-4"></i>
                                    </span>
                                    <span class="user-company-chip-body">
                                        <span class="user-company-chip-name">{{ $currentLinkedCompany->company_name }}</span>
                                        <span class="user-company-chip-meta">{{ $currentLinkedCompany->domain }}</span>
                                    </span>
                                    <span class="user-company-chip-badge">@lang('employee::my_companies.current')</span>
                                </div>
                            @endif

                            @foreach ($otherLinkedCompanies as $company)
                                <button
                                    type="button"
                                    class="user-company-chip js-navbar-open-company"
                                    data-url="{{ route('my-companies.switch', ['tenantId' => $company->tenant_id]) }}"
                                    title="{{ $company->domain }}"
                                >
                                    <span class="user-company-chip-icon">
                                        <i class="ki-outline ki-office-bag fs-4"></i>
                                    </span>
                                    <span class="user-company-chip-body">
                                        <span class="user-company-chip-name">{{ $company->company_name }}</span>
                                        <span class="user-company-chip-meta">{{ $company->domain }} · {{ $roleLabel($company->role) }}</span>
                                    </span>
                                    <span class="user-company-chip-action">
                                        <i class="ki-outline ki-exit-right fs-5"></i>
                                    </span>
                                    <span class="user-company-chip-progress d-none">
                                        <span class="spinner-border spinner-border-sm"></span>
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        <div class="user-workspace-links">
                            <a href="{{ route('my-companies.index') }}" class="user-workspace-link">
                                <i class="ki-outline ki-element-11 fs-5"></i>
                                <span>@lang('employee::my_companies.menu')</span>
                            </a>
                            <a href="{{ url('/subscription') }}" class="user-workspace-link">
                                <i class="ki-outline ki-crown fs-5"></i>
                                <span>@lang('general.subscriptions')</span>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="user-quick-menu-section">
                        <div class="user-quick-menu-section-title">
                            <i class="ki-outline ki-abstract-26 fs-6"></i>
                            <span>@lang('general.workspace')</span>
                        </div>
                        <a href="{{ url('/subscription') }}" class="user-workspace-link">
                            <i class="ki-outline ki-crown fs-5"></i>
                            <span>@lang('general.subscriptions')</span>
                        </a>
                    </div>
                @endif

                @if ($quickLinks->isNotEmpty())
                    <div class="user-quick-menu-section">
                        <div class="user-quick-menu-section-title">
                            <i class="ki-outline ki-flash-circle fs-6"></i>
                            <span>@lang('general.quick_access')</span>
                        </div>
                        <div class="user-shortcuts-grid">
                            @foreach ($quickLinks as $link)
                                <a href="{{ url($link['url']) }}" class="user-shortcut-item"
                                    data-quick-link-key="{{ md5($link['url']) }}">
                                    <i class="{{ $link['icon'] }} fs-4"></i>
                                    <span>{{ $link['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="user-quick-menu-footer">
                <div class="user-quick-menu-footer-row">
                    <div class="menu-item flex-grow-1" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                        data-kt-menu-placement="{{ $menu_placement_x }}" data-kt-menu-offset="-8px, 0">
                        <button type="button" class="user-footer-btn">
                            <i class="ki-outline ki-night-day theme-light-show fs-4"></i>
                            <i class="ki-outline ki-moon theme-dark-show fs-4"></i>
                            <span>@lang('general.mode')</span>
                        </button>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-3 fs-7 w-140px"
                            data-kt-menu="true" data-kt-element="theme-mode-menu">
                            <div class="menu-item px-3 my-0">
                                <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                                    <span class="menu-icon" data-kt-element="icon">
                                        <i class="ki-outline ki-night-day fs-3"></i>
                                    </span>
                                    <span class="menu-title">@lang('general.light')</span>
                                </a>
                            </div>
                            <div class="menu-item px-3 my-0">
                                <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                    <span class="menu-icon" data-kt-element="icon">
                                        <i class="ki-outline ki-moon fs-3"></i>
                                    </span>
                                    <span class="menu-title">@lang('general.dark')</span>
                                </a>
                            </div>
                            <div class="menu-item px-3 my-0">
                                <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                    <span class="menu-icon" data-kt-element="icon">
                                        <i class="ki-outline ki-screen fs-3"></i>
                                    </span>
                                    <span class="menu-title">@lang('general.system')</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="menu-item flex-grow-1" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                        data-kt-menu-placement="{{ $menu_placement_x }}" data-kt-menu-offset="-8px, 0">
                        <button type="button" class="user-footer-btn">
                            <img class="user-footer-flag"
                                src="/assets/media/flags/{{ session('locale') == 'ar' ? 'saudi-arabia.svg' : 'united-states.svg' }}"
                                alt="" />
                            <span>{{ session('locale') == 'ar' ? 'العربية' : 'English' }}</span>
                        </button>
                        <div class="menu-sub menu-sub-dropdown w-160px py-3">
                            <div class="menu-item px-3">
                                <a href="{{ route('set_locale', ['locale' => 'en']) }}"
                                    class="menu-link d-flex px-3 {{ session('locale') == 'en' ? 'active' : '' }}">
                                    <span class="symbol symbol-20px me-3">
                                        <img class="rounded-1" src="/assets/media/flags/united-states.svg" alt="" />
                                    </span>English</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="{{ route('set_locale', ['locale' => 'ar']) }}"
                                    class="menu-link d-flex px-3 {{ session('locale') == 'ar' ? 'active' : '' }}">
                                    <span class="symbol symbol-20px me-3">
                                        <img class="rounded-1" src="/assets/media/flags/saudi-arabia.svg" alt="" />
                                    </span>العربية</a>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('logout') }}" class="user-footer-logout">
                    <i class="ki-outline ki-exit-right fs-4"></i>
                    <span>@lang('general.sign_out')</span>
                </a>
            </div>
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
        --uqm-accent: #e8b923;
        --uqm-border: #e8edf4;
        --uqm-surface: #f8fafc;
        --uqm-text: #3f4254;
        display: flex;
        flex-direction: column;
        width: min(340px, calc(100vw - 1.5rem));
        max-height: min(88dvh, 620px);
        padding: 0;
        margin: 0;
        overflow: hidden;
        border-radius: 14px;
        border: 1px solid var(--uqm-border);
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.14);
        background: #fff;
    }

    .user-quick-menu-header {
        flex-shrink: 0;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--uqm-border);
        background: linear-gradient(180deg, #fffdf6 0%, #ffffff 100%);
    }

    .user-quick-menu-profile {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }

    .user-quick-menu-profile-text {
        line-height: 1.25;
    }

    .user-quick-menu-scroll {
        flex: 1 1 auto;
        min-height: 0;
        overflow-x: hidden;
        overflow-y: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        padding: 0.65rem 0.85rem 0.75rem;
    }

    .user-quick-menu-scroll::-webkit-scrollbar {
        width: 5px;
    }

    .user-quick-menu-scroll::-webkit-scrollbar-thumb {
        background: #d5dbe5;
        border-radius: 99px;
    }

    .user-quick-menu-section + .user-quick-menu-section {
        margin-top: 0.85rem;
        padding-top: 0.85rem;
        border-top: 1px dashed #e7ecf3;
    }

    .user-quick-menu-section-title {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin-bottom: 0.55rem;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #99a1b7;
    }

    .user-companies-list {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }

    .user-companies-list.is-scrollable {
        max-height: 9.5rem;
        overflow-y: auto;
        padding-inline-end: 0.15rem;
    }

    .user-company-chip {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        width: 100%;
        min-width: 0;
        padding: 0.5rem 0.6rem;
        border-radius: 10px;
        border: 1px solid var(--uqm-border);
        background: var(--uqm-surface);
        text-align: start;
        transition: border-color .15s ease, background-color .15s ease, transform .15s ease;
    }

    button.user-company-chip {
        cursor: pointer;
    }

    button.user-company-chip:hover:not(:disabled) {
        border-color: #e8c96a;
        background: #fff9e8;
        transform: translateY(-1px);
    }

    button.user-company-chip:disabled {
        opacity: .7;
        cursor: wait;
    }

    .user-company-chip.is-current {
        border-color: #b7e4c7;
        background: #f3fbf6;
    }

    .user-company-chip-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.85rem;
        height: 1.85rem;
        border-radius: 8px;
        background: #fff;
        color: var(--uqm-accent);
        flex-shrink: 0;
    }

    .user-company-chip.is-current .user-company-chip-icon {
        color: #50cd89;
    }

    .user-company-chip-body {
        display: flex;
        flex-direction: column;
        min-width: 0;
        flex: 1 1 auto;
    }

    .user-company-chip-name {
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--uqm-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-company-chip-meta {
        font-size: 0.68rem;
        color: #99a1b7;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-company-chip-badge {
        flex-shrink: 0;
        font-size: 0.62rem;
        font-weight: 700;
        padding: 0.15rem 0.45rem;
        border-radius: 999px;
        background: #e8fff3;
        color: #1f8f55;
    }

    .user-company-chip-action {
        flex-shrink: 0;
        color: #99a1b7;
    }

    .user-company-chip-progress {
        flex-shrink: 0;
    }

    .user-workspace-links {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.55rem;
    }

    .user-workspace-link {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.55rem;
        border-radius: 8px;
        border: 1px solid var(--uqm-border);
        background: #fff;
        color: #5e6278;
        font-size: 0.74rem;
        font-weight: 600;
        text-decoration: none;
        transition: all .15s ease;
    }

    .user-workspace-link:hover {
        border-color: #e8c96a;
        color: #b8890d;
        background: #fffdf4;
    }

    .user-shortcuts-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.45rem;
    }

    .user-shortcut-item {
        border: 1px solid var(--uqm-border);
        border-radius: 9px;
        padding: 0.5rem 0.45rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        min-height: 2.35rem;
        color: var(--uqm-text);
        background: var(--uqm-surface);
        text-decoration: none;
        font-size: 0.72rem;
        font-weight: 600;
        line-height: 1.2;
        transition: all .15s ease;
    }

    .user-shortcut-item span {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .user-shortcut-item:hover {
        border-color: #e8c96a;
        background: #fff9e8;
        color: #b8890d;
    }

    .user-quick-menu-footer {
        flex-shrink: 0;
        padding: 0.65rem 0.85rem 0.8rem;
        border-top: 1px solid var(--uqm-border);
        background: #fbfcfe;
    }

    .user-quick-menu-footer-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.45rem;
        margin-bottom: 0.45rem;
    }

    .user-footer-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        width: 100%;
        padding: 0.45rem 0.5rem;
        border: 1px solid var(--uqm-border);
        border-radius: 9px;
        background: #fff;
        color: #5e6278;
        font-size: 0.74rem;
        font-weight: 600;
    }

    .user-footer-btn:hover {
        border-color: #d5dbe5;
        background: #f8fafc;
    }

    .user-footer-flag {
        width: 1rem;
        height: 1rem;
        border-radius: 2px;
        object-fit: cover;
    }

    .user-footer-logout {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        width: 100%;
        padding: 0.5rem;
        border-radius: 9px;
        background: #fff5f5;
        border: 1px solid #ffd6d6;
        color: #d9214e;
        font-size: 0.78rem;
        font-weight: 700;
        text-decoration: none;
        transition: all .15s ease;
    }

    .user-footer-logout:hover {
        background: #ffe9e9;
        color: #b81940;
    }

    [data-bs-theme="dark"] .user-quick-menu {
        --uqm-border: rgba(255, 255, 255, 0.08);
        --uqm-surface: rgba(255, 255, 255, 0.04);
        --uqm-text: #f1f1f4;
        background: #151521;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.45);
    }

    [data-bs-theme="dark"] .user-quick-menu-header {
        background: linear-gradient(180deg, rgba(232, 185, 35, 0.08) 0%, #151521 100%);
    }

    [data-bs-theme="dark"] .user-quick-menu-footer {
        background: #12121c;
    }

    [data-bs-theme="dark"] .user-company-chip.is-current {
        border-color: rgba(80, 205, 137, 0.35);
        background: rgba(80, 205, 137, 0.08);
    }

    [data-bs-theme="dark"] .user-company-chip-icon {
        background: rgba(255, 255, 255, 0.06);
    }

    [data-bs-theme="dark"] .user-workspace-link,
    [data-bs-theme="dark"] .user-footer-btn,
    [data-bs-theme="dark"] .user-shortcut-item {
        background: rgba(255, 255, 255, 0.03);
        color: #d1d5de;
    }

    [data-bs-theme="dark"] .user-footer-logout {
        background: rgba(217, 33, 78, 0.12);
        border-color: rgba(217, 33, 78, 0.25);
    }

    @media (max-width: 575.98px) {
        .user-quick-menu {
            width: min(320px, calc(100vw - 1rem));
            max-height: min(92dvh, 680px);
        }

        .user-shortcuts-grid {
            grid-template-columns: 1fr;
        }

        .user-quick-menu-scroll {
            padding-inline: 0.7rem;
        }
    }

    @media (max-height: 700px) {
        .user-quick-menu {
            max-height: calc(100dvh - 4.5rem);
        }

        .user-companies-list.is-scrollable {
            max-height: 6.5rem;
        }
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

        document.querySelectorAll('.js-navbar-open-company').forEach((button) => {
            button.addEventListener('click', async function () {
                const endpoint = this.dataset.url;
                if (!endpoint || this.disabled) {
                    return;
                }

                const body = this.querySelector('.user-company-chip-body');
                const action = this.querySelector('.user-company-chip-action');
                const progress = this.querySelector('.user-company-chip-progress');

                this.disabled = true;
                body?.classList.add('d-none');
                action?.classList.add('d-none');
                progress?.classList.remove('d-none');

                try {
                    const response = await fetch(endpoint, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error('switch failed');
                    }

                    const data = await response.json();
                    if (data.url) {
                        window.location.href = data.url;
                    }
                } catch (error) {
                    alert(@json(__('employee::my_companies.switch_failed')));
                    this.disabled = false;
                    body?.classList.remove('d-none');
                    action?.classList.remove('d-none');
                    progress?.classList.add('d-none');
                }
            });
        });

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
