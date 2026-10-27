@extends('employee::layouts.master')

@section('title', __('employee::activity.page_title'))
@section('css')
@parent
<style>
    .ua-hero {
        background: linear-gradient(135deg, #1e3a5f 0%, #0d9488 100%);
        border-radius: 0.75rem;
        color: #fff;
    }
    .ua-hero p { color: rgba(255,255,255,.85); }
    #ua_users_table tbody td { vertical-align: middle; }
</style>
@endsection

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-xxl">
        <div class="ua-hero p-6 mb-6">
            <h1 class="text-white fw-bold mb-2">@lang('employee::activity.page_title')</h1>
            <p class="mb-0 fs-6">@lang('employee::activity.page_subtitle')</p>
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
                            <th>@lang('employee::fields.user_name')</th>
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
        order: [[3, 'desc']],
        ajax: '{{ route('activity.users.data') }}',
        columns: [
            { data: 'display_name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'user_name', name: 'user_name' },
            { data: 'last_activity_at_fmt', name: 'last_activity_at', searchable: false },
            { data: 'activity_count_fmt', name: 'activity_count', searchable: false },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
    });
});
</script>
@endsection
