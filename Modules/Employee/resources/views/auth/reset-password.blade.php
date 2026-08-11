<!DOCTYPE html>
@php
    $local = app()->currentLocale();
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
@endphp
<html lang="{{ $local }}" dir="{{ $dir }}">

<head>
    <title>@lang('employee::general.reset_password_page_title') — {{ brand_short_name() }}</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="color-scheme" content="light dark" />
    @include('layouts.css-references')
    @include('employee::auth.partials.guest-auth-styles')
</head>

<body id="kt_body" class="app-blank bgi-size-cover bgi-attachment-fixed bgi-position-center bgi-no-repeat login-auth-page">
    <div id="initial-loader" class="page-loader">
        <span class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </span>
    </div>
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

    <div class="login-auth-wrap">
        <div class="login-auth-card">
            <div class="row g-0 align-items-stretch">
                <div class="col-12 col-lg-6 login-auth-panel login-auth-panel--brand">
                    <a href="{{ url('/') }}" class="login-auth-logo text-decoration-none d-inline-block mb-2"
                        aria-label="{{ brand_short_name() }}">
                        <img src="{{ asset('assets/media/logos/1-11.png') }}" alt="" />
                    </a>
                    @if (tenant('id'))
                        @php
                            try {
                                $loginCompanyName = function_exists('company_header_name') ? company_header_name() : null;
                            } catch (\Throwable) {
                                $loginCompanyName = null;
                            }
                            if (! filled($loginCompanyName)) {
                                $loginCompanyName = ucfirst((string) tenant('id'));
                            }
                        @endphp
                        <span class="login-auth-tenant">{{ $loginCompanyName }}</span>
                    @endif
                    <h1 class="login-auth-title">@lang('employee::general.reset_password_brand_title')</h1>
                    <p class="login-auth-sub">@lang('employee::general.reset_password_brand_sub')</p>

                    <div class="login-auth-aside-footer">
                        <div class="login-lang-wrap position-relative d-inline-flex justify-content-center" id="login_lang_wrap">
                            <button type="button"
                                class="btn btn-flex btn-link btn-color-gray-700 btn-active-color-primary fs-base px-2"
                                id="login_lang_btn"
                                aria-expanded="false"
                                aria-haspopup="true"
                                aria-controls="login_lang_menu">
                                @if (session('locale') === 'ar')
                                    <img class="w-20px h-20px rounded me-2"
                                        src="{{ asset('assets/media/flags/saudi-arabia.svg') }}" alt="" />
                                    <span class="me-1">العربية</span>
                                @else
                                    <img class="w-20px h-20px rounded me-2"
                                        src="{{ asset('assets/media/flags/united-states.svg') }}" alt="" />
                                    <span class="me-1">English</span>
                                @endif
                                <i class="ki-outline ki-down fs-5 text-muted m-0 login-lang-chevron" aria-hidden="true"></i>
                            </button>
                            <div class="login-lang-dropdown" id="login_lang_menu" role="menu" aria-labelledby="login_lang_btn">
                                <a href="{{ route('set_locale', ['locale' => 'en']) }}" class="login-lang-link" role="menuitem">
                                    <img class="rounded-1" width="20" height="20" src="{{ asset('assets/media/flags/united-states.svg') }}" alt="" />
                                    <span>English</span>
                                </a>
                                <a href="{{ route('set_locale', ['locale' => 'ar']) }}" class="login-lang-link" role="menuitem">
                                    <img class="rounded-1" width="20" height="20" src="{{ asset('assets/media/flags/saudi-arabia.svg') }}" alt="" />
                                    <span>العربية</span>
                                </a>
                            </div>
                        </div>
                        <div class="login-auth-links">
                            <a href="#" target="_blank" rel="noopener noreferrer" class="text-primary">@lang('employee::general.terms')</a>
                            <span class="text-muted opacity-50" aria-hidden="true">|</span>
                            <a href="#" target="_blank" rel="noopener noreferrer" class="text-primary">@lang('employee::general.contact_us')</a>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6 login-auth-panel login-auth-panel--form">
                    @if ($errors->any())
                        <div class="alert alert-danger mb-8" role="alert" aria-live="polite">
                            <ul class="mb-0 ps-4 small">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="login-auth-divider">
                        <span>@lang('employee::general.reset_password_page_title')</span>
                    </div>

                    <p class="text-muted fs-7 mb-6">@lang('employee::general.reset_password_help')</p>

                    <form action="{{ route('password.update') }}" method="POST" class="form w-100" id="kt_reset_password_form" novalidate>
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}" />
                        <input type="hidden" name="email" value="{{ old('email', $email) }}" />

                        <div class="fv-row mb-4">
                            <label for="password" class="form-label">{{ __('employee::fields.new_password') }}</label>
                            <div class="login-password-field position-relative">
                                <input type="password" name="password" id="password" required autocomplete="new-password"
                                    class="form-control form-control-solid bg-transparent @error('password') is-invalid @enderror" />
                                <button type="button" class="login-password-toggle" id="reset_pw_toggle"
                                    aria-pressed="false"
                                    aria-label="@lang('employee::general.show_password')"
                                    title="@lang('employee::general.show_password')">
                                    <i class="ki-outline ki-eye" id="reset_pw_toggle_icon"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="fv-row mb-6">
                            <label for="password_confirmation" class="form-label">{{ __('employee::fields.password_confirmation') }}</label>
                            <div class="login-password-field position-relative">
                                <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                                    class="form-control form-control-solid bg-transparent" />
                                <button type="button" class="login-password-toggle" id="reset_pw_toggle_c"
                                    aria-pressed="false"
                                    aria-label="@lang('employee::general.show_password')"
                                    title="@lang('employee::general.show_password')">
                                    <i class="ki-outline ki-eye" id="reset_pw_toggle_c_icon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid gap-3">
                            <button type="submit" class="btn btn-primary login-auth-submit" id="reset_pw_submit" data-kt-indicator="off">
                                <span class="indicator-label">@lang('employee::general.save_new_password')</span>
                                <span class="indicator-progress">@lang('employee::general.please_wait')
                                    <span class="spinner-border spinner-border-sm align-middle ms-2" aria-hidden="true"></span>
                                </span>
                            </button>
                            <a href="{{ route('login') }}" class="btn btn-light btn-active-light-primary">
                                @lang('employee::general.back_to_login')
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.js-references')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var loader = document.getElementById('initial-loader');
            if (loader) {
                loader.classList.add('login-loader--hide');
                setTimeout(function() {
                    loader.style.display = 'none';
                }, 300);
            }

            var alertEl = document.querySelector('.login-auth-card .alert-danger');
            if (alertEl) {
                setTimeout(function() {
                    alertEl.style.display = 'none';
                }, 14000);
            }

            function bindPwToggle(pwId, btnId, iconId) {
                var pw = document.getElementById(pwId);
                var btn = document.getElementById(btnId);
                var icon = document.getElementById(iconId);
                var showLabel = @json(__('employee::general.show_password'));
                var hideLabel = @json(__('employee::general.hide_password'));
                if (!pw || !btn || !icon) {
                    return;
                }
                btn.addEventListener('click', function() {
                    var isText = pw.getAttribute('type') === 'text';
                    pw.setAttribute('type', isText ? 'password' : 'text');
                    var nowHidden = pw.getAttribute('type') === 'password';
                    icon.classList.toggle('ki-eye', nowHidden);
                    icon.classList.toggle('ki-eye-slash', !nowHidden);
                    btn.setAttribute('aria-pressed', nowHidden ? 'false' : 'true');
                    btn.setAttribute('aria-label', nowHidden ? showLabel : hideLabel);
                    btn.setAttribute('title', nowHidden ? showLabel : hideLabel);
                });
            }
            bindPwToggle('password', 'reset_pw_toggle', 'reset_pw_toggle_icon');
            bindPwToggle('password_confirmation', 'reset_pw_toggle_c', 'reset_pw_toggle_c_icon');

            var langWrap = document.getElementById('login_lang_wrap');
            var langBtn = document.getElementById('login_lang_btn');
            if (langWrap && langBtn) {
                function setLangOpen(open) {
                    langWrap.classList.toggle('is-open', open);
                    langBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                }
                langBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    setLangOpen(!langWrap.classList.contains('is-open'));
                });
                document.addEventListener('click', function() {
                    setLangOpen(false);
                });
                langWrap.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        setLangOpen(false);
                    }
                });
            }

            var form = document.getElementById('kt_reset_password_form');
            var submit = document.getElementById('reset_pw_submit');
            if (form && submit) {
                form.addEventListener('submit', function() {
                    submit.setAttribute('data-kt-indicator', 'on');
                    submit.setAttribute('disabled', 'disabled');
                });
            }
        });
    </script>
</body>

</html>
