@extends('employee::layouts.master')

@php
    $employeeName = $employee->translated_name ?? $employee->name ?? '';
    $initial = mb_strtoupper(mb_substr((string) ($employee->name ?? '?'), 0, 1));
@endphp

@section('title', __('employee::activity.timeline_for', ['name' => $employeeName]))
@section('css')
@parent
<style>
    .ua-profile-card {
        border-left: 4px solid #0d9488;
        padding-left: 1.25rem;
    }
    .ua-mini-stat {
        border-radius: 0.65rem;
        background: var(--bs-gray-100);
        padding: 0.75rem 1rem;
        text-align: center;
    }
    .ua-mini-stat .n { font-weight: 700; font-size: 1.25rem; }
    .ua-mini-stat .l { font-size: 0.75rem; color: var(--bs-gray-600); }
    #ua_activity_table tbody td { vertical-align: middle; }
    .ua-detail-section { margin-bottom: 1.25rem; }
    .ua-detail-section h6 { font-weight: 700; margin-bottom: 0.65rem; }
    .ua-detail-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.45rem 0;
        border-bottom: 1px dashed var(--bs-gray-200);
        font-size: 0.9rem;
    }
    .ua-detail-row .k { color: var(--bs-gray-600); min-width: 38%; }
    .ua-detail-row .v { color: var(--bs-gray-900); text-align: end; word-break: break-word; }
</style>
@endsection

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-xxl">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-6 gap-4">
            <div class="ua-profile-card d-flex align-items-center gap-4">
                <span class="symbol symbol-50px symbol-circle bg-light-primary text-primary fs-3 fw-bold">{{ $initial }}</span>
                <div>
                    <a href="{{ route('activity.index') }}" class="text-muted fs-7 d-block mb-1">← @lang('employee::activity.back_to_users')</a>
                    <h1 class="fw-bold mb-1">@lang('employee::activity.timeline_for', ['name' => $employeeName])</h1>
                    <p class="text-muted mb-0">{{ $employee->email }}</p>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-3">
                <div class="ua-mini-stat">
                    <div class="n">{{ number_format($summary['total']) }}</div>
                    <div class="l">@lang('employee::activity.summary_total')</div>
                </div>
                <div class="ua-mini-stat">
                    <div class="n">{{ number_format($summary['today']) }}</div>
                    <div class="l">@lang('employee::activity.summary_today')</div>
                </div>
                <div class="ua-mini-stat">
                    <div class="n text-danger">{{ number_format($summary['deletions']) }}</div>
                    <div class="l">@lang('employee::activity.summary_deletions')</div>
                </div>
            </div>
        </div>

        <div class="card card-flush mb-5">
            <div class="card-body py-4">
                <form id="ua_filter_form" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">@lang('employee::activity.event_type')</label>
                        <select name="event" id="ua_event" class="form-select">
                            @foreach($eventTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">@lang('employee::activity.date_from')</label>
                        <input type="date" name="date_from" id="ua_date_from" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">@lang('employee::activity.date_to')</label>
                        <input type="date" name="date_to" id="ua_date_to" class="form-control">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="button" class="btn btn-primary flex-grow-1" id="ua_apply_filter">@lang('employee::activity.apply_filter')</button>
                        <button type="button" class="btn btn-light" id="ua_clear_filter">@lang('employee::activity.clear_filter')</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-flush">
            <div class="card-body pt-0">
                <table class="table align-middle table-row-bordered w-100" id="ua_activity_table">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase">
                            <th>@lang('employee::activity.when')</th>
                            <th>@lang('employee::activity.event_type')</th>
                            <th>@lang('employee::activity.what_happened')</th>
                            <th>@lang('employee::activity.area')</th>
                            <th class="text-center">@lang('employee::activity.actions')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ua_detail_modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="ua_detail_title">@lang('employee::activity.entry_details')</h5>
                    <p class="text-muted mb-0 fs-7" id="ua_detail_subtitle"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-4" id="ua_detail_body"></div>
        </div>
    </div>
</div>
@endsection

@section('script')
@parent
<script src="{{ url('js/table.js') }}"></script>
<script>
let ua_activity_table;

function uaEsc(text) {
    return $('<div>').text(text == null ? '' : text).html();
}

function uaRenderDetail(data) {
    $('#ua_detail_title').text(data.headline || '');
    $('#ua_detail_subtitle').text((data.when || '') + (data.when_relative ? ' · ' + data.when_relative : ''));
    const $body = $('#ua_detail_body').empty();
    (data.sections || []).forEach(function (section) {
        const $sec = $('<div class="ua-detail-section"></div>');
        $sec.append('<h6>' + uaEsc(section.title) + '</h6>');
        (section.rows || []).forEach(function (row) {
            $sec.append(
                '<div class="ua-detail-row"><span class="k">' + uaEsc(row.label) + '</span><span class="v">' + uaEsc(row.value) + '</span></div>'
            );
        });
        $body.append($sec);
    });
}

$(function () {
    ua_activity_table = $('#ua_activity_table').DataTable({
        processing: true,
        serverSide: true,
        order: [[0, 'desc']],
        ajax: {
            url: '{{ route('activity.data', ['employee' => $employee->id]) }}',
            data: function (d) {
                d.event = $('#ua_event').val();
                d.date_from = $('#ua_date_from').val();
                d.date_to = $('#ua_date_to').val();
            }
        },
        columns: [
            { data: 'when_cell', name: 'created_at' },
            { data: 'event_badge', name: 'event', orderable: false },
            { data: 'headline_cell', name: 'description' },
            { data: 'area_cell', name: 'log_name', orderable: false },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
    });

    $('#ua_apply_filter').on('click', function () {
        ua_activity_table.ajax.reload();
    });

    $('#ua_clear_filter').on('click', function () {
        $('#ua_event').val('');
        $('#ua_date_from').val('');
        $('#ua_date_to').val('');
        ua_activity_table.ajax.reload();
    });

    $(document).on('click', '.js-activity-detail', function () {
        $.getJSON($(this).data('url'), function (data) {
            uaRenderDetail(data);
            new bootstrap.Modal(document.getElementById('ua_detail_modal')).show();
        });
    });
});
</script>
@endsection
