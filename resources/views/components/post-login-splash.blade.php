@php
    $splashUser = auth()->user();
    $splashUserName = $splashUser?->translated_name
        ?? $splashUser?->{get_name_by_lang()}
        ?? $splashUser?->user_name
        ?? $splashUser?->name
        ?? '';
    $splashWelcomeSep = app()->getLocale() === 'ar' ? '، ' : ', ';
    $splashWelcomeText = $splashUserName !== ''
        ? __('general.post_login_splash_welcome').$splashWelcomeSep.$splashUserName
        : __('general.post_login_splash_welcome');
@endphp
<div id="post-login-splash" class="post-login-splash" role="dialog" aria-modal="true" aria-label="{{ $splashWelcomeText }}">
    <div class="post-login-splash__backdrop"></div>
    <div class="post-login-splash__content">
        <div class="post-login-splash__logo-wrap">
            <img
                src="{{ asset('assets/media/logos/1-12.png') }}"
                alt="@lang('general.post_login_splash_app_name')"
                class="post-login-splash__logo"
            />
        </div>
        <p class="post-login-splash__welcome">
            @lang('general.post_login_splash_welcome')@if ($splashUserName !== '')<span class="post-login-splash__sep">{{ $splashWelcomeSep }}</span><span class="post-login-splash__user-name">{{ $splashUserName }}</span>@endif
        </p>
        <h1 class="post-login-splash__app-name">@lang('general.post_login_splash_app_name')</h1>
        <p class="post-login-splash__message">@lang('general.post_login_splash_message')</p>
    </div>
</div>

<style>
    .post-login-splash {
        position: fixed;
        inset: 0;
        z-index: 10050;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        pointer-events: all;
    }

    .post-login-splash__backdrop {
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 95% 75% at 50% -5%, rgba(233, 183, 31, 0.42), transparent 58%),
            radial-gradient(ellipse 55% 45% at 100% 100%, rgba(233, 183, 31, 0.22), transparent 52%),
            radial-gradient(ellipse 45% 40% at 0% 85%, rgba(255, 255, 255, 0.95), transparent 48%),
            linear-gradient(155deg, #ffffff 0%, #fff9e8 22%, #fce9a8 48%, #fff4cc 68%, #ffffff 100%);
    }

    [data-bs-theme="dark"] .post-login-splash__backdrop {
        background:
            radial-gradient(ellipse 95% 75% at 50% -5%, rgba(233, 183, 31, 0.28), transparent 58%),
            radial-gradient(ellipse 55% 45% at 100% 100%, rgba(233, 183, 31, 0.14), transparent 52%),
            linear-gradient(155deg, #1a1810 0%, #2a2418 35%, #1f1c14 65%, #151521 100%);
    }

    .post-login-splash__content {
        position: relative;
        z-index: 1;
        text-align: center;
        padding: 2rem 1.75rem;
        max-width: 34rem;
        width: 100%;
    }

    .post-login-splash__logo-wrap {
        display: flex;
        justify-content: center;
        margin-bottom: 1.75rem;
    }

    .post-login-splash__logo {
        height: clamp(72px, 14vw, 108px);
        width: auto;
        object-fit: contain;
        transform-origin: center center;
        will-change: transform, filter, opacity;
        filter: drop-shadow(0 12px 28px rgba(var(--bs-primary-rgb), .22));
        animation:
            postLoginSplashLogoIn 1.5s cubic-bezier(0.34, 1.15, 0.48, 1) both,
            postLoginSplashLogoPulse 2.8s ease-in-out 1.5s infinite;
    }

    .post-login-splash__welcome {
        margin: 0 0 0.5rem;
        font-size: 1.05rem;
        font-weight: 600;
        color: var(--bs-gray-700);
        letter-spacing: 0.01em;
        animation: postLoginSplashTextIn 0.9s ease 0.7s both;
    }

    .post-login-splash__user-name {
        display: inline-block;
        font-weight: 800;
        color: var(--bs-primary, #e9b71f);
        margin-inline-start: 0.15rem;
    }

    [data-bs-theme="dark"] .post-login-splash__welcome {
        color: var(--bs-gray-300);
    }

    [data-bs-theme="dark"] .post-login-splash__user-name {
        color: #f0c94a;
    }

    .post-login-splash__app-name {
        margin: 0 0 0.85rem;
        font-size: clamp(1.45rem, 4vw, 1.95rem);
        font-weight: 800;
        line-height: 1.25;
        color: var(--bs-gray-900);
        letter-spacing: -0.02em;
        animation: postLoginSplashTextIn 0.9s ease 1.05s both;
    }

    [data-bs-theme="dark"] .post-login-splash__app-name {
        color: var(--bs-gray-100);
    }

    .post-login-splash__message {
        margin: 0 auto;
        max-width: 28rem;
        font-size: 0.95rem;
        line-height: 1.65;
        color: var(--bs-gray-600);
        animation: postLoginSplashTextIn 0.9s ease 1.35s both;
    }

    [data-bs-theme="dark"] .post-login-splash__message {
        color: var(--bs-gray-500);
    }

    .post-login-splash--out {
        pointer-events: none;
    }

    .post-login-splash--out .post-login-splash__backdrop {
        animation: postLoginSplashFadeOut 0.7s ease forwards;
    }

    .post-login-splash--out .post-login-splash__content {
        animation: postLoginSplashContentOut 0.6s ease forwards;
    }

    .post-login-splash--out .post-login-splash__logo {
        animation: none !important;
    }

    @keyframes postLoginSplashLogoIn {
        0% {
            opacity: 0;
            transform: translateX(52vw) rotate(32deg) scale(0.78);
        }
        45% {
            opacity: 1;
            transform: translateX(6vw) rotate(-10deg) scale(1.04);
        }
        68% {
            transform: translateX(-1.5vw) rotate(4deg) scale(0.98);
        }
        82% {
            transform: translateX(0.6vw) rotate(-2deg) scale(1.01);
        }
        100% {
            opacity: 1;
            transform: translateX(0) rotate(0deg) scale(1);
        }
    }

    @keyframes postLoginSplashLogoPulse {
        0%, 100% {
            transform: translateX(0) rotate(0deg) scale(1);
            filter: drop-shadow(0 12px 28px rgba(var(--bs-primary-rgb), .22));
        }
        50% {
            transform: translateX(0) rotate(0deg) scale(1.06);
            filter: drop-shadow(0 18px 40px rgba(var(--bs-primary-rgb), .34));
        }
    }

    @keyframes postLoginSplashTextIn {
        from {
            opacity: 0;
            transform: translateY(14px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes postLoginSplashFadeOut {
        to {
            opacity: 0;
        }
    }

    @keyframes postLoginSplashContentOut {
        to {
            opacity: 0;
            transform: scale(0.98);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .post-login-splash__logo,
        .post-login-splash__welcome,
        .post-login-splash__app-name,
        .post-login-splash__message {
            animation: none !important;
        }

        .post-login-splash--out .post-login-splash__backdrop,
        .post-login-splash--out .post-login-splash__content {
            animation: none !important;
            opacity: 0;
        }
    }
</style>
