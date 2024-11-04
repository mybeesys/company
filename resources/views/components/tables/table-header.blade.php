@props(['model', 'url' => null, 'filters' => null, 'export' => null, 'module', 'search' => true, 'export' => true, 'addButton' => true])

@if ($search)
    <div class="card-title">
        <div class="d-flex align-items-center position-relative my-1">
            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
            <input type="text" data-kt-filter="search" class="form-control form-control-solid w-250px ps-12"
                placeholder='@lang("{$module}::general.{$model}_search")' />
        </div>
        <div id="kt_employee_table_export" class="d-none"></div>
    </div>
@endif
<div class="card-toolbar flex-row-fluid justify-content-end gap-5">
    <div class="card-toolbar flex-row-fluid justify-content-end gap-5" data-kt-{{ $model }}-table-toolbar="base">

        {{ $filters }}
        @if ($export)
            <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                data-kt-menu-placement="bottom-end">
                <i class="ki-duotone ki-exit-down fs-2"><span class="path1"></span><span class="path2"></span></i>
                @lang('general.report_export')
            </button>

            {{ $export }}

            <div id="kt_{{ $model }}_table_buttons" class="d-none"></div>
        @endif

    </div>
    <div class="d-flex justify-content-end align-items-center d-none" data-kt-{{ $model }}-table-toolbar="selected">
        <div class="fw-bold me-5">
        <span class="me-2" data-kt-{{ $model }}-table-select="selected_count"></span>Selected</div>
        <button type="button" class="btn btn-danger" data-kt-{{ $model }}-table-select="delete_selected">Delete Selected</button>
    </div>
    {{ $slot }}
    @if ($slot->isEmpty() && $addButton)
        <a href="{{ url("/{$url}") }}" class="btn btn-primary">@lang("{$module}::general.add_{$model}")
        </a>
    @endif
</div>
