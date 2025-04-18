@props(['employees', 'timecard' => null, 'formId' => null, 'establishments'])
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <x-form.form-card :title="__('employee::general.timecard_details')">
        <div class="d-flex flex-wrap">
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.select name="employee_id" :options="$employees" :data_allow_clear=false :label="__('employee::fields.employee')" required
                    :optionName="get_name_by_lang()" :errors="$errors"
                    placeholder="{{ __('employee::general.select_option') }}" value="{{ $timecard?->employee_id }}" />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.select name="establishment_id" :options="$establishments" :data_allow_clear=false :label="__('employee::fields.establishment')" required
                    :errors="$errors" placeholder="{{ __('employee::general.select_option') }}"
                    value="{{ $timecard?->establishment_id }}" />
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
