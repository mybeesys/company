@extends('layouts.app')
@section('title', __('menuItemLang.dashboard'))

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css" rel="stylesheet" type="text/css">
    <style>
        .dash-card { border: 0; border-radius: 14px; box-shadow: 0 6px 22px rgba(62, 57, 107, 0.08); }
        .kpi-card { border-radius: 14px; border: 1px solid #eef1f7; background: #fff; padding: 18px; height: 100%; }
        .kpi-soft-sales { background: #f0f7ff; border-color: #d9e9fb; }
        .kpi-soft-purchases { background: #f8f5ff; border-color: #e8dcff; }
        .kpi-soft-expenses { background: #fff8f5; border-color: #ffe1d7; }
        .kpi-soft-net { background: #f1faf6; border-color: #d7f3e8; }
        .kpi-title { font-size: 12px; color: #7e8299; margin-bottom: 8px; }
        .kpi-value { font-size: 22px; font-weight: 700; color: #181c32; line-height: 1.2; }
        .kpi-meta { margin-top: 7px; font-size: 12px; }
        .quick-action-btn {
            border-radius: 12px; border: 1px solid #edf0f6; background: #fff; text-decoration: none;
            padding: 14px 10px; text-align: center; min-height: 106px; display: flex; align-items: center;
            justify-content: center; flex-direction: column; gap: 8px; color: #181c32 !important; transition: all .2s ease;
        }
        .quick-action-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(26, 39, 89, 0.10); }
        .filter-box { background: #f8f9fc; border: 1px solid #eceff5; border-radius: 12px; padding: 12px; }
        .table-wrap { max-height: 380px; overflow-y: auto; }

        .dashboard-hub-shell { min-height: calc(100vh - 140px); }
        .dashboard-hub-tabs {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #eef1f7;
            padding: 6px 8px;
            box-shadow: 0 2px 12px rgba(62, 57, 107, 0.05);
            gap: 4px;
            flex-wrap: nowrap;
            overflow-x: auto;
            scrollbar-width: thin;
        }
        .dashboard-hub-tabs .nav-link {
            white-space: nowrap;
            border-radius: 8px;
            color: #5e6278;
            font-weight: 600;
            font-size: 13px;
            padding: 8px 14px;
            border: 0;
            margin: 0;
        }
        .dashboard-hub-tabs .nav-link.active {
            background: var(--bs-primary-light);
            color: var(--bs-primary);
        }
        .dashboard-hub-tabs .nav-link:not(.active):hover {
            background: #f8f9fc;
            color: #181c32;
        }
        .dashboard-hub-panel,
        #dashboard-hub-embed-wrap {
            overflow: visible;
        }
        #dashboard-hub-iframe {
            width: 100%;
            height: 0;
            min-height: 0;
            border: 0;
            border-radius: 14px;
            background: #f5f8fa;
            display: none;
            overflow: hidden;
            vertical-align: top;
        }
        #dashboard-hub-iframe.is-visible { display: block; }
        .dashboard-hub-loading {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-3 dashboard-hub-shell">
        <nav class="nav nav-pills dashboard-hub-tabs mb-4" id="dashboardHubTabs" role="tablist">
            @foreach ($dashboardTabs as $tab)
                @php
                    $labelKey = $tab['label'];
                    $tabLabel = __($labelKey);
                    if ($tabLabel === $labelKey) {
                        $tabLabel = $tab['id'];
                    }
                @endphp
                <button type="button"
                    class="nav-link {{ $activeDashboardTab === $tab['id'] ? 'active' : '' }}"
                    data-dashboard-tab="{{ $tab['id'] }}"
                    data-tab-type="{{ $tab['type'] }}"
                    data-embed-url="{{ $tab['embed_url'] ?? '' }}"
                    role="tab"
                    aria-selected="{{ $activeDashboardTab === $tab['id'] ? 'true' : 'false' }}">
                    <i class="{{ $tab['icon'] }} me-1 opacity-75"></i>{{ $tabLabel }}
                </button>
            @endforeach
        </nav>

        <div class="dashboard-hub-panel">
            <div id="dashboard-hub-overview-wrap" class="{{ $activeDashboardTab === 'overview' ? '' : 'd-none' }}">
                @include('employee::dashboard.overview')
            </div>
            <div id="dashboard-hub-embed-wrap" class="{{ $activeDashboardTab === 'overview' ? 'd-none' : '' }}">
                <div id="dashboard-hub-loading" class="dashboard-hub-loading {{ $activeDashboardTab === 'overview' ? 'd-none' : '' }}">
                    <span class="spinner-border text-primary" role="status"></span>
                </div>
                <iframe id="dashboard-hub-iframe"
                    title="@lang('employee::main.dashboard_hub_iframe_title')"
                    scrolling="no"
                    class="{{ $activeDashboardTab === 'overview' ? '' : 'is-visible' }}"></iframe>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script>
        window.dashboardHubConfig = {
            activeTab: @json($activeDashboardTab),
            tabs: @json(collect($dashboardTabs)->mapWithKeys(fn ($t) => [$t['id'] => [
                'type' => $t['type'],
                'embed_url' => $t['embed_url'] ?? null,
            ]]))
        };
    </script>
    <script src="{{ asset('js/dashboard-hub.js') }}?v=5"></script>
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
                colors: ['#3699FF', '#F1416C'],
                plotOptions: { bar: { horizontal: false, columnWidth: '48%', borderRadius: 6 } },
                dataLabels: { enabled: false },
                stroke: { show: false },
                xaxis: { categories: monthLabels, labels: { style: { fontSize: '12px' } } },
                yaxis: {
                    title: { text: lang === 'ar' ? 'المبلغ (ريال)' : 'Amount (SAR)', offsetX: lang === 'ar' ? -30 : 0 },
                    labels: { formatter: function (val) { return fmtn(val); } }
                },
                tooltip: { y: { formatter: function (val) { return fmtn(val) + ' {{ app()->getLocale() === "ar" ? "ريال" : "SAR" }}'; } } },
                legend: { position: 'top', horizontalAlign: 'right', fontSize: '13px' }
            }).render();
        })();
    </script>
@endsection
