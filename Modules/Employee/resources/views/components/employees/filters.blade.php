@props(['options' => null])
<div class="mb-10">
    <label class="form-label fs-6 fw-semibold">@lang('employee::general.deleted_records')</label>
    @php
        $options = [
            ['id' => 'with_deleted_records', 'name' => __('employee::general.with_deleted_records')],
            ['id' => 'only_deleted_records', 'name' => __('employee::general.only_deleted_records')],
        ];
    @endphp
    <x-form.select :options=$options name="deleted_records" />
</div>

<div class="mb-10">
    <label class="form-label fs-6 fw-semibold">@lang('employee::fields.status')</label>
    @php
        $options = [
            ['id' => '1', 'name' => __('employee::fields.active')],
            ['id' => '0', 'name' => __('employee::fields.inActive')],
        ];
    @endphp
    <x-form.select :options=$options name="status" />
</div>