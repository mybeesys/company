@extends('layouts.app')
@section('title', app()->getLocale() === 'ar' ? 'لوحة قرار تنفيذية' : 'Executive Dashboard')

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css" rel="stylesheet" type="text/css">
    @include('employee::dashboard.partials.tabs-styles')
@endsection

@section('content')
    @php
        $activeDashboardTab = 'overview';
        $hubTabsShowLabel = app()->getLocale() === 'ar' ? 'إظهار لوحات التحكم' : 'Show module dashboards';
        $hubTabsHideLabel = app()->getLocale() === 'ar' ? 'إخفاء لوحات التحكم' : 'Hide module dashboards';
    @endphp
    <div class="container-fluid py-3">
        <div class="ed-hub-tabs-toolbar">
            <button
                type="button"
                class="ed-hub-tabs-toggle"
                id="edHubTabsToggle"
                aria-expanded="false"
                aria-controls="edHubTabsPanel"
                data-show-label="{{ $hubTabsShowLabel }}"
                data-hide-label="{{ $hubTabsHideLabel }}"
            >{{ $hubTabsShowLabel }}</button>
        </div>
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
        ></div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    {{-- Matches vite.config.js buildDirectory (not the default public/build manifest). --}}
    @vite(['resources/components/executive-dashboard/main.jsx'], 'tenancy/assets/build')
    <script>
        (function () {
            var key = 'mybee.executiveDashboard.hubTabs';
            var panel = document.getElementById('edHubTabsPanel');
            var btn = document.getElementById('edHubTabsToggle');
            if (!panel || !btn) return;
            var apply = function (show) {
                panel.hidden = !show;
                btn.setAttribute('aria-expanded', show ? 'true' : 'false');
                btn.textContent = show ? btn.getAttribute('data-hide-label') : btn.getAttribute('data-show-label');
                try { sessionStorage.setItem(key, show ? '1' : '0'); } catch (e) {}
            };
            var stored = false;
            try { stored = sessionStorage.getItem(key) === '1'; } catch (e) {}
            apply(stored);
            btn.addEventListener('click', function () { apply(panel.hidden); });
        })();
    </script>
@endsection
