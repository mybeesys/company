<!DOCTYPE html>

@php
    $local = app()->currentLocale();
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
    $rtl_files = $local == 'ar' ? '.rtl' : '';
    $menu_placement_x = $local == 'ar' ? 'right-start' : 'left-start';
    $menu_placement_y = $local == 'ar' ? 'bottom-start' : 'bottom-end';
@endphp
<html lang="{{ $local }}" direction="{{ $dir }}" dir="{{ $dir }}"
    style="direction: {{ $dir }}">

<!--begin::Head-->


<head>
    <title>@hasSection('title')@yield('title') — @endif{{ brand_short_name() }}</title>
    <meta charset="utf-8" />
    <meta name="description"
        content="The most advanced Tailwind CSS & Bootstrap 5 Admin Theme with 40 unique prebuilt layouts on Themeforest trusted by 100,000 beginners and professionals. Multi-demo, Dark Mode, RTL support and complete React, Angular, Vue, Asp.Net Core, Rails, Spring, Blazor, Django, Express.js, Node.js, Flask, Symfony & Laravel versions. Grab your copy now and get life-time updates for free." />
    <meta name="keywords"
        content="tailwind, tailwindcss, metronic, bootstrap, bootstrap 5, angular, VueJs, React, Asp.Net Core, Rails, Spring, Blazor, Django, Express.js, Node.js, Flask, Symfony & Laravel starter kits, admin themes, web design, figma, web development, free templates, free admin themes, bootstrap theme, bootstrap template, bootstrap dashboard, bootstrap dak mode, bootstrap button, bootstrap datepicker, bootstrap timepicker, fullcalendar, datatables, flaticon" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="{{ brand_short_name() }}" />
    <meta name="application-name" content="{{ brand_short_name() }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta property="og:url" content="{{ url('/') }}" />
    <meta property="og:site_name" content="{{ brand_short_name() }}" />
    @include('layouts.css-references')
    @yield('css')
    <style>
        /* Match menuSimple symbol styling across admin/app pages */
        .currency-symbol {
            font-family: 'saudi_riyal', 'Tajawal', 'Cairo', 'Segoe UI Symbol', 'Arial Unicode MS', sans-serif !important;
            font-variant-numeric: tabular-nums;
            line-height: 1;
            display: inline-block;
            margin-inline-start: 0.1rem;
        }

        /* شريط علوي أقصر وأكثر اتساقاً */
        .app-header.app-header--compact .app-header-main {
            min-height: 52px;
            padding-block: 0.25rem;
        }

        .app-header.app-header--compact .app-header-main--grid {
            display: grid;
            grid-template-columns: auto auto minmax(0, 1fr);
            align-items: center;
            width: 100%;
            column-gap: 0.5rem;
        }

        .app-header.app-header--compact .app-header-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            min-width: 0;
        }

        .app-header.app-header--compact .app-header-logo-img {
            height: 42px;
            width: auto;
            max-height: 42px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .app-header.app-header--compact .app-header-company-name {
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.15;
            max-width: min(42vw, 20rem);
        }

        [data-bs-theme="dark"] .app-header.app-header--compact .app-header-company-name {
            color: var(--bs-gray-100) !important;
        }

        .app-header.app-header--compact .app-header-separator {
            margin-top: 0;
        }

        .app-navbar--compact .app-navbar-meta {
            min-width: 0;
        }

        .app-navbar--compact .app-navbar-meta .navbar-meta-date {
            font-variant-numeric: tabular-nums;
        }

        .app-navbar--compact .app-navbar-item .btn-icon {
            border-radius: 0.55rem;
        }
    </style>

</head>
<!--end::Head-->
<!--begin::Body-->
<body id="kt_app_body" data-kt-app-header-fixed="true" data-kt-app-header-fixed-mobile="true"
    data-kt-app-sidebar-minimize="{{ (session('sidebar_minimize') == false ? 'on' : 'off') ?? 'off' }}" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true"
    data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true"
    data-kt-app-aside-enabled="false" data-kt-app-aside-fixed="false" data-kt-app-aside-push-toolbar="true"
    data-kt-app-aside-push-footer="true" class="app-default">
    <div id="ajax-progress-bar" class="progress position-fixed top-0 start-0 w-100"
        style="height: 5px; z-index: 3000; display: none; background-color: #ffffff00">
        <div class="progress-bar progress-bar-animated bg-primary" role="progressbar" style="width: 0%;"
            aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <div id="initial-loader" class="page-loader">
        <span class="spinner-border text-warning" role="status">
            <span class="visually-hidden">Loading...</span>
        </span>
    </div>
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            @php
                $appHeaderCompanyName = null;
                try {
                    if (function_exists('get_company_id') && ($cid = get_company_id())) {
                        $row = \Illuminate\Support\Facades\DB::connection('mysql')
                            ->table('companies')
                            ->select('name', 'name_ar')
                            ->where('id', $cid)
                            ->first();
                        if ($row) {
                            $appHeaderCompanyName = app()->getLocale() === 'ar'
                                ? ($row->name_ar ?: $row->name)
                                : ($row->name ?: $row->name_ar);
                        }
                    }
                } catch (\Throwable $e) {
                    $appHeaderCompanyName = null;
                }
            @endphp
            <div id="kt_app_header" class="app-header app-header--compact d-flex flex-column flex-stack">
                <!--begin::Header main-->
                <div class="app-header-main app-header-main--grid flex-grow-1 ps-lg-6 ps-3">
                    <div class="app-header-toggles d-flex align-items-center" id="kt_app_header_logo">
                        <div id="kt_app_sidebar_toggle"
                            class="app-sidebar-toggle btn btn-sm btn-icon bg-body btn-color-gray-500 btn-active-color-primary w-28px h-28px ms-n2 me-2 d-none d-lg-flex"
                            data-kt-toggle="true" data-kt-toggle-target="body"
                            data-kt-toggle-name="app-sidebar-minimize">
                            <i class="ki-outline ki-abstract-14 fs-4"></i>
                        </div>
                        <div class="btn btn-icon btn-active-color-primary w-32px h-32px ms-1 me-1 d-flex d-lg-none"
                            id="kt_app_sidebar_mobile_toggle">
                            <i class="ki-outline ki-abstract-14 fs-3"></i>
                        </div>
                    </div>
                    <div class="app-header-brand">
                        @if (filled($appHeaderCompanyName))
                            <span class="app-header-company-name text-truncate text-gray-800" title="{{ $appHeaderCompanyName }}">{{ $appHeaderCompanyName }}</span>
                        @endif
                        <a href="/" class="app-sidebar-logo app-header-logo-link d-inline-flex align-items-center py-1 text-decoration-none flex-shrink-0">
                            <img alt="{{ brand_short_name() }}" src="/assets/media/logos/1-11.png" class="app-header-logo-img" />
                        </a>
                    </div>
                    @include('components.navBar')
                </div>
                <!--begin::Separator-->
                <div class="app-header-separator"></div>
                <!--end::Separator-->
            </div>
            <!--end::Header-->
            <!--begin::Wrapper-->
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <!--begin::Sidebar-->
                <div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true"
                    data-kt-drawer-name="app-sidebar" data-kt-drawer-activate="{default: true, lg: false}"
                    data-kt-drawer-overlay="true" data-kt-drawer-width="250px" data-kt-drawer-direction="start"
                    data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
                    <!--begin::Wrapper-->
                    <div class="app-sidebar-wrapper">
                        <div id="kt_app_sidebar_wrapper" class="hover-scroll-y my-5 my-lg-2 mx-4"
                            data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}"
                            data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_app_header"
                            data-kt-scroll-wrappers="#kt_app_sidebar_wrapper" data-kt-scroll-offset="5px">
                            <!--begin::Sidebar menu-->

                            @include('components.sidebar.sideBar')

                            <!--end::Sidebar menu-->
                        </div>
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Sidebar-->
                <!--begin::Main-->
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <!--begin::Content wrapper-->
                    <div class="d-flex flex-column flex-column-fluid">
                        @yield('content-head')
                        <div id="kt_app_content" class="app-content flex-column-fluid pt-2">
                            <!--begin::Content container-->
                            <div id="kt_app_content_container" class="app-container container-fluid">
                                @yield('content')
                            </div>
                            <!--end::Content container-->
                        </div>
                    </div>
                    <!--end::Content wrapper-->
                    <!--begin::Footer-->
                    <div id="kt_app_footer" class="app-footer">
                        <!--begin::Footer container-->
                        <div
                            class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack">
                            <!--begin::Copyright-->
                            <div class="text-gray-900 order-2 order-md-1">
                                <span class="text-muted fw-semibold me-1">{{ date('Y') }}&copy;</span>
                                <a href="{{ url('/') }}" class="text-gray-800 text-hover-primary">{{ brand_short_name() }}</a>
                                <span class="text-muted fs-8 ms-2 d-none d-md-inline">{{ brand_legal_name() }}</span>
                            </div>
                            <!--end::Copyright-->
                            <!--begin::Menu-->
                            <ul class="menu menu-gray-600 menu-hover-primary fw-semibold order-1">
                                <li class="menu-item">
                                    <a href="https://keenthemes.com" target="_blank" class="menu-link px-2">About</a>
                                </li>
                                <li class="menu-item">
                                    <a href="https://devs.keenthemes.com" target="_blank"
                                        class="menu-link px-2">Support</a>
                                </li>
                                <li class="menu-item">
                                    <a href="https://1.envato.market/EA4JP" target="_blank"
                                        class="menu-link px-2">Purchase</a>
                                </li>
                            </ul>
                            <!--end::Menu-->
                        </div>
                        <!--end::Footer container-->
                    </div>
                    <!--end::Footer-->
                </div>
                <!--end:::Main-->
                <!--begin::aside-->
                @include('components.aside')
                <!--end::aside-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Page-->
    </div>
    <!--end::App-->
    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-outline ki-arrow-up"></i>
    </div>

    @include('layouts.js-references')

    @yield('script')
</body>
<!--end::Body-->

</html>
