@props(['employee' => null])
<div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
    <x-employee::card :title="__('employee::fields.employee_image')" bodyClass="text-center">
        <x-employee::form.image-input :errors=$errors name="image" image="{{ $employee?->image }}" />
        <div class="text-muted fs-7">@lang('employee::general.image_hint')</div>
    </x-employee::card>
    <x-employee::card :title="__('employee::fields.status')">
        <x-slot:header>
            <span class="ms-1" data-bs-toggle="tooltip" title="@lang('employee::general.employee_status_hint')">
                <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
            </span>
        </x-slot>
        <div class="form-check form-switch form-check-success form-check-solid">
            <input type="hidden" name="isActive" value="0">
            <x-employee::form.input :errors=$errors class="form-check-input" value="1" type="checkbox"
                labelClass="form-check-label" name="isActive" label="تفعيل/تعطيل"
                checked="{{ $employee?->isActive }}" />
        </div>
    </x-employee::card>
    <x-employee::card :title="__('employee::fields.role')">
        @php
            $options = [['value' => 'with_deleted_records', 'name' => __('employee::general.with_deleted_records')]];
        @endphp
        <x-employee::form.select name="deleted_records" :options=$options :errors=$errors />
    </x-employee::card>

</div>
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div>
        <div class="d-flex flex-column gap-7 gap-lg-10">
            <x-employee::card :title="__('employee::general.employee_details')">

                <div class="d-flex flex-wrap ">
                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.input required :errors=$errors
                            placeholder="{{ __('employee::fields.first_name') }} ({{ __('employee::fields.required') }})"
                            value="{{ $employee?->firstName }}" name="firstName" :label="__('employee::fields.first_name')" />
                    </x-employee::form.input-div>
                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.input required :errors=$errors
                            placeholder="{{ __('employee::fields.last_name') }} ({{ __('employee::fields.required') }})"
                            value="{{ $employee?->lastName }}" name="lastName" :label="__('employee::fields.last_name')" />
                    </x-employee::form.input-div>
                </div>

                <div class="d-flex flex-wrap">
                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.input type="email" required :errors=$errors
                            placeholder="{{ __('employee::fields.email') }} ({{ __('employee::fields.required') }})"
                            value="{{ $employee?->email }}" name="email" :label="__('employee::fields.email')" />
                    </x-employee::form.input-div>

                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.input required :errors=$errors
                            placeholder="{{ __('employee::fields.phone_number') }} ({{ __('employee::fields.required') }})"
                            value="{{ $employee?->phoneNumber }}" name="phoneNumber" :label="__('employee::fields.phone_number')" />
                    </x-employee::form.input-div>
                </div>

                <div class="d-flex flex-wrap">
                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.date-picker name="employmentStartDate" :errors=$errors required
                            value="{{ $employee?->employmentStartDate }}" :label="__('employee::fields.employment_start_date')" />
                    </x-employee::form.input-div>

                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.input :errors=$errors type="number" placeholder="00.0" name="wage"
                            :label="__('employee::fields.wage')" />
                    </x-employee::form.input-div>
                </div>
                <div class="gap-10">
                    <x-employee::form.input-div class="px-2">
                        <label for="PIN" class="form-label">@lang('employee::fields.employee_access_pin')</label>
                        <div class="input-group">
                            <x-employee::form.input :errors=$errors type="number" placeholder="PIN" name="PIN"
                                value="{{ $employee?->PIN }}" required>
                                <a href="#" id="generate_pin" class="btn btn-light-primary">@lang('employee::general.generate_pin')</a>
                            </x-employee::form.input>
                        </div>
                    </x-employee::form.input-div>
                </div>
            </x-employee::card>
        </div>
    </div>
    <div class="d-flex justify-content-end">
        <a href="{{ url('/employees') }}" id="kt_ecommerce_add_product_cancel"
            class="btn btn-light me-5">@lang('employee::general.cancel')</a>
        <button type="submit" id="add_employee_form_button" class="btn btn-primary">
            <span class="indicator-label">@lang('employee::general.save')</span>
            <span class="indicator-progress">@lang('employee::general.please_wait')
                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
        </button>
    </div>
</div>
