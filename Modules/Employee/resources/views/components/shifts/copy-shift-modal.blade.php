@props(['roles'])
@php
    $endStatusOptions = [
        ['id' => 'clockout', 'name' => __('employee::fields.clockout')],
        ['id' => 'break', 'name' => __('employee::fields.break')],
    ];
@endphp
<x-general.modal module="employee" class="mw-600px" header_class="px-8 py-5" body_class="pt-5" id="shift_copy">
    <x-slot:header>
        <h2 class="copy-shifts-modal-title fs-5 mb-0 text-danger"></h2>
    </x-slot:header>
    <x-form.input-div class="mb-8 w-100 d-flex align-items-center" :row=false>
        <label class="w-100 fs-5">@lang('employee::general.select_week_to_copy_to')</label>
        <x-form.input class="form-control form-control-solid" name="copyShiftDatePicker" />
    </x-form.input-div>
</x-general.modal>
