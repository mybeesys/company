@php
    $locale = app()->getLocale();
    $dir = str_starts_with((string) $locale, 'ar') ? 'rtl' : 'ltr';
    $switchLang = $locale === 'ar' ? 'en' : 'ar';
    $switchLabel = $locale === 'ar' ? 'English' : 'عربي';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#ebb81e">
    <title>{{ __('errors.tenant_missing_document_title') }} — {{ $brand }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/1-14.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #171717;
            --muted: #5c6470;
            --line: rgba(23, 23, 23, 0.08);
            --surface: #fffdf8;
            --soft: #f7f3ea;
            --gold: #ebb81e;
            --gold-deep: #b88912;
            --gold-soft: rgba(235, 184, 30, 0.18);
            --hex: polygon(25% 6%, 75% 6%, 100% 50%, 75% 94%, 25% 94%, 0 50%);
        }

        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            margin: 0;
            color: var(--ink);
            background: var(--soft);
            font-family: "Cairo", system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .tm {
            position: relative;
            min-height: 100vh;
            overflow: clip;
            isolation: isolate;
        }

        .tm__glow {
            position: absolute;
            inset: -10% -5% auto;
            height: min(72vh, 640px);
            background:
                radial-gradient(ellipse 55% 60% at 18% 28%, rgba(235, 184, 30, 0.34), transparent 70%),
                radial-gradient(ellipse 45% 50% at 82% 18%, rgba(184, 137, 18, 0.18), transparent 68%),
                linear-gradient(180deg, #fffdf8 0%, var(--soft) 100%);
            z-index: -2;
            pointer-events: none;
        }

        .tm__mesh {
            position: absolute;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            opacity: 0.45;
            background-image:
                linear-gradient(rgba(23, 23, 23, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(23, 23, 23, 0.035) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 70% 55% at 50% 20%, #000 20%, transparent 75%);
        }

        .tm__hex {
            position: absolute;
            background: var(--gold-soft);
            clip-path: var(--hex);
            pointer-events: none;
            z-index: -1;
            animation: tmDrift 11s ease-in-out infinite alternate;
        }
        .tm__hex--a {
            width: min(34vw, 280px);
            height: min(34vw, 280px);
            top: 6%;
            inset-inline-end: 4%;
            opacity: 0.9;
        }
        .tm__hex--b {
            width: min(22vw, 160px);
            height: min(22vw, 160px);
            top: 42%;
            inset-inline-start: 3%;
            opacity: 0.55;
            animation-delay: -4s;
            animation-duration: 9s;
        }
        .tm__hex--c {
            width: min(16vw, 110px);
            height: min(16vw, 110px);
            top: 18%;
            inset-inline-start: 18%;
            opacity: 0.35;
            animation-delay: -2s;
            animation-duration: 13s;
        }

        .tm__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.1rem clamp(1.1rem, 3vw, 2.4rem);
        }

        .tm__brand {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            text-decoration: none;
            color: inherit;
        }
        .tm__brand img {
            height: 2.85rem;
            width: auto;
            max-width: min(46vw, 11rem);
            object-fit: contain;
            display: block;
            filter: drop-shadow(0 6px 14px rgba(23, 23, 23, 0.08));
        }
        .tm__brand strong {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .tm__lang {
            appearance: none;
            border: 1px solid var(--line);
            background: rgba(255, 253, 248, 0.8);
            color: var(--ink);
            border-radius: 0.75rem;
            padding: 0.45rem 0.85rem;
            font: inherit;
            font-size: 0.82rem;
            font-weight: 800;
            text-decoration: none;
            transition: border-color 0.2s ease, transform 0.2s ease, background 0.2s ease;
        }
        .tm__lang:hover {
            border-color: var(--gold);
            background: var(--gold-soft);
            transform: translateY(-1px);
        }

        .tm__hero {
            display: grid;
            align-content: center;
            gap: 1.15rem;
            min-height: calc(100svh - 5.5rem);
            padding: 1.5rem clamp(1.1rem, 4vw, 3.5rem) 2.25rem;
            max-width: 56rem;
        }

        .tm__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            width: fit-content;
            font-size: 0.78rem;
            font-weight: 800;
            color: var(--gold-deep);
            letter-spacing: 0.04em;
            opacity: 0;
            animation: tmRise 0.7s ease 0.05s forwards;
        }
        .tm__eyebrow::before {
            content: "";
            width: 0.75rem;
            height: 0.85rem;
            background: var(--gold);
            clip-path: var(--hex);
        }

        .tm__logo-hero {
            display: inline-flex;
            align-items: center;
            justify-content: flex-start;
            width: fit-content;
            margin: 0.1rem 0 0.15rem;
            padding: 0.85rem 1.05rem;
            border-radius: 1.35rem;
            background:
                radial-gradient(circle at 30% 20%, rgba(255, 255, 255, 0.95), transparent 55%),
                linear-gradient(180deg, #fffef9 0%, #fff8e8 100%);
            border: 1px solid rgba(184, 137, 18, 0.22);
            box-shadow:
                0 18px 40px -24px rgba(184, 137, 18, 0.55),
                0 10px 24px -18px rgba(23, 23, 23, 0.18);
            opacity: 0;
            animation: tmRise 0.75s cubic-bezier(0.22, 1, 0.36, 1) 0.1s forwards;
        }
        .tm__logo-hero img {
            display: block;
            height: clamp(4.6rem, 14vw, 7.25rem);
            width: auto;
            max-width: min(88vw, 22rem);
            object-fit: contain;
            filter: drop-shadow(0 8px 18px rgba(23, 23, 23, 0.1));
        }

        .tm__headline {
            margin: 0;
            max-width: 22ch;
            font-size: clamp(1.35rem, 3.2vw, 1.85rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.35;
            opacity: 0;
            animation: tmRise 0.75s cubic-bezier(0.22, 1, 0.36, 1) 0.18s forwards;
        }

        .tm__lead {
            margin: 0;
            max-width: 36rem;
            font-size: 1.02rem;
            line-height: 1.7;
            color: var(--muted);
            font-weight: 600;
            opacity: 0;
            animation: tmRise 0.75s cubic-bezier(0.22, 1, 0.36, 1) 0.26s forwards;
        }

        .tm__host {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            width: fit-content;
            margin: 0;
            padding: 0.35rem 0.7rem;
            border: 1px dashed rgba(184, 137, 18, 0.4);
            border-radius: 0.65rem;
            background: rgba(255, 253, 248, 0.7);
            color: var(--gold-deep);
            font-size: 0.82rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            opacity: 0;
            animation: tmRise 0.75s ease 0.32s forwards;
        }
        .tm__host code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--ink);
        }

        .tm__ctas {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
            margin-top: 0.35rem;
            opacity: 0;
            animation: tmRise 0.75s cubic-bezier(0.22, 1, 0.36, 1) 0.38s forwards;
        }

        .tm__btn {
            appearance: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 3rem;
            padding: 0.65rem 1.25rem;
            border-radius: 0.9rem;
            border: 1px solid transparent;
            font: inherit;
            font-size: 0.95rem;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, border-color 0.18s ease;
        }
        .tm__btn:active { transform: translateY(1px); }
        .tm__btn--primary {
            background: linear-gradient(180deg, #f0c63a 0%, var(--gold) 55%, #d4a417 100%);
            color: #1a1a1a;
            box-shadow: 0 14px 28px -16px rgba(184, 137, 18, 0.9);
        }
        .tm__btn--primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px -14px rgba(184, 137, 18, 0.95);
        }
        .tm__btn--ghost {
            background: rgba(255, 253, 248, 0.85);
            color: var(--ink);
            border-color: var(--line);
        }
        .tm__btn--ghost:hover {
            border-color: var(--gold);
            background: #fff;
            transform: translateY(-2px);
        }

        .tm__proof {
            display: grid;
            gap: 0.85rem;
            margin: 0 clamp(1.1rem, 4vw, 3.5rem) 2.5rem;
            padding: 1.35rem 0 0;
            border-top: 1px solid var(--line);
            max-width: 40rem;
        }
        .tm__proof h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }
        .tm__proof ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 0.55rem;
        }
        .tm__proof li {
            display: grid;
            grid-template-columns: 0.85rem 1fr;
            gap: 0.65rem;
            align-items: start;
            color: var(--muted);
            font-size: 0.92rem;
            font-weight: 600;
            line-height: 1.55;
        }
        .tm__proof li::before {
            content: "";
            width: 0.7rem;
            height: 0.8rem;
            margin-top: 0.25rem;
            background: var(--gold);
            clip-path: var(--hex);
        }

        .tm__foot {
            padding: 0 clamp(1.1rem, 3vw, 2.4rem) 1.5rem;
            color: #8a8376;
            font-size: 0.78rem;
            font-weight: 600;
        }

        @keyframes tmRise {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: none; }
        }
        @keyframes tmDrift {
            from { transform: translateY(0) rotate(0deg); }
            to { transform: translateY(14px) rotate(5deg); }
        }

        @media (max-width: 640px) {
            .tm__hero { min-height: calc(100vh - 4.75rem); padding-top: 0.75rem; }
            .tm__logo-hero {
                padding: 0.75rem 0.85rem;
                border-radius: 1.15rem;
            }
            .tm__logo-hero img {
                height: clamp(3.8rem, 22vw, 5.4rem);
            }
            .tm__ctas { width: 100%; }
            .tm__btn { width: 100%; }
        }

        @media (prefers-reduced-motion: reduce) {
            .tm__hex, .tm__eyebrow, .tm__logo-hero, .tm__headline, .tm__lead, .tm__host, .tm__ctas {
                animation: none !important;
                opacity: 1 !important;
                transform: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="tm">
        <div class="tm__glow" aria-hidden="true"></div>
        <div class="tm__mesh" aria-hidden="true"></div>
        <span class="tm__hex tm__hex--a" aria-hidden="true"></span>
        <span class="tm__hex tm__hex--b" aria-hidden="true"></span>
        <span class="tm__hex tm__hex--c" aria-hidden="true"></span>

        <header class="tm__top">
            <a class="tm__brand" href="{{ $centralUrl }}">
                <img
                    src="{{ asset('assets/media/logos/1-15.png') }}"
                    alt="{{ $brand }}"
                    width="176"
                    height="48"
                    decoding="async"
                >
                <strong>{{ $brand }}</strong>
            </a>
            <a class="tm__lang" href="?lang={{ $switchLang }}">{{ $switchLabel }}</a>
        </header>

        <main class="tm__hero">
            <p class="tm__eyebrow">@lang('errors.tenant_missing_kicker')</p>
            <div class="tm__logo-hero" aria-label="{{ $brand }}">
                <img
                    src="{{ asset('assets/media/logos/1-15.png') }}"
                    alt="{{ $brand }}"
                    width="360"
                    height="120"
                    decoding="async"
                    fetchpriority="high"
                >
            </div>
            <h1 class="tm__headline">@lang('errors.tenant_missing_title')</h1>
            <p class="tm__lead">@lang('errors.tenant_missing_body')</p>
            <p class="tm__host">
                <span>@lang('errors.tenant_missing_host_label')</span>
                <code>{{ $host }}</code>
            </p>

            <div class="tm__ctas">
                <a class="tm__btn tm__btn--primary" href="{{ $registerUrl }}">
                    @lang('errors.tenant_missing_cta_start')
                </a>
                <a class="tm__btn tm__btn--ghost" href="{{ $loginUrl }}">
                    @lang('errors.tenant_missing_cta_login')
                </a>
            </div>
        </main>

        <section class="tm__proof" aria-label="{{ __('errors.tenant_missing_proof_title') }}">
            <h2>@lang('errors.tenant_missing_proof_title')</h2>
            <ul>
                <li>@lang('errors.tenant_missing_proof_1')</li>
                <li>@lang('errors.tenant_missing_proof_2')</li>
                <li>@lang('errors.tenant_missing_proof_3')</li>
            </ul>
        </section>

        <footer class="tm__foot">
            © {{ date('Y') }} {{ $brand }} — @lang('errors.tenant_missing_footer')
        </footer>
    </div>
</body>
</html>
