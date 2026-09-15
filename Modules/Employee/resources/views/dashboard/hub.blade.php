@extends('layouts.app')
@section('title', __('menuItemLang.dashboard'))

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css" rel="stylesheet" type="text/css">
    <style>
        :root {
            --cc-ink: #1e2129;
            --cc-muted: #7e8299;
            --cc-line: #eff2f5;
            --cc-surface: #ffffff;
            --cc-soft: #f9f9f9;
            --cc-accent: var(--bs-primary);
            --cc-accent-soft: var(--bs-primary-light);
            --cc-accent-text: var(--bs-primary-text-emphasis, #5e490f);
            --cc-header-offset: calc(var(--bs-app-header-height, 70px) + 12px);
            --cc-sales: var(--bs-primary);
            --cc-purchases: #7239ea;
            --cc-expenses: #f1416c;
            --cc-net: #50cd89;
            --cc-radius: 16px;
            --cc-shadow: 0 8px 24px rgba(30, 33, 41, 0.06);
        }

        .cc-shell {
            --bs-gutter-x: 1.25rem;
            padding-bottom: 3rem;
        }

        .cc-hero {
            position: relative;
            overflow: hidden;
            border-radius: calc(var(--cc-radius) + 4px);
            background:
                radial-gradient(1000px 260px at 0% 0%, rgba(var(--bs-primary-rgb), 0.16), transparent 55%),
                radial-gradient(800px 220px at 100% 0%, rgba(30, 33, 41, 0.05), transparent 50%),
                linear-gradient(180deg, #fffdf6 0%, #ffffff 72%);
            border: 1px solid var(--cc-line);
            box-shadow: var(--cc-shadow);
            padding: 1.35rem 1.5rem;
            margin-bottom: 1.25rem;
        }

        .cc-hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--cc-accent-text);
            background: var(--cc-accent-soft);
            border-radius: 999px;
            padding: .28rem .7rem;
            margin-bottom: .65rem;
        }

        .cc-hero h1 {
            font-size: clamp(1.35rem, 2vw, 1.85rem);
            font-weight: 800;
            color: var(--cc-ink);
            margin: 0 0 .35rem;
            letter-spacing: -.02em;
        }

        .cc-hero-sub {
            color: var(--cc-muted);
            font-size: .95rem;
            line-height: 1.65;
            max-width: 46rem;
            margin: 0;
        }

        .cc-filter {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid var(--cc-line);
            border-radius: 14px;
            padding: .85rem 1rem;
            backdrop-filter: blur(6px);
        }

        .cc-layout {
            display: grid;
            grid-template-columns: 220px minmax(0, 1fr);
            gap: 1.25rem;
            align-items: start;
        }

        /* Stay under fixed app header; never cover navbar */
        .cc-rail {
            position: sticky;
            top: var(--cc-header-offset);
            z-index: 25;
            align-self: start;
            max-height: calc(100vh - var(--cc-header-offset) - 16px);
        }

        .cc-rail-inner {
            background: var(--cc-surface);
            border: 1px solid var(--cc-line);
            border-radius: var(--cc-radius);
            box-shadow: var(--cc-shadow);
            padding: .75rem;
            max-height: inherit;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .cc-rail-label {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--cc-muted);
            padding: .35rem .55rem .55rem;
            flex: 0 0 auto;
        }

        .cc-rail-nav {
            display: flex;
            flex-direction: column;
            gap: .25rem;
            overflow: auto;
            flex: 1 1 auto;
            min-height: 0;
            padding-bottom: .15rem;
            scrollbar-width: thin;
        }

        @media (max-width: 991.98px) {
            .cc-layout {
                grid-template-columns: 1fr;
            }
            .cc-rail {
                top: var(--cc-header-offset);
                max-height: none;
                margin-bottom: .35rem;
            }
            .cc-rail-inner {
                max-height: none;
            }
            .cc-rail-nav {
                flex-direction: row;
                overflow-x: auto;
                overflow-y: hidden;
            }
            .cc-rail-nav .cc-rail-link {
                white-space: nowrap;
                flex: 0 0 auto;
            }
        }

        .cc-rail-link {
            display: flex;
            align-items: center;
            gap: .55rem;
            border: 0;
            background: transparent;
            color: #3f4254;
            text-decoration: none !important;
            border-radius: 10px;
            padding: .6rem .7rem;
            font-size: .86rem;
            font-weight: 600;
            transition: background .15s ease, color .15s ease;
        }

        .cc-rail-link i {
            width: 1.1rem;
            text-align: center;
            opacity: .75;
        }

        .cc-rail-link:hover {
            background: var(--cc-soft);
            color: var(--cc-ink);
        }

        .cc-rail-link.is-active {
            background: var(--cc-accent-soft);
            color: var(--cc-accent-text);
            box-shadow: inset 3px 0 0 var(--cc-accent);
        }

        [dir="rtl"] .cc-rail-link.is-active {
            box-shadow: inset -3px 0 0 var(--cc-accent);
        }

        .cc-rail-link.is-active i {
            opacity: 1;
            color: var(--cc-accent);
        }

        .cc-section {
            scroll-margin-top: calc(var(--cc-header-offset) + 8px);
            margin-bottom: 1.35rem;
        }

        .cc-section-chrome {
            background: var(--cc-surface);
            border: 1px solid var(--cc-line);
            border-radius: var(--cc-radius);
            box-shadow: var(--cc-shadow);
            overflow: hidden;
        }

        .cc-section-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: .85rem;
            padding: 1.05rem 1.25rem;
            border-bottom: 1px solid var(--cc-line);
            background: linear-gradient(180deg, #fffef9 0%, #ffffff 100%);
        }

        .cc-section-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--cc-ink);
            letter-spacing: -.01em;
        }

        .cc-section-desc {
            margin: .3rem 0 0;
            color: var(--cc-muted);
            font-size: .86rem;
            line-height: 1.55;
            max-width: 46rem;
        }

        .cc-section-body {
            padding: 1rem 1.15rem 1.25rem;
        }

        .cc-section-body.is-embed {
            padding: 0;
            background: #fafbfc;
            min-height: 220px;
            position: relative;
        }

        .cc-embed-frame {
            display: block;
            width: 100%;
            border: 0;
            min-height: 220px;
            height: 220px;
            background: transparent;
            opacity: 0;
            transition: opacity .25s ease;
        }

        .cc-embed-frame.is-visible {
            opacity: 1;
        }

        .cc-embed-loading {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            color: var(--cc-muted);
            font-size: .9rem;
            font-weight: 600;
            background: linear-gradient(180deg, rgba(255, 253, 246, .92), rgba(255, 255, 255, .96));
        }

        .cc-embed-loading.is-hidden {
            display: none;
        }

        .dash-card {
            border: 0;
            border-radius: 14px;
            box-shadow: none;
            border: 1px solid var(--cc-line);
            background: #fff;
        }
        .kpi-card {
            border-radius: 14px;
            border: 1px solid var(--cc-line);
            background: #fff;
            padding: 1rem 1.05rem;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            inset-inline-start: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #e4e6ef;
        }
        .kpi-soft-sales::before { background: var(--cc-sales); }
        .kpi-soft-purchases::before { background: var(--cc-purchases); }
        .kpi-soft-expenses::before { background: var(--cc-expenses); }
        .kpi-soft-net::before { background: var(--cc-net); }
        .kpi-soft-sales { background: linear-gradient(135deg, var(--bs-primary-light), #fff); }
        .kpi-soft-purchases { background: linear-gradient(135deg, #f8f5ff, #fff); }
        .kpi-soft-expenses { background: linear-gradient(135deg, #fff5f8, #fff); }
        .kpi-soft-net { background: linear-gradient(135deg, #e8fff3, #fff); }
        .kpi-title { font-size: .75rem; color: var(--cc-muted); margin-bottom: .45rem; font-weight: 600; }
        .kpi-value { font-size: 1.35rem; font-weight: 800; color: var(--cc-ink); line-height: 1.25; letter-spacing: -.02em; }
        .kpi-meta { margin-top: .45rem; font-size: .78rem; }
        .quick-action-btn {
            border-radius: 12px;
            border: 1px solid var(--cc-line);
            background: #fff;
            text-decoration: none;
            padding: .85rem .6rem;
            text-align: center;
            min-height: 92px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: .45rem;
            color: var(--cc-ink) !important;
            transition: transform .15s ease, box-shadow .15s ease;
            font-size: .8rem;
            font-weight: 600;
        }
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(30, 33, 41, 0.08);
            border-color: rgba(var(--bs-primary-rgb), 0.45);
        }
        .filter-box { background: var(--cc-soft); border: 1px solid var(--cc-line); border-radius: 12px; padding: 12px; }
        .table-wrap { max-height: 380px; overflow-y: auto; }
    </style>
@endsection

@section('content')
    @php
        $embedSections = $dashboardEmbedSections ?? [];
        $navItems = $dashboardTabs ?? [];
        $activeSection = $activeSection ?? 'overview';
    @endphp

    <div class="container-fluid py-3 cc-shell" id="dashboardCommandCenter"
         data-active-section="{{ $activeSection }}">

        <header class="cc-hero">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-4">
                <div class="flex-grow-1" style="min-width: min(100%, 280px);">
                    <div class="cc-hero-kicker">
                        <i class="fas fa-chart-pie"></i>
                        @lang('employee::main.dashboard_hub_title')
                    </div>
                    <h1>@lang('menuItemLang.dashboard')</h1>
                    <p class="cc-hero-sub">@lang('employee::main.dashboard_hub_subtitle')</p>
                </div>
                <form method="GET" action="{{ route('dashboard') }}" class="cc-filter d-flex flex-wrap align-items-end gap-3">
                    @if ($activeSection && $activeSection !== 'overview')
                        <input type="hidden" name="tab" value="{{ $activeSection }}">
                    @endif
                    <div>
                        <label class="form-label mb-1 fs-8 text-muted">{{ app()->getLocale() === 'ar' ? 'من تاريخ' : 'From' }}</label>
                        <input type="date" name="start_date" class="form-control form-control-sm form-control-solid" value="{{ $startDate->toDateString() }}">
                    </div>
                    <div>
                        <label class="form-label mb-1 fs-8 text-muted">{{ app()->getLocale() === 'ar' ? 'إلى تاريخ' : 'To' }}</label>
                        <input type="date" name="end_date" class="form-control form-control-sm form-control-solid" value="{{ $endDate->toDateString() }}">
                    </div>
                    <button class="btn btn-sm btn-primary">{{ app()->getLocale() === 'ar' ? 'تطبيق' : 'Apply' }}</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-light">@lang('general.clear_filters')</a>
                </form>
            </div>
        </header>

        <div class="cc-layout">
            @if (count($navItems) > 1)
                <aside class="cc-rail" aria-label="@lang('employee::main.dashboard_jump_to')">
                    <div class="cc-rail-inner">
                        <div class="cc-rail-label">@lang('employee::main.dashboard_jump_to')</div>
                        <nav class="cc-rail-nav" id="dashboardSectionNav">
                            @foreach ($navItems as $tab)
                                @php
                                    $labelKey = $tab['label'];
                                    $tabLabel = __($labelKey);
                                    if ($tabLabel === $labelKey) {
                                        $tabLabel = $tab['id'];
                                    }
                                    $sectionId = $tab['section_id'] ?? ('section-'.$tab['id']);
                                @endphp
                                <a href="#{{ $sectionId }}"
                                   class="cc-rail-link {{ ($activeSection === $tab['id']) ? 'is-active' : '' }}"
                                   data-section-target="{{ $tab['id'] }}">
                                    <i class="{{ $tab['icon'] }}"></i>
                                    <span>{{ $tabLabel }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>
            @endif

            <div class="cc-main">
                @php
                    $showOverview = collect($navItems)->contains(fn ($t) => ($t['id'] ?? '') === 'overview');
                @endphp

                @if ($showOverview)
                <section class="cc-section" id="section-overview" data-section-id="overview">
                    <div class="cc-section-chrome">
                        <div class="cc-section-head">
                            <div>
                                <h2 class="cc-section-title">@lang('menuItemLang.dashboard')</h2>
                                <p class="cc-section-desc">@lang('employee::main.dashboard_section_overview_desc')</p>
                            </div>
                        </div>
                        <div class="cc-section-body">
                            @include('employee::dashboard.overview')
                        </div>
                    </div>
                </section>
                @endif

                @foreach ($embedSections as $section)
                    @php
                        $labelKey = $section['label'];
                        $sectionLabel = __($labelKey);
                        if ($sectionLabel === $labelKey) {
                            $sectionLabel = $section['id'];
                        }
                        $sectionId = $section['section_id'] ?? ('section-'.$section['id']);
                        $descKey = $section['description_key'] ?? ('employee::main.dashboard_section_'.$section['id'].'_desc');
                    @endphp
                    <section class="cc-section"
                             id="{{ $sectionId }}"
                             data-section-id="{{ $section['id'] }}"
                             data-embed-url="{{ $section['embed_url'] }}">
                        <div class="cc-section-chrome">
                            <div class="cc-section-head">
                                <div>
                                    <h2 class="cc-section-title">
                                        <i class="{{ $section['icon'] }} me-2 opacity-75"></i>{{ $sectionLabel }}
                                    </h2>
                                    <p class="cc-section-desc">@lang($descKey)</p>
                                </div>
                                @if (! empty($section['url']))
                                    <a href="{{ $section['url'] }}" class="btn btn-sm btn-light-primary" target="_blank" rel="noopener">
                                        <i class="fas fa-external-link-alt me-1"></i>
                                        @lang('employee::main.dashboard_open_full')
                                    </a>
                                @endif
                            </div>
                            <div class="cc-section-body is-embed">
                                <div class="cc-embed-loading" data-embed-loading>
                                    <span class="spinner-border spinner-border-sm text-muted" role="status" aria-hidden="true"></span>
                                    @lang('employee::main.dashboard_section_loading')
                                </div>
                                <iframe
                                    class="cc-embed-frame"
                                    title="{{ $sectionLabel }}"
                                    data-dashboard-embed
                                    loading="lazy"
                                    referrerpolicy="same-origin"
                                ></iframe>
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script src="{{ asset('js/dashboard-hub.js') }}?v=3"></script>
    <script>
        (function () {
            var lang = '{{ app()->getLocale() }}';
            var chartEl = document.querySelector('#sales-expenses-chart');
            if (!chartEl || typeof ApexCharts === 'undefined') return;

            var monthLabels = lang === 'ar' ? @json($monthLabelsAr) : @json($monthLabelsEn);
            function fmtn(v) {
                return Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            new ApexCharts(chartEl, {
                series: [
                    { name: lang === 'ar' ? 'المبيعات' : 'Sales', data: @json($salesArray) },
                    { name: lang === 'ar' ? 'المصروفات' : 'Expenses', data: @json($expensesArray) }
                ],
                chart: { type: 'bar', height: 340, toolbar: { show: false }, fontFamily: 'Tajawal, sans-serif' },
                colors: ['#e9b71f', '#1e2129'],
                plotOptions: { bar: { horizontal: false, columnWidth: '48%', borderRadius: 6 } },
                dataLabels: { enabled: false },
                stroke: { show: false },
                xaxis: { categories: monthLabels, labels: { style: { fontSize: '12px' } } },
                yaxis: {
                    title: { text: lang === 'ar' ? 'المبلغ' : 'Amount', offsetX: lang === 'ar' ? -30 : 0 },
                    labels: { formatter: function (val) { return fmtn(val); } }
                },
                tooltip: { y: { formatter: function (val) { return fmtn(val); } } },
                legend: { position: 'top', horizontalAlign: 'right', fontSize: '13px' },
                grid: { borderColor: '#eff2f5', strokeDashArray: 4 }
            }).render();
        })();
    </script>
@endsection
