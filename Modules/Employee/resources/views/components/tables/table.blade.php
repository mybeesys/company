@props(['columns', 'model'])
<table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_{{ $model }}_table">
    <thead>
        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0 w-100">
            <th class="min-w-75px text-start">@lang('employee::fields.number')</th>
            @foreach ($columns as $column)
                <th class="{{ $column['class'] }}">@lang("employee::fields.{$column['name']}")</th>
            @endforeach
            <th class="text-center">@lang('employee::fields.actions')</th>
        </tr>
    </thead>
    <tbody class="fw-semibold text-gray-600">
    </tbody>
</table>
