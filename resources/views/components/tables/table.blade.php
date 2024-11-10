@props(['columns', 'model', 'module', 'selectColumn' => false, 'actionColumn' => true, 'idColumn' => true])
<table class="table align-middle table-striped table-row-bordered fs-6 gy-5" id="kt_{{ $model }}_table">
    <thead>
        <tr class="text-start text-gray-600 fw-bold fs-7 text-uppercase gs-0 w-100" id="{{ $model }}_headerRow">
            @if ($selectColumn)
                <th class="min-w-10px pe-5">
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="{{ $model }}_select form-check-input" type="checkbox" data-kt-check="true"
                            data-kt-check-target="#kt_{{ $model }}_table .form-check-input" value="1" />
                    </div>
                </th>
            @endif
            @if ($idColumn)
                <th class="min-w-75px text-start align-middle"><span class="px-1">@lang("{$module}::fields.id")</span></th>
            @endif
            @foreach ($columns as $column)
                <th class="{{ $column['class'] }}"><span class="px-1">@lang("{$module}::fields.{$column['name']}")</span></th>
            @endforeach
            @if ($actionColumn)
                <th class="text-center align-middle">@lang("{$module}::fields.actions")</th>
            @endif
        </tr>
    </thead>
    <tbody class="fw-semibold text-gray-600" id="{{ $model }}_tableBody">
    </tbody>
</table>
