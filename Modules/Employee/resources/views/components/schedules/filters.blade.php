@php
    $filters = [
        [
            'label' => __('employee::fields.establishment'),
            'name' => 'establishment',
            'options' => [
                ['id' => 'with_deleted_records', 'name' => __('employee::general.with_deleted_records')],
                ['id' => 'only_deleted_records', 'name' => __('employee::general.only_deleted_records')],
            ],
        ],
        [
            'label' => __('employee::fields.role'),
            'name' => 'role',
            'options' => [
                ['id' => '1', 'name' => __('employee::fields.active')],
                ['id' => '0', 'name' => __('employee::fields.inActive')],
            ],
        ],
        [
            'label' => __('employee::fields.employee'),
            'name' => 'employee',
            'options' => [
                ['id' => '1', 'name' => __('employee::fields.active')],
                ['id' => '0', 'name' => __('employee::fields.inActive')],
            ],
        ],
    ];
@endphp
@foreach ($filters as $filter)
    <div class="fv-row flex-md-root min-w-250px min-w-md-150px w-100">
        <x-form.select :options="$filter['options']" name="{{ $filter['name'] }}" />
    </div>
@endforeach
