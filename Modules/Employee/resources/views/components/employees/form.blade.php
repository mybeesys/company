@props(['employee' => null, 'roles', 'permissionSets', 'establishments'])
{{-- employee section --}}
<div class="d-flex flex-column flex-lg-row">
    <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
        <x-form.form-card :title="__('employee::fields.employee_image')" bodyClass="text-center">
            <x-form.image-input :errors=$errors name="image" image="{{ $employee?->image }}" />
            <div class="text-muted fs-7">@lang('employee::general.image_hint')</div>
        </x-form.form-card>

        <x-form.form-card :title="__('employee::fields.status')">
            <x-slot:titleSlot>
                <span class="ms-1" data-bs-toggle="tooltip" title="@lang('employee::general.employee_status_hint')">
                    <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                </span>
            </x-slot>
            <x-form.switch-div>
                <input type="hidden" name="isActive" value="0">
                <x-form.input :errors=$errors class="form-check-input" value="1" type="checkbox"
                    labelClass="form-check-label" name="isActive"
                    label="{{ __('employee::general.deactivate/activate') }}" checked="{{ $employee?->isActive }}" />
            </x-form.switch-div>
        </x-form.form-card>
        <x-form.form-card class="h-100">
        </x-form.form-card>
    </div>
    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
        <div>
            <div class="d-flex flex-column gap-7 gap-lg-10">

                <x-form.form-card :title="__('employee::general.employee_details')">
                    <div class="d-flex flex-wrap">
                        <x-form.input-div class="mb-10 w-100 px-2">
                            <x-form.input required :errors=$errors
                                placeholder="{{ __('employee::fields.name') }} ({{ __('employee::fields.required') }})"
                                value="{{ $employee?->name }}" name="name" :label="__('employee::fields.name')" />
                        </x-form.input-div>
                        <x-form.input-div class="mb-10 w-100 px-2">
                            <x-form.input required :errors=$errors
                                placeholder="{{ __('employee::fields.name_en') }} ({{ __('employee::fields.required') }})"
                                value="{{ $employee?->name_en }}" name="name_en" :label="__('employee::fields.name_en')" />
                        </x-form.input-div>
                    </div>

                    <div class="d-flex flex-wrap">
                        <x-form.input-div class="mb-10 w-100 px-2">
                            <x-form.input type="email" required :errors=$errors
                                placeholder="{{ __('employee::fields.email') }} ({{ __('employee::fields.required') }})"
                                value="{{ $employee?->email }}" name="email" :label="__('employee::fields.email')" />
                        </x-form.input-div>

                        <x-form.input-div class="mb-10 w-100 px-2">
                            <x-form.input :errors=$errors placeholder="{{ __('employee::fields.phone_number') }}"
                                value="{{ $employee?->phoneNumber }}" name="phoneNumber" :label="__('employee::fields.phone_number')" />
                        </x-form.input-div>
                    </div>

                    <div class="d-flex flex-wrap">
                        <x-form.input-div class="mb-10 w-100 px-2">
                            <x-form.date-picker name="employmentStartDate" :errors=$errors required
                                value="{{ $employee?->employmentStartDate }}" :label="__('employee::fields.employment_start_date')" />
                        </x-form.input-div>
                        @if ($employee)
                            <x-form.input-div class="mb-10 w-100 px-2">
                                <x-form.date-picker name="employmentEndDate" :errors=$errors
                                    value="{{ $employee?->employmentEndDate }}" :label="__('employee::fields.employment_end_date')" />
                            </x-form.input-div>
                        @endif

                    </div>
                </x-form.form-card>

                <x-form.form-card :title="__('employee::general.pos')">
                    <div class="d-flex flex-wrap">
                        <x-form.input-div class="mb-10 w-100 px-2">
                            <label for="PIN" class="form-label">@lang('employee::fields.employee_access_pin')</label>
                            <div class="input-group">
                                <x-form.input :errors=$errors type="number" placeholder="PIN" name="PIN"
                                    value="{{ $employee?->PIN }}" required>
                                    <a href="#" id="generate_pin"
                                        class="btn btn-light-primary">@lang('employee::general.generate_pin')</a>
                                </x-form.input>
                            </div>
                        </x-form.input-div>
                    </div>
                    <x-employee::employees.role-wage-repeater :roles=$roles :employee=$employee
                        :establishments=$establishments />
                </x-form.form-card>
            </div>
        </div>
    </div>
</div>
{{-- console access managment (optional) --}}
<div class="mb-5 mt-5">
    <x-form.form-card :title="__('employee::general.dashboard_access')" id="dashboard_managment_access" collapsible
        headerClass="active-managment-fields">
        <x-slot:header>
            <div class="card-toolbar justify-content-end">
                <x-form.switch-div class="form-check-custom">
                    <input type="hidden" name="active_managment_fields_btn" value="0">
                    <x-form.input :errors=$errors class="form-check-input h-20px w-30px" value="1" type="checkbox"
                        name="active_managment_fields_btn" />
                </x-form.switch-div>
            </div>
        </x-slot:header>
        <div class="d-flex flex-wrap">
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input :errors=$errors
                    placeholder="{{ __('employee::fields.user_name') }} ({{ __('employee::fields.required') }})"
                    value="{{ $employee?->administrativeUser?->userName }}" name="username" :label="__('employee::fields.user_name')" />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input type="password" :errors=$errors placeholder="{{ __('employee::fields.password') }}"
                    name="password" :label="__('employee::fields.password')" />
            </x-form.input-div>

        </div>
        <div class="d-flex flex-wrap">
            @php
                $administrativeUser = $employee?->administrativeUser;
            @endphp
            <x-form.input-div class="w-100 w-md-50 mb-10 px-2" :row=false>
                <x-employee::employees.dashboard-role-repeater :permissionSets=$permissionSets
                    :establishments=$establishments :administrativeUser=$administrativeUser />
            </x-form.input-div>
            <x-form.switch-div class="my-md-10 mx-md-10">
                <input type="hidden" name="accountLocked" value="1">
                <x-form.input :errors=$errors class="form-check-input" value="0" type="checkbox"
                    labelClass="form-check-label" name="accountLocked"
                    label="{{ __('employee::general.deactivate/activate') }}"
                    checked="{{ !$employee?->administrativeUser?->accountLocked }}" />
            </x-form.switch-div>

        </div>
    </x-form.form-card>
</div>

<x-form.form-buttons cancelUrl="{{ url('/employee') }}" />
