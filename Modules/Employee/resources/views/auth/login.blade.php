<!DOCTYPE html>
@php
    $local = app()->currentLocale();
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
@endphp
<html lang="{{ $local }}" dir="{{ $dir }}">

<head>
    <title>{{ config('app.name', 'Khaliyat Alnuzum Almutakamila') }} — @lang('employee::general.log_in')</title>
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
                        aria-label="{{ config('app.name', 'Khaliyat Alnuzum Almutakamila') }}">
                        <img src="{{ asset('assets/media/logos/1-11.png') }}" alt="" />
                    </a>
                    @if (tenant('id'))
                        <span class="login-auth-tenant">{{ ucfirst(tenant('id')) }}</span>
                    @endif
                    <h1 class="login-auth-title">@lang('employee::general.login_hero_title')</h1>
                    <p class="login-auth-sub">@lang('employee::general.login_hero_subtitle')</p>

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
                                    <li>
                                        @if ($error === 'subscription_expired')
                                            {{ __('employee::responses.subscription_expired') }}
                                        @else
                                            {{ $error }}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="login-auth-divider">
                        <span>@lang('employee::general.login_to_your_company')</span>
                    </div>

                    <form action="{{ route('login.postLogin') }}" method="POST" class="form w-100" id="kt_sign_in_form" novalidate>
                        @csrf

                        <div class="fv-row mb-6">
                            <x-form.input
                                class="bg-transparent"
                                name="email"
                                autocomplete="username"
                                :label="__('employee::general.email_or_user_name')"
                                :placeholder="__('employee::general.email_or_user_name')"
                            />
                        </div>

                        <div class="fv-row mb-2">
                            <label for="password" class="form-label fw-semibold fs-7 mb-1">{{ __('employee::fields.password') }}</label>
                            <div class="login-password-field position-relative">
                                <input type="password" name="password" id="password" autocomplete="current-password" required
                                    placeholder="{{ __('employee::fields.password') }}"
                                    class="form-control form-control-solid bg-transparent @error('password') is-invalid @enderror" />
                                <button type="button" class="login-password-toggle" id="login_pw_toggle"
                                    aria-pressed="false"
                                    aria-label="@lang('employee::general.show_password')"
                                    title="@lang('employee::general.show_password')">
                                    <i class="ki-outline ki-eye" id="login_pw_toggle_icon"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="login-auth-actions">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="remember" id="login_remember" value="1"
                                    @checked(old('remember')) />
                                <label class="form-check-label" for="login_remember">@lang('employee::general.remember_me')</label>
                            </div>
                            <a href="{{ route('password.request') }}" class="link-primary fw-semibold small">@lang('employee::general.forgot_password')</a>
                        </div>

                        <div class="d-grid">
                            <button type="submit" id="kt_sign_in_submit" class="btn btn-primary login-auth-submit" data-kt-indicator="off">
                                <span class="indicator-label">@lang('employee::general.sign_in')</span>
                                <span class="indicator-progress">@lang('employee::general.please_wait')
                                    <span class="spinner-border spinner-border-sm align-middle ms-2" aria-hidden="true"></span>
                                </span>
                            </button>
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

            var pw = document.getElementById('password');
            var btn = document.getElementById('login_pw_toggle');
            var icon = document.getElementById('login_pw_toggle_icon');
            var showLabel = @json(__('employee::general.show_password'));
            var hideLabel = @json(__('employee::general.hide_password'));
            if (pw && btn && icon) {
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

            var form = document.getElementById('kt_sign_in_form');
            var submit = document.getElementById('kt_sign_in_submit');
            if (form && submit) {
                form.addEventListener('submit', function() {
                    /* Metronic: يظهر indicator-progress فقط عند data-kt-indicator="on" على الزر */
                    submit.setAttribute('data-kt-indicator', 'on');
                    submit.setAttribute('disabled', 'disabled');
                });
            }

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
        });
    </script>
</body>

</html>
