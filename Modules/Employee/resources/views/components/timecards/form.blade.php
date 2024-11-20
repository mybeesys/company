@props(['employees', 'timecard' => null, 'formId' => null, 'roles'])
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <x-form.form-card :title="__('employee::general.timecard_details')">
        <div class="d-flex flex-wrap">
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.select name="employee_id" :options="$employees" :data_allow_clear=false :label="__('employee::fields.employee')" required
                    optionName="{{ session()->get('locale') == 'ar' ? 'name' : 'name_en' }}" :errors="$errors"
                    placeholder="{{ __('employee::general.select_option') }}" value="{{ $timecard?->employee_id }}" />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.select name="role_id" :options="$roles" :data_allow_clear=false :label="__('employee::fields.role')" required
                    :errors="$errors" placeholder="{{ __('employee::general.select_option') }}"
                    value="{{ $timecard?->role_id }}" />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.time-picker name="clock_in_time" :label="__('employee::fields.inTime')" value="{{ $timecard?->clock_in_time }}"
                    required />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.time-picker name="clock_out_time" :label="__('employee::fields.outTime')" value="{{ $timecard?->clock_out_time }}"
                    required />
            </x-form.input-div>
        </div>
        <div class="d-flex flex-wrap">
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.date-picker name="date" :errors=$errors required value="{{ $timecard?->date }}"
                    :label="__('employee::fields.date')" />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input :errors="$errors" type="number" placeholder="0" readonly required :label="__('employee::fields.total_hours')"
                    name="hours_worked" value="{{ $timecard?->hours_worked }}" />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input :errors="$errors" type="number" placeholder="0" readonly attribute="step=.01" :label="__('employee::fields.overtime_hours')"
                    name="overtime_hours" value="{{ $timecard?->overtime_hours }}" />
            </x-form.input-div>
        </div>
    </x-form.form-card>
    <x-form.form-buttons cancelUrl="{{ url('/schedule/timecard') }}" :id=$formId />
</div>
