@php
    $filters = [
        [
            'label' => __('employee::fields.employee_status'),
            'name' => 'employee_status',
            'options' => [
                ['id' => '1', 'name' => __('employee::fields.active')],
                ['id' => '0', 'name' => __('employee::fields.inActive')],
            ],
        ],
    ];
@endphp
<div class="mb-10">
    <x-form.input :label="__('employee::fields.date')" name="date" />
</div>
@foreach ($filters as $filter)
    <div class="mb-10">
        <label class="form-label fs-6 fw-semibold">{{ $filter['label'] }}</label>
        <x-form.select :options="$filter['options']" name="{{ $filter['name'] }}" />
    </div>
@endforeach
