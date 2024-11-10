@props(['columns', 'model', 'module', 'selectColumn' => false])
<table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_{{ $model }}_table">
    <thead>
        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0 w-100">
            @if ($selectColumn)
                <th class="min-w-10px pe-5">
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="{{ $model }}_select form-check-input" type="checkbox" data-kt-check="true"
                            data-kt-check-target="#kt_{{ $model }}_table .form-check-input" value="1" />
                    </div>
                </th>
            @endif
            <th class="min-w-75px text-start">@lang("{$module}::fields.number")</th>
            @foreach ($columns as $column)
                <th class="{{ $column['class'] }}">@lang("{$module}::fields.{$column['name']}")</th>
            @endforeach
            <th class="text-center">@lang("{$module}::fields.actions")</th>
        </tr>
    </thead>
    <tbody class="fw-semibold text-gray-600">
    </tbody>
</table>
