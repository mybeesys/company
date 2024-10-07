@props(['role' => null, 'departments' => null])
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <x-employee::form.form-card :title="__('employee::general.role_details')">
        <div class="d-flex flex-wrap">
            <x-employee::form.input-div class="mb-10 w-100 px-2">
                <x-employee::form.input required :errors=$errors
                    placeholder="{{ __('employee::fields.name') }} ({{ __('employee::fields.required') }})"
                    value="{{ $role?->name }}" name="name" :label="__('employee::fields.name')" />
            </x-employee::form.input-div>
            <x-employee::form.input-div class="mb-10 w-100 px-2">
                <x-employee::form.input :errors=$errors placeholder="{{ __('employee::fields.department') }}"
                    name="department" :label="__('employee::fields.department')">
                    <x-slot:datalist>
                        <datalist id="departmentlist">
                            @isset($departments)
                                @foreach ($departments as $department)
                                    <option value="{{ $department }}">
                                @endforeach
                            @endisset
                        </datalist>
                    </x-slot:datalist>
                </x-employee::form.input>
            </x-employee::form.input-div>
            <x-employee::form.input-div class="mb-10 w-100 px-2">
                <x-employee::form.input required :errors=$errors placeholder="{{ __('employee::fields.rank') }} (1-999)"
                    value="{{ $role?->rank }}" name="rank" :label="__('employee::fields.rank')" />
            </x-employee::form.input-div>
        </div>
    </x-employee::form.form-card>
    <x-employee::form.form-buttons cancelUrl="{{ url('/role') }}" />
</div>
