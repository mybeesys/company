@props(['establishments', 'roles'])
@php
    $filters = [
        [
            'name' => 'establishment_filter',
            'selected' => 'all',
            'options' => array_merge([['id' => 'all', 'name' => __('employee::general.all_establishments')]], $establishments->toArray()),
        ],
        [
            'name' => 'role_filter',
            'selected' => 'all',
            'options' => array_merge([['id' => 'all', 'name' => __('employee::general.all_roles')]], $roles->toArray()),
        ],
        [
            'name' => 'employee_filter',
            'selected' => 'all',
            'options' => [
                ['id' => 'all', 'name' => __('employee::general.all_employees')],
                ['id' => '1', 'name' => __('employee::fields.active')],
                ['id' => '0', 'name' => __('employee::fields.inActive')],
            ],
        ],
    ];
@endphp
@foreach ($filters as $filter)
    <div class="fv-row flex-md-root min-w-250px min-w-md-150px w-100">
        <x-form.select value="{{ $filter['selected'] }}" data_allow_clear="false" :options="$filter['options']" name="{{ $filter['name'] }}" />
    </div>
@endforeach
