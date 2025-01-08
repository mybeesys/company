<!DOCTYPE html>
@php
    $local = app()->currentLocale();
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
    $rtl_files = $local == 'ar' ? '.rtl' : '';
    $menu_placement_x = $local == 'ar' ? 'left-start' : 'right-start';
    $menu_placement_y = $local == 'ar' ? 'bottom-end' : 'bottom-start';
@endphp
<html lang="{{ $local }}" direction="{{ $dir }}" dir="{{ $dir }}"
    style="direction: {{ $dir }}">

<head>
    <title>MyBee - @lang('employee::general.log_in')</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    @include('layouts.css-references')
</head>

<body id="kt_body" class="app-blank bgi-size-cover bgi-attachment-fixed bgi-position-center bgi-no-repeat">
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
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <style>
            body {
                background-image: url('assets/media/auth/bg4.jpg');
            }

            [data-bs-theme="dark"] body {
                background-image: url('assets/media/auth/bg4-dark.jpg');
            }
        </style>
        <div class="d-flex flex-column flex-column-fluid flex-lg-row">
            <div class="d-flex flex-center w-lg-50 pt-15 pt-lg-0 px-10">
                <div class="d-flex flex-center flex-lg-start flex-column">
                    <a href="#" class="mb-7">
                        <img class="h-400px w-369px" alt="Logo" src="assets/media/logos/1-08.png" />
                    </a>
                </div>
            </div>
            <div
                class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12 p-lg-20">
                <!--begin::Card-->
                <div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-550px py-20 px-15">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-10">
                        <!--begin::Form-->
                        <form action="{{ route('login.postLogin') }}" method="POST" class="form w-100"
                            novalidate="novalidate" id="kt_sign_in_form">
                            @csrf
                            <div class="text-center mb-11">
                                <h1 class="text-gray-900 fw-bolder mb-3">{{ ucfirst(tenant('id')) }}</h1>
                                {{-- <div class="text-gray-500 fw-semibold fs-6">Your Social Campaigns</div> --}}
                            </div>
                            {{-- <div class="row g-3 mb-9">
                                <div class="col-md-6">
                                    <a href="#"
                                        class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100">
                                        <img alt="Logo" src="assets/media/svg/brand-logos/google-icon.svg"
                                            class="h-15px me-3" />Sign in with Google</a>
                                </div>
                                <div class="col-md-6">
                                    <a href="#"
                                        class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100">
                                        <img alt="Logo" src="assets/media/svg/brand-logos/apple-black.svg"
                                            class="theme-light-show h-15px me-3" />
                                        <img alt="Logo" src="assets/media/svg/brand-logos/apple-black-dark.svg"
                                            class="theme-dark-show h-15px me-3" />Sign in with Apple</a>
                                </div>
                            </div> --}}
                            <div class="separator separator-content mt-14 mb-20">
                                <span class="w-125px text-gray-500 fw-semibold fs-6">@lang('employee::general.login_to_your_company')</span>
                            </div>

                            <div class="fv-row mb-8">
                                <x-form.input class="bg-transparent" name="email" :placeholder="__('employee::general.email_or_user_name')" />
                            </div>
                            <div class="fv-row mb-5">
                                <x-form.input class="bg-transparent" type="password" name="password"
                                    :placeholder="__('employee::fields.password')" />
                            </div>
                            <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-12">
                                <a href="authentication/layouts/creative/reset-password.html"
                                    class="link-primary">@lang('employee::general.forgot_password')</a>
                            </div>
                            <div class="d-grid mb-10">
                                <button type="submit" id="kt_sign_in_submit" class="btn btn-primary">
                                    <span class="indicator-label">@lang('employee::general.sign_in')</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="d-flex flex-stack px-lg-10">
                        <div class="me-0">
                            <button
                                class="btn btn-flex btn-link btn-color-gray-700 btn-active-color-primary rotate fs-base"
                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-start"
                                data-kt-menu-offset="0px, 0px">
                                @if (session('locale') === 'ar')
                                    <img data-kt-element="current-lang-flag" class="w-20px h-20px rounded me-3"
                                        src="/assets/media/flags/saudi-arabia.svg" alt="" />
                                    <span data-kt-element="current-lang-name" class="me-1">العربية</span>
                                    <i class="ki-outline ki-down fs-5 text-muted rotate-180 m-0"></i>
                                @else
                                    <img data-kt-element="current-lang-flag" class="w-20px h-20px rounded me-3"
                                        src="assets/media/flags/united-states.svg" alt="" />
                                    <span data-kt-element="current-lang-name" class="me-1">English</span>
                                    <i class="ki-outline ki-down fs-5 text-muted rotate-180 m-0"></i>
                                @endif
                            </button>
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-4 fs-7"
                                data-kt-menu="true" id="kt_auth_lang_menu">
                                <div class="menu-item px-3">
                                    <a href="{{ route('set_locale', ['locale' => 'en']) }}"
                                        class="menu-link d-flex px-5" data-kt-lang="English">
                                        <span class="symbol symbol-20px me-4">
                                            <img data-kt-element="lang-flag" class="rounded-1"
                                                src="assets/media/flags/united-states.svg" alt="" />
                                        </span>
                                        <span data-kt-element="lang-name">English</span>
                                    </a>
                                </div>
                                <div class="menu-item px-3">
                                    <a href="{{ route('set_locale', ['locale' => 'ar']) }}"
                                        class="menu-link d-flex px-5" data-kt-lang="arabic">
                                        <span class="symbol symbol-20px me-4">
                                            <img data-kt-element="lang-flag" class="rounded-1"
                                                src="assets/media/flags/saudi-arabia.svg" alt="" />
                                        </span>
                                        <span data-kt-element="lang-name">العربية</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex fw-semibold text-primary fs-base gap-5">
                            <a href="#" target="_blank">@lang('employee::general.terms')</a>
                            <a href="#" target="_blank">@lang('employee::general.contact_us')</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('layouts.js-references')


</body>
<!--end::Body-->

</html>
