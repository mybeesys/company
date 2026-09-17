@extends('layouts.app')
@section('title', __('menuItemLang.dashboard'))

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css" rel="stylesheet" type="text/css">
    @include('employee::dashboard.partials.tabs-styles')
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
        ></div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    {{-- Matches vite.config.js buildDirectory (not the default public/build manifest). --}}
    @vite(['resources/components/executive-dashboard/main.jsx'], 'tenancy/assets/build')
@endsection
