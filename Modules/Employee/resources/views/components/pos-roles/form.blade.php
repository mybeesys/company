@props(['role' => null, 'departments' => null, 'permissions'])
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <x-form.form-card :title="__('employee::general.role_details')">
        <div class="d-flex flex-wrap">
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input required :errors=$errors
                    placeholder="{{ __('employee::fields.name') }} ({{ __('employee::fields.required') }})"
                    value="{{ $role?->name }}" name="name" :label="__('employee::fields.name')" />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input :errors=$errors placeholder="{{ __('employee::fields.department') }}"
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
                </x-form.input>
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input required :errors=$errors placeholder="{{ __('employee::fields.rank') }} (1-999)"
                    value="{{ $role?->rank }}" name="rank" :label="__('employee::fields.rank')" />
            </x-form.input-div>
        </div>
    </x-form.form-card>
    <x-form.form-card :title="__('employee::main.permissions')" bodyClass="d-flex flex-column flex-md-row justify-content-between">
        <div class="table-responsive w-100">
            <table class="table table-row-dashed border-gray-300 align-middle gy-6">
                <thead>
                    <tr class="w-100">
                    </tr>
                </thead>
                <tbody class="fs-6 fw-semibold">
                    @foreach ($permissions->chunk(3) as $permissionChunk)
                        <tr>
                            @foreach ($permissionChunk as $permission)
                                <td>{{ session()->get('locale') == 'ar' ? $permission->name_ar : $permission->name }}
                                    <x-form.field-hint
                                        hint="{{ session()->get('locale') == 'ar' ? $permission->description_ar : $permission->description }}" />
                                </td>
                                <td>
                                    <x-form.input-div class="form-check form-check-custom form-check-solid">
                                        <x-form.input :errors=$errors class="form-check-input mx-5"
                                            type="checkbox"
                                            value="{{ $permission->getAttributes()['name'] === 'select_all_permissions' ? 'all' : $permission->id }}"
                                            name="permissions[{{ $permission->id }}]" :form_control="false"
                                            checked="{{ $role?->permissions->contains($permission->id) || $role?->permissions->first()->name == 'Select all permissions' }}"
                                            attribute='{{ $permission->getAttributes()["name"] === "select_all_permissions" ? "data-kt-check-target=[data-select-all=permissions] data-kt-check=true data-id={$permission->id}" : "data-select-all=permissions" }}' />
                                    </x-form.input-div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-form.form-card>
    <x-form.form-buttons cancelUrl="{{ url('/pos-role') }}" />
</div>
<input type="hidden" id="role_id" name="role_id" value="">
