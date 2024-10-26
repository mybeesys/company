@props(['permissions', 'disabled' => false, 'role' => null, 'header' => true])
<div class="table-responsive ">
    @if ($header)
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="permission" module="employee" :export="false" :addButton="false" />
        </x-cards.card-header>
    @endif
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
                                    value="{{ $permission->name == 'select_all_permissions' ? 'all' : $permission->id }}"
                                    name="pos_permissions[{{ $permission->id }}]" :form_control="false" :disabled=$disabled
                                    checked="{{ $role?->permissions->contains($permission->id) || $role?->permissions?->first()?->name == 'select_all_permissions' }}"
                                    attribute="{{ $permission->name === 'select_all_permissions' ? 'data-kt-check-target=[data-select-all=pos_permissions] data-kt-check=true data-id=' . $permission->id : 'data-select-all=pos_permissions' }}" />
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
