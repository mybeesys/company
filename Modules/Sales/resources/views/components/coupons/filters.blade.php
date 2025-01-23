@php
    $filters = [
        [
            'label' => __('employee::general.deleted_records'),
            'name' => 'coupon_deleted_records',
            'options' => [
                ['id' => 'with_deleted_records', 'name' => __('employee::general.with_deleted_records')],
                ['id' => 'only_deleted_records', 'name' => __('employee::general.only_deleted_records')],
            ],
        ],
        [
            'label' => __('employee::fields.status'),
            'name' => 'coupon_status',
            'options' => [
                ['id' => '1', 'name' => __('employee::fields.active')],
                ['id' => '0', 'name' => __('employee::fields.inActive')],
            ],
        ],
    ];
@endphp
@foreach ($filters as $filter)
    <div class="mb-10">
        <label class="form-label fs-6 fw-semibold">{{ $filter['label'] }}</label>
        <x-form.select :options="$filter['options']" name="{{ $filter['name'] }}" />
    </div>
@endforeach
