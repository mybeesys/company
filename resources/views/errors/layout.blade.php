<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $dir = str_starts_with((string) $locale, 'ar') ? 'rtl' : 'ltr';
@endphp
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#e9b71f">
    <title>@yield('title', brand_short_name())</title>
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/1-14.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --err-gold: #e9b71f;
            --err-gold-soft: rgba(233, 183, 31, .16);
            --err-ink: #1e2129;
            --err-muted: #6b7280;
            --err-line: #efe8d4;
            --err-card: #fff;
            --err-bg: #f7f4ec;
            --err-shadow: 0 24px 60px -28px rgba(30, 33, 41, .18);
            --err-radius: 28px;
        }

        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            margin: 0;
            font-family: Cairo, "Segoe UI", Tahoma, sans-serif;
            color: var(--err-ink);
            background: var(--err-bg);
            -webkit-font-smoothing: antialiased;
        }

        .err-page {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.25rem;
        }

        .err-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            pointer-events: none;
            animation: errOrb 14s ease-in-out infinite;
        }
        .err-orb--a {
            width: 22rem;
            height: 22rem;
            background: rgba(233, 183, 31, .22);
            top: -6rem;
            inset-inline-end: -4rem;
        }
        .err-orb--b {
            width: 16rem;
            height: 16rem;
            background: rgba(233, 183, 31, .12);
            bottom: -5rem;
            inset-inline-start: -3rem;
            animation-delay: -5s;
        }

        .err-brand {
            position: relative;
            z-index: 1;
            margin-bottom: 1.75rem;
            opacity: 0;
            animation: errRise .7s ease .05s forwards;
        }
        .err-brand img {
            height: 88px;
            width: auto;
            max-width: min(72vw, 280px);
            display: block;
            object-fit: contain;
            filter: drop-shadow(0 8px 18px rgba(30, 33, 41, .08));
        }

        .err-card {
            position: relative;
            z-index: 1;
            width: min(100%, 28.5rem);
            background: var(--err-card);
            border: 1px solid var(--err-line);
            border-radius: var(--err-radius);
            box-shadow: var(--err-shadow);
            padding: 2.15rem 1.75rem 1.6rem;
            text-align: center;
            opacity: 0;
            animation: errRise .75s cubic-bezier(.22, 1, .36, 1) .12s forwards;
        }

        .err-mark {
            width: 5.25rem;
            height: 5.25rem;
            margin: 0 auto 1.25rem;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at 50% 30%, #fff 0%, transparent 55%),
                linear-gradient(180deg, #fff8e1 0%, #f8efcf 100%);
            border: 1px solid #f0e2b0;
            color: #b88816;
            animation: errBreathe 4.2s ease-in-out .6s infinite;
        }
        .err-mark svg {
            width: 2.05rem;
            height: 2.05rem;
        }

        .err-kicker {
            margin: 0 0 .45rem;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #b88816;
        }
        .err-title {
            margin: 0 0 .7rem;
            font-size: clamp(1.35rem, 3vw, 1.7rem);
            font-weight: 800;
            line-height: 1.35;
            color: var(--err-ink);
        }
        .err-body,
        .err-hint {
            margin: 0 auto;
            max-width: 24.5rem;
            font-size: .95rem;
            line-height: 1.8;
            color: var(--err-muted);
        }
        .err-hint {
            margin-top: .7rem;
            font-size: .88rem;
            color: #8a8376;
        }

        .err-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .6rem;
            margin-top: 1.55rem;
        }
        .err-btn {
            appearance: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.75rem;
            padding: .55rem 1.15rem;
            border-radius: 999px;
            border: 1px solid transparent;
            font: inherit;
            font-size: .9rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease, border-color .15s ease;
        }
        .err-btn:active { transform: translateY(1px); }
        .err-btn--primary {
            background: var(--err-gold);
            color: #1e2129;
            box-shadow: 0 10px 20px -12px rgba(233, 183, 31, .9);
        }
        .err-btn--primary:hover { background: #d1a41b; }
        .err-btn--ghost {
            background: #fff;
            color: var(--err-ink);
            border-color: #eadfb8;
        }
        .err-btn--ghost:hover { background: #fffaf0; }

        .err-foot {
            margin-top: 1.15rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        .err-foot a {
            color: #9a927f;
            font-size: .82rem;
            font-weight: 600;
            text-decoration: none;
        }
        .err-foot a:hover { color: #5e490f; }

        @keyframes errRise {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: none; }
        }
        @keyframes errBreathe {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.045); }
        }
        @keyframes errOrb {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-18px, 12px); }
        }

        @media (prefers-reduced-motion: reduce) {
            .err-orb, .err-brand, .err-card, .err-mark {
                animation: none !important;
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <main class="err-page">
        <span class="err-orb err-orb--a" aria-hidden="true"></span>
        <span class="err-orb err-orb--b" aria-hidden="true"></span>
        <span class="err-brand">
            <img src="{{ asset('assets/media/logos/1-01.png') }}" alt="{{ brand_short_name() }}">
        </span>
        @yield('content')
    </main>
    @yield('scripts')
</body>
</html>
