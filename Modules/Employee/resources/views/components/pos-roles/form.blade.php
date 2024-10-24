@props(['role' => null, 'departments' => null, 'permissions', 'disabled' => false, 'formId' => null])
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <x-form.form-card :title="__('employee::general.role_details')">
        <div class="d-flex flex-wrap">
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input required :errors=$errors :disabled=$disabled
                    placeholder="{{ __('employee::fields.name') }} ({{ __('employee::fields.required') }})"
                    value="{{ $role?->name }}" name="name" :label="__('employee::fields.name')" />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input :errors=$errors placeholder="{{ __('employee::fields.department') }}" name="department"
                    :label="__('employee::fields.department')" :disabled=$disabled>
                    <x-slot:datalist>
                        <datalist id="departmentlist">
                            @isset($departments)
                                @foreach ($departments as $department)
                                    <option value="{{ $department }}">
                                @endforeach
                            @endisset
                        </datalist>
                    </x-slot:datalist>
                </x-form.input>
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input required :errors=$errors placeholder="{{ __('employee::fields.rank') }} (1-999)"
                    :disabled=$disabled value="{{ $role?->rank }}" name="rank" :label="__('employee::fields.rank')" />
            </x-form.input-div>
        </div>
    </x-form.form-card>
    <x-form.form-card :title="__('employee::main.permissions')" bodyClass="d-flex flex-column flex-md-row justify-content-between">
        <x-employee::pos-roles.permissions-input :permissions=$permissions :disabled=$disabled :role=$role />
    </x-form.form-card>
    <x-form.form-buttons cancelUrl="{{ url('/pos-role') }}" :id=$formId :disabled=$disabled />
</div>
