@props(['dashboardRole' => null, 'modules'])
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <x-form.form-card :title="__('employee::general.role_details')">
        <div class="d-flex flex-wrap gap-5">
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input required :errors=$errors
                    placeholder="{{ __('employee::fields.name') }} ({{ __('employee::fields.required') }})"
                    value="{{ $dashboardRole?->permissionSetName }}" name="permissionSetName" :label="__('employee::fields.name')" />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input required :errors=$errors placeholder="{{ __('employee::fields.rank') }} (1-999)"
                    value="{{ $dashboardRole?->rank }}" name="rank" :label="__('employee::fields.rank')" />
            </x-form.input-div>
            <x-form.switch-div class="my-auto">
                <input type="hidden" name="isActive" value="0">
                <x-form.input :errors=$errors class="form-check-input" value="1" type="checkbox"
                    labelClass="form-check-label" name="isActive"
                    label="{{ __('employee::general.deactivate/activate') }}"
                    checked="{{ $dashboardRole?->isActive }}" />
            </x-form.switch-div>
        </div>
    </x-form.form-card>

    <div class="table-responsive">
        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
            <thead>
                <tr class="border-0">
                    <th class="w-100 fs-6 fw-bold text-gray-800">
                        <div class="d-flex justify-content-between rounded-start rounded-end bg-light p-5">
                            <div class="w-100 d-flex">
                                <div class="px-10">
                                    @lang('employee::main.permissions')
                                </div>
                            </div>
                            <div class="d-flex justify-content-between w-100">
                                <div class="px-4">@lang('employee::general.show')</div>
                                <div class="px-4">@lang('employee::general.print')</div>
                                <div class="px-4">@lang('employee::general.create')</div>
                                <div class="px-4">@lang('employee::general.edit')</div>
                                <div class="px-4">@lang('employee::general.deletion')</div>
                            </div>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody class="border-bottom border-dashed">
                @foreach ($modules as $moduleName => $module)
                    <tr>
                        <td>
                            {{-- parent row --}}
                            <div class="d-flex justify-content-between w-100 pt-2">
                                <div class="d-flex align-items-center collapsible py-3 toggle mb-0 collapsed w-100"
                                    data-bs-toggle="collapse" data-bs-target="#kt_{{ $loop->index }}"
                                    aria-expanded="true">
                                    <div class="btn btn-sm btn-icon mw-20px btn-active-color-primary me-5 ps-3">
                                        <i class="ki-outline ki-minus-square toggle-on text-primary fs-1"></i>
                                        <i class="ki-outline ki-plus-square toggle-off fs-1"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-gray-700 fw-bold cursor-pointer mb-0 lh-base">
                                            @lang("menuItemLang.{$moduleName}")
                                        </h4>
                                    </div>
                                </div>
                                <div class="w-100 pe-5 my-auto">
                                    <div class="d-flex justify-content-between w-100 gap-11">
                                        <x-form.input-div class="form-check form-check-custom form-check-solid ps-6"
                                            :row="false">
                                            <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                value="{{ $moduleName }}.all.show"
                                                name="permissions[{{ $moduleName }}.all.show]" :form_control="false"
                                                attribute="data-select-all={{ $moduleName }}-all-show" />
                                        </x-form.input-div>
                                        <x-form.input-div class="form-check form-check-custom form-check-solid"
                                            :row="false">
                                            <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                value="{{ $moduleName }}.all.print"
                                                name="permissions[{{ $moduleName }}.all.print]" :form_control="false" />
                                        </x-form.input-div>
                                        <x-form.input-div class="form-check form-check-custom form-check-solid"
                                            :row="false">
                                            <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                value="{{ $moduleName }}.all.create"
                                                name="permissions[{{ $moduleName }}.all.create]" :form_control="false" />
                                        </x-form.input-div>
                                        <x-form.input-div class="form-check form-check-custom form-check-solid"
                                            :row="false">
                                            <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                value="{{ $moduleName }}.all.edit"
                                                name="permissions[{{ $moduleName }}.all.edit]" :form_control="false" />
                                        </x-form.input-div>
                                        <x-form.input-div class="form-check form-check-custom form-check-solid pe-5"
                                            :row="false">
                                            <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                value="{{ $moduleName }}.all.delete"
                                                name="permissions[{{ $moduleName }}.all.delete]"
                                                :form_control="false" />
                                        </x-form.input-div>
                                    </div>
                                </div>
                            </div>
                            {{-- child rows --}}
                            <div id="kt_{{ $loop->index }}" class="collapse">
                                @foreach ($module as $key => $permission)
                                    @php
                                        $show = $permission->has('show') ? $permission['show'] : null;
                                        $print = $permission->has('print') ? $permission['print'] : null;
                                        $create = $permission->has('create') ? $permission['create'] : null;
                                        $edit = $permission->has('edit') ? $permission['edit'] : null;
                                        $delete = $permission->has('delete') ? $permission['delete'] : null;
                                        $name_ar = explode('.', $key)[1];
                                        $name_en = explode('.', $key)[0];
                                    @endphp
                                    <div class="d-flex pt-10">
                                        <div class="w-100 text-gray-700 fw-bold fs-4 d-flex">
                                            <div class="ps-11 w-100">
                                                {{ session()->get('locale') == 'ar' ? $name_ar : $name_en }}
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between w-100 pe-5 gap-11">
                                            <x-form.input-div
                                                class="fv-row form-check form-check-custom form-check-solid ps-6"
                                                :row="false">
                                                <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                    value="{{ $show }}"
                                                    name="permissions[{{ $moduleName . '.' . $name_en . '.show' }}]"
                                                    :form_control="false" disabled="{{ $show ? false : true }}" />
                                            </x-form.input-div>

                                            <x-form.input-div
                                                class="fv-row form-check form-check-custom form-check-solid"
                                                :row="false">
                                                <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                    value="{{ $permission->has('print') ? $permission['print'] : null }}"
                                                    name="permissions[{{ $moduleName . '.' . $name_en . '.print' }}]"
                                                    :form_control="false"
                                                    disabled="{{ $permission->has('print') ? false : true }}" />
                                            </x-form.input-div>

                                            <x-form.input-div
                                                class="fv-row form-check form-check-custom form-check-solid"
                                                :row="false">
                                                <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                    value="{{ $permission->has('create') ? $permission['create'] : null }}"
                                                    name="permissions[{{ $moduleName . '.' . $name_en . '.create' }}]"
                                                    :form_control="false"
                                                    disabled="{{ $permission->has('create') ? false : true }}" />
                                            </x-form.input-div>

                                            <x-form.input-div
                                                class="fv-row form-check form-check-custom form-check-solid"
                                                :row="false">
                                                <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                    value="{{ $permission->has('edit') ? $permission['edit'] : null }}"
                                                    name="permissions[{{ $moduleName . '.' . $name_en . '.edit' }}]"
                                                    :form_control="false"
                                                    disabled="{{ $permission->has('edit') ? false : true }}" />
                                            </x-form.input-div>

                                            <x-form.input-div
                                                class="fv-row form-check form-check-custom form-check-solid pe-5"
                                                :row="false">
                                                <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                    value="{{ $permission->has('delete') ? $permission['delete'] : null }}"
                                                    name="permissions[{{ $moduleName . '.' . $name_en . '.delete' }}]"
                                                    :form_control="false"
                                                    disabled="{{ $permission->has('delete') ? false : true }}" />
                                            </x-form.input-div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-form.form-buttons cancelUrl="{{ url('/dashboard-role') }}" />
</div>
