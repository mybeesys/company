@props(['establishments', 'employees'])
<x-general.modal module="employee" id='add_payroll_modal' title='create_payroll' class='mw-600px'>
    <x-form.input-div class="mb-10 w-100">
        <x-form.select name="establishment" :label="__('employee::fields.establishment')" :options=$establishments :errors="$errors"
            data_allow_clear="false" required placeholder="{{ __('employee::fields.establishment') }}"
            attribute="multiple" no_default>
            <button type="button" id="est-select-all-btn"
                class="btn btn-primary px-4 py-1 fs-7 ms-2 mb-1">{{ __('employee::general.select_all') }}</button>
            <button type="button" id="est-deselect-all-btn"
                class="btn btn-secondary px-4 py-1 fs-7 mb-1">{{ __('employee::general.deselect_all') }}</button>
        </x-form.select>
    </x-form.input-div>
    <x-form.input-div class="mb-10 w-100">
        <x-form.select name="employee" optionName="translatedName" :label="__('employee::fields.employee')" :options=$employees
            :errors="$errors" data_allow_clear="false" placeholder="{{ __('employee::fields.employee') }}" required
            no_default attribute="multiple">
            <button type="button" id="emp-select-all-btn"
                class="btn btn-primary px-4 py-1 fs-7 ms-2 mb-1">{{ __('employee::general.select_all') }}</button>
            <button type="button" id="emp-deselect-all-btn"
                class="btn btn-secondary px-4 py-1 fs-7 mb-1">{{ __('employee::general.deselect_all') }}</button>
        </x-form.select>
    </x-form.input-div>
    <x-form.input-div class="mb-10 w-100">
        <x-form.input :label="__('employee::fields.year_month')" :placeholder="__('employee::fields.date')" name="date" required />
    </x-form.input-div>
</x-general.modal>
