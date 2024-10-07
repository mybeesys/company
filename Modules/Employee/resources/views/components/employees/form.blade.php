@props(['employee' => null, 'roles'])
<div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
    <x-employee::form.form-card :title="__('employee::fields.employee_image')" bodyClass="text-center">
        <x-employee::form.image-input :errors=$errors name="image" image="{{ $employee?->image }}" />
        <div class="text-muted fs-7">@lang('employee::general.image_hint')</div>
    </x-employee::form.form-card>
    <x-employee::form.form-card :title="__('employee::fields.status')">
        <x-slot:header>
            <span class="ms-1" data-bs-toggle="tooltip" title="@lang('employee::general.employee_status_hint')">
                <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
            </span>
        </x-slot>
        <div class="form-check form-switch form-check-success form-check-solid">
            <input type="hidden" name="isActive" value="0">
            <x-employee::form.input :errors=$errors class="form-check-input" value="1" type="checkbox"
                labelClass="form-check-label" name="isActive" label="{{ __('employee::general.deactivate/activate') }}"
                checked="{{ $employee?->isActive }}" />
        </div>
        </x-employee::card>
        <x-employee::form.form-card :title="__('employee::fields.role')">
            <x-employee::form.select name="role" :options=$roles :errors=$errors
                value="{{ $employee?->roles?->first()?->id }}" />
        </x-employee::form.form-card>

</div>
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div>
        <div class="d-flex flex-column gap-7 gap-lg-10">
            <x-employee::form.form-card :title="__('employee::general.employee_details')">

                <div class="d-flex flex-wrap">
                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.input required :errors=$errors
                            placeholder="{{ __('employee::fields.name') }} ({{ __('employee::fields.required') }})"
                            value="{{ $employee?->name }}" name="name" :label="__('employee::fields.name')" />
                    </x-employee::form.input-div>
                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.input required :errors=$errors
                            placeholder="{{ __('employee::fields.name_en') }} ({{ __('employee::fields.required') }})"
                            value="{{ $employee?->name_en }}" name="name_en" :label="__('employee::fields.name_en')" />
                    </x-employee::form.input-div>
                </div>

                <div class="d-flex flex-wrap">
                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.input type="email" required :errors=$errors
                            placeholder="{{ __('employee::fields.email') }} ({{ __('employee::fields.required') }})"
                            value="{{ $employee?->email }}" name="email" :label="__('employee::fields.email')" />
                    </x-employee::form.input-div>

                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.input type="password" :required=!$employee :errors=$errors
                            placeholder="{{ __('employee::fields.password') }}" name="password" :label="__('employee::fields.password')" />
                    </x-employee::form.input-div>
                </div>

                <div class="d-flex flex-wrap">
                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.date-picker name="employmentStartDate" :errors=$errors required
                            value="{{ $employee?->employmentStartDate }}" :label="__('employee::fields.employment_start_date')" />
                    </x-employee::form.input-div>

                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.input :errors=$errors placeholder="{{ __('employee::fields.phone_number') }}"
                            value="{{ $employee?->phoneNumber }}" name="phoneNumber" :label="__('employee::fields.phone_number')" />
                    </x-employee::form.input-div>
                </div>
                <div class="d-flex flex-wrap">
                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <label for="PIN" class="form-label">@lang('employee::fields.employee_access_pin')</label>
                        <div class="input-group">
                            <x-employee::form.input :errors=$errors type="number" placeholder="PIN" name="PIN"
                                value="{{ $employee?->PIN }}" required>
                                <a href="#" id="generate_pin" class="btn btn-light-primary">@lang('employee::general.generate_pin')</a>
                            </x-employee::form.input>
                        </div>
                    </x-employee::form.input-div>
                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.input :errors=$errors type="number" placeholder="00.0" name="wage"
                            :label="__('employee::fields.wage')" />
                    </x-employee::form.input-div>
                </div>
            </x-employee::form.form-card>
        </div>
    </div>
    <x-employee::form.form-buttons cancelUrl="{{ url('/employee') }}" />
</div>
