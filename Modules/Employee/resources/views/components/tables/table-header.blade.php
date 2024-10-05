@props(['model', 'filters' => null, 'export' => null])
<div class="card-title">
    <div class="d-flex align-items-center position-relative my-1">
        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
        <input type="text" data-kt-filter="search" class="form-control form-control-solid w-250px ps-12"
            placeholder='@lang("employee::general.{$model}_search")' />
    </div>
    <div id="kt_employee_table_export" class="d-none"></div>
</div>
<div class="card-toolbar flex-row-fluid justify-content-end gap-5">
    <div class="card-toolbar flex-row-fluid justify-content-end gap-5">

        {{ $filters }}

        <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
            data-kt-menu-placement="bottom-end">
            <i class="ki-duotone ki-exit-down fs-2"><span class="path1"></span><span class="path2"></span></i>
            @lang('employee::general.report_export')
        </button>

        {{ $export }}

        <div id="kt_{{ $model }}_table_buttons" class="d-none"></div>

    </div>

    <a href={{ url("/{$model}/create") }} class="btn btn-primary">@lang("employee::general.add_{$model}")
    </a>
</div>
