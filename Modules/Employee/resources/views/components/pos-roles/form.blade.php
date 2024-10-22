@props(['role' => null, 'departments' => null, 'permissions', 'disabled' => false, 'formId'])
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
                <x-form.input required :errors=$errors placeholder="{{ __('employee::fields.rank') }} (1-999)" :disabled=$disabled
                    value="{{ $role?->rank }}" name="rank" :label="__('employee::fields.rank')" />
            </x-form.input-div>
        </div>
    </x-form.form-card>
    <x-form.form-card :title="__('employee::main.permissions')" bodyClass="d-flex flex-column flex-md-row justify-content-between">
        <div class="table-responsive ">
            <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                <x-tables.table-header model="permission" module="employee" :export="false" :addButton="false" />
            </x-cards.card-header>
            <table class="table table-row-dashed border-gray-300 align-middle gy-6" id="role-permission-table">
                <thead>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="fs-6 fw-semibold">
                    @foreach ($permissions->chunk(3) as $permissionChunk)
                        <tr>
                            @foreach ($permissionChunk as $permission)
                                <td>{{ session()->get('locale') == 'ar' ? $permission->name_ar : $permission->modifiedName }}
                                    <x-form.field-hint
                                        hint="{{ session()->get('locale') == 'ar' ? $permission->description_ar : $permission->description }}" />
                                </td>
                                <td>
                                    <x-form.input-div class="form-check form-check-custom form-check-solid">
                                        <x-form.input :errors=$errors class="form-check-input mx-5" type="checkbox"
                                            value="{{ $permission->name === 'select_all_permissions' ? 'all' : $permission->id }}"
                                            name="permissions[{{ $permission->id }}]" :form_control="false" :disabled=$disabled
                                            checked="{{ $role?->permissions->contains($permission->id) || $role?->permissions?->first()?->name == 'select_all_permissions' }}"
                                            attribute="{{ $permission->name === 'select_all_permissions' ? 'data-kt-check-target=[data-select-all=permissions] data-kt-check=true data-id=' . $permission->id : 'data-select-all=permissions' }}" />
                                    </x-form.input-div>
                                </td>
                            @endforeach
                            @for ($i = count($permissionChunk); $i < 3; $i++)
                                <td></td>
                                <td></td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-form.form-card>
    <x-form.form-buttons cancelUrl="{{ url('/pos-role') }}" :id=$formId :disabled=$disabled/>
</div>