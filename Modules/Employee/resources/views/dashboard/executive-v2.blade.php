@extends('layouts.app')
@section('title', __('menuItemLang.dashboard'))

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css" rel="stylesheet" type="text/css">
    @include('employee::dashboard.partials.tabs-styles')
    <style>
        .ed-boot-fallback {
            display: grid;
            gap: 0.85rem;
            padding: 1.25rem 0.25rem 2rem;
        }
        .ed-boot-fallback__hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 1rem 1.15rem;
            border-radius: 1rem;
            border: 1px solid rgba(235, 184, 30, 0.28);
            background:
                radial-gradient(700px 180px at 0% 0%, rgba(235, 184, 30, 0.18), transparent 55%),
                linear-gradient(180deg, #fffdf6 0%, #ffffff 70%);
            box-shadow: 0 8px 24px rgba(30, 33, 41, 0.05);
        }
        .ed-boot-fallback__kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.78rem;
            font-weight: 800;
            color: #8a6a0d;
            margin-bottom: 0.35rem;
        }
        .ed-boot-fallback__hex {
            width: 0.7rem;
            height: 0.78rem;
            background: linear-gradient(145deg, #f5d76e, #ebb81e 45%, #b88912);
            clip-path: polygon(25% 6%, 75% 6%, 100% 50%, 75% 94%, 25% 94%, 0 50%);
        }
        .ed-boot-fallback__title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: #1e2129;
        }
        .ed-boot-fallback__sub {
            margin: 0.25rem 0 0;
            color: #7e8299;
            font-size: 0.88rem;
        }
        .ed-boot-fallback__grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
        }
        @media (max-width: 991.98px) {
            .ed-boot-fallback__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        .ed-boot-fallback__card {
            height: 96px;
            border-radius: 0.85rem;
            border: 1px solid #eff2f5;
            background: linear-gradient(90deg, #eef1f6 25%, #ffffff 50%, #eef1f6 75%);
            background-size: 200% 100%;
            animation: ed-boot-shimmer 1.2s ease-in-out infinite;
        }
        @keyframes ed-boot-shimmer {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }
        .ed-boot-fallback__error {
            display: none;
            margin-top: 0.75rem;
            padding: 0.85rem 1rem;
            border-radius: 0.85rem;
            background: #fff8e8;
            border: 1px solid rgba(235, 184, 30, 0.35);
            color: #8a6a0d;
            font-weight: 700;
            font-size: 0.88rem;
        }
        .ed-boot-fallback.is-error .ed-boot-fallback__grid { display: none; }
        .ed-boot-fallback.is-error .ed-boot-fallback__error { display: block; }
    </style>
@endsection

@section('content')
    @php
        $activeDashboardTab = 'overview';
    @endphp
    <div class="container-fluid ed-shell">
        <div id="edHubTabsPanel" class="ed-hub-tabs-panel" hidden>
            @include('employee::dashboard.partials.tabs-nav')
        </div>
        <div
            id="executive-dashboard-root"
            data-bootstrap-url="{{ route('dashboard-v2.bootstrap') }}"
            data-data-url="{{ route('dashboard-v2.data') }}"
            data-widget-url="{{ url('dashboard-v2/api/widgets') }}"
            data-locale="{{ app()->getLocale() }}"
            data-dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
        >
            <div class="ed-boot-fallback" data-ed-boot-fallback>
                <div class="ed-boot-fallback__hero">
                    <div>
                        <div class="ed-boot-fallback__kicker">
                            <span class="ed-boot-fallback__hex" aria-hidden="true"></span>
                            @lang('general::general.hive_chip_kicker')
                        </div>
                        <h1 class="ed-boot-fallback__title">@lang('menuItemLang.dashboard')</h1>
                        <p class="ed-boot-fallback__sub">@lang('general::general.hive_dashboard_loading')</p>
                    </div>
                    <a href="{{ route('subscription.manage') }}" class="btn btn-sm btn-warning fw-bold">
                        @lang('general::general.manage_subscription')
                    </a>
                </div>
                <div class="ed-boot-fallback__grid" aria-hidden="true">
                    <div class="ed-boot-fallback__card"></div>
                    <div class="ed-boot-fallback__card"></div>
                    <div class="ed-boot-fallback__card"></div>
                    <div class="ed-boot-fallback__card"></div>
                </div>
                <div class="ed-boot-fallback__error" data-ed-boot-error>
                    @lang('general::general.hive_dashboard_load_error')
                    <a href="{{ url('/dashboard') }}" class="ms-2">@lang('general::general.retry')</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    {{-- Matches vite.config.js buildDirectory (not the default public/build manifest). --}}
    @vite(['resources/components/executive-dashboard/main.jsx'], 'tenancy/assets/build')
    <script>
        (function () {
            var root = document.getElementById('executive-dashboard-root');
            if (!root) return;
            var fallback = root.querySelector('[data-ed-boot-fallback]');
            setTimeout(function () {
                if (!root.querySelector('.ed-page') && fallback) {
                    fallback.classList.add('is-error');
                }
            }, 12000);
        })();
    </script>
@endsection
