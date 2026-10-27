@extends('employee::layouts.master')

@section('title', __('employee::activity.timeline_for', ['name' => $employee->translated_name ?? $employee->name ?? '']))
@section('css')
@parent
<style>
    .ua-timeline-header {
        border-left: 4px solid #0d9488;
        padding-left: 1rem;
    }
    #ua_activity_table tbody td { vertical-align: middle; font-size: 0.9rem; }
    #ua_detail_modal pre {
        max-height: 420px;
        overflow: auto;
        font-size: 0.78rem;
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
    }
</style>
@endsection

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-xxl">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-6 gap-3">
            <div class="ua-timeline-header">
                <a href="{{ route('activity.index') }}" class="text-muted fs-7 d-block mb-1">← @lang('employee::activity.back_to_users')</a>
                <h1 class="fw-bold mb-0">@lang('employee::activity.timeline_for', ['name' => $employee->translated_name ?? $employee->name ?? ''])</h1>
                <p class="text-muted mb-0">{{ $employee->email }} · {{ $employee->user_name }}</p>
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
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary w-100" id="ua_apply_filter">@lang('employee::activity.apply_filter')</button>
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
                            <th>@lang('employee::activity.description')</th>
                            <th>@lang('employee::activity.context')</th>
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
            <div class="modal-header">
                <h5 class="modal-title">@lang('employee::activity.entry_details')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-2" id="ua_detail_summary"></p>
                <pre id="ua_detail_json"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
@parent
<script src="{{ url('js/table.js') }}"></script>
<script>
let ua_activity_table;

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
            { data: 'created_at_fmt', name: 'created_at' },
            { data: 'event_badge', name: 'event', orderable: false },
            { data: 'description_fmt', name: 'description' },
            { data: 'context', name: 'route_name', orderable: false },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
    });

    $('#ua_apply_filter').on('click', function () {
        ua_activity_table.ajax.reload();
    });

    $(document).on('click', '.js-activity-detail', function () {
        const url = $(this).data('url');
        $.getJSON(url, function (data) {
            $('#ua_detail_summary').text(data.description || '');
            $('#ua_detail_json').text(JSON.stringify(data, null, 2));
            new bootstrap.Modal(document.getElementById('ua_detail_modal')).show();
        });
    });
});
</script>
@endsection
