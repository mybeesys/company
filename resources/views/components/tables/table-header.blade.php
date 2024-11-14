@props([
    'model',
    'url' => null,
    'filters' => null,
    'export' => null,
    'module',
    'search' => true,
    'addButton' => true,
    'elements' => null,
])
<div class="container px-0">
    <div class="card-toolbar flex-wrap justify-content-between gap-5" data-kt-{{ $model }}-table-toolbar="base">

        <div class="d-flex flex-wrap flex-md-nowrap gap-5 align-items-center me-auto">
            {{ $elements }}
            @if ($search)
                <div class="min-w-200px w-100">
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" data-kt-filter="search" class="form-control form-control-solid ps-12"
                            placeholder='@lang("{$module}::general.{$model}_search")' />
                    </div>
                </div>
            @endif
        </div>
        {{ $filters }}
        @if ($export)
            <button type="button"
                class="btn btn-light-primary d-flex flex-nowrap text-nowrap justify-content-center fv-row flex-md-root px-5 mw-250px"
                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                <i class="ki-duotone ki-exit-down fs-2"><span class="path1"></span><span class="path2"></span></i>
                @lang('general.report_export')
            </button>

            {{ $export }}

            <div id="kt_{{ $model }}_table_buttons" class="d-none"></div>
        @endif

        @if ($slot->isEmpty() && $addButton)
            <a href="{{ url("/{$url}") }}"
                class="btn btn-primary fv-row flex-md-root min-w-150px mw-250px">@lang("{$module}::general.add_{$model}")
            </a>
        @endif
    </div>
    {{ $slot }}
</div>
