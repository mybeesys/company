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
        .dashboard-hub-panel {
            overflow: visible;
        }
    </style>
    @include('employee::dashboard.partials.tabs-styles')
@endsection

@section('content')
    <div class="container-fluid py-3 dashboard-hub-shell">
        @include('employee::dashboard.partials.tabs-nav', ['activeDashboardTab' => $activeDashboardTab])

        <div class="dashboard-hub-panel">
            @include('employee::dashboard.overview')
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
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
