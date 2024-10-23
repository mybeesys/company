@props(['employees', 'timecard' => null, 'formId' => null])
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <x-form.form-card :title="__('employee::general.timecard_details')">
        <div class="d-flex flex-wrap">
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.select name="employee_id" :options="$employees" :data_allow_clear=false :label="__('employee::fields.employee')" required
                    optionName="{{ session()->get('locale') == 'ar' ? 'name' : 'name_en' }}" :errors="$errors"
                    placeholder="{{ __('employee::general.select_option') }}" value="{{ $timecard?->employee_id }}" />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.time-picker name="clockInTime" :label="__('employee::fields.inTime')" value="{{ $timecard?->clockInTime }}" required />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.time-picker name="clockOutTime" :label="__('employee::fields.outTime')" value="{{ $timecard?->clockOutTime }}"
                    required />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.date-picker name="date" :errors=$errors required value="{{ $timecard?->date }}"
                    :label="__('employee::fields.date')" />
            </x-form.input-div>
        </div>
        <div class="d-flex flex-wrap">
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input :errors="$errors" type="number" placeholder="0" readonly required :label="__('employee::fields.total_hours')"
                    name="hoursWorked" value="{{ $timecard?->hoursWorked }}" />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input :errors="$errors" type="number" placeholder="0" attribute="step=.01" :label="__('employee::fields.overtime_hours')"
                    name="overtimeHours" value="{{ $timecard?->overtimeHours }}" />
            </x-form.input-div>
        </div>
    </x-form.form-card>
    <x-form.form-buttons cancelUrl="{{ url('/timecard') }}" :id=$formId />
</div>
