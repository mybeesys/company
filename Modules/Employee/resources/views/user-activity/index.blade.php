@extends('employee::layouts.master')

@section('title', __('employee::activity.page_title'))
@section('css')
@parent
<style>
    .ua-hero {
        background: var(--bs-primary, #e9b71f);
        border-radius: 0.85rem;
        color: var(--bs-primary-inverse, #1e2129);
    }
    .ua-hero .ua-hero-text {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        line-height: 1.6;
        color: inherit;
    }
    .ua-stat-card {
        border: 1px solid var(--bs-gray-200);
        border-radius: 0.75rem;
        padding: 1.1rem 1.25rem;
        height: 100%;
        background: #fff;
    }
    .ua-stat-card .label { color: var(--bs-gray-600); font-size: 0.8rem; }
    .ua-stat-card .value { font-size: 1.35rem; font-weight: 700; color: var(--bs-gray-900); }
    #ua_users_table tbody td { vertical-align: middle; }
</style>
@endsection

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-xxl">
        <div class="ua-hero p-5 mb-6">
            <p class="ua-hero-text">@lang('employee::activity.page_subtitle')</p>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-3">
                <div class="ua-stat-card">
                    <div class="label">@lang('employee::activity.stat_users')</div>
                    <div class="value">{{ number_format($stats['dashboard_users']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="ua-stat-card">
                    <div class="label">@lang('employee::activity.stat_today')</div>
                    <div class="value">{{ number_format($stats['events_today']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="ua-stat-card">
                    <div class="label">@lang('employee::activity.stat_week')</div>
                    <div class="value">{{ number_format($stats['events_week']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="ua-stat-card">
                    <div class="label">@lang('employee::activity.stat_last_log')</div>
                    <div class="value fs-6">
                        @if($stats['last_log_at'])
                            {{ \Carbon\Carbon::parse($stats['last_log_at'])->locale(app()->getLocale())->diffForHumans() }}
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-flush">
            <div class="card-header align-items-center py-5">
                <h3 class="card-title fw-bold">@lang('employee::activity.users_overview')</h3>
            </div>
            <div class="card-body pt-0">
                <table class="table align-middle table-row-bordered fs-6 w-100" id="ua_users_table">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase">
                            <th>@lang('employee::fields.name')</th>
                            <th>@lang('employee::fields.email')</th>
                            <th>@lang('employee::activity.last_activity')</th>
                            <th>@lang('employee::activity.events_count')</th>
                            <th class="text-center">@lang('employee::activity.actions')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
@parent
<script src="{{ url('js/table.js') }}"></script>
<script>
$(function () {
    $('#ua_users_table').DataTable({
        processing: true,
        serverSide: true,
        order: [[2, 'desc']],
        ajax: '{{ route('activity.users.data') }}',
        columns: [
            { data: 'user_cell', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'last_activity_at_fmt', name: 'last_activity_at', searchable: false },
            { data: 'activity_count_fmt', name: 'activity_count', searchable: false },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
    });
});
</script>
@endsection
