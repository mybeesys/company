@props(['dashboardRole' => null])
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
                <tr class="fw-bold fs-6 text-gray-800 text-center border-0 bg-light">
                    <th class="rounded-start rounded-end d-flex justify-content-between w-100">
                        <div class="w-100 d-flex">
                            <div class="px-10">
                                @lang('employee::main.permissions')
                            </div>
                        </div>
                        <div class="d-flex justify-content-between w-100 px-5">
                            <div class="px-4">@lang('employee::general.show')</div>
                            <div class="px-4">@lang('employee::general.print')</div>
                            <div class="px-4">@lang('employee::general.create')</div>
                            <div class="px-4">@lang('employee::general.edit')</div>
                        </div>
                    </th>
                </tr>
            </thead>

            <tbody class="border-bottom border-dashed">
                <tr>
                    <td>
                        {{-- parent row --}}
                        <div class="d-flex justify-content-between w-100 pt-2">
                            <div class="d-flex align-items-center collapsible py-3 toggle mb-0 active w-100"
                                data-bs-toggle="collapse" data-bs-target="#kt_job_4_1" aria-expanded="true">
                                <div class="btn btn-sm btn-icon mw-20px btn-active-color-primary me-5">
                                    <i class="ki-outline ki-minus-square toggle-on text-primary fs-1"></i>
                                    <i class="ki-outline ki-plus-square toggle-off fs-1"></i>
                                </div>
                                <div>
                                    <h4 class="text-gray-700 fw-bold cursor-pointer mb-0 lh-base">How does it work How
                                    </h4>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between w-100 px-5">
                                <x-form.input-div class="form-check form-check-custom form-check-solid"
                                    :row="false">
                                    <x-form.input :errors=$errors class="form-check-input mx-5" type="checkbox"
                                        value="1" name="permissions[]" :form_control="false"
                                        attribute="data-kt-check-target=[data-select-all=permissions] data-kt-check=true" />
                                </x-form.input-div>
                                <x-form.input-div class="form-check form-check-custom form-check-solid"
                                    :row="false">
                                    <x-form.input :errors=$errors class="form-check-input mx-5" type="checkbox"
                                        value="1" name="permissions[]" :form_control="false"
                                        attribute="data-kt-check-target=[data-select-all=permissions] data-kt-check=true" />
                                </x-form.input-div>
                                <x-form.input-div class="form-check form-check-custom form-check-solid"
                                    :row="false">
                                    <x-form.input :errors=$errors class="form-check-input mx-5" type="checkbox"
                                        value="1" name="permissions[]" :form_control="false"
                                        attribute="data-kt-check-target=[data-select-all=permissions] data-kt-check=true" />
                                </x-form.input-div>
                                <x-form.input-div class="form-check form-check-custom form-check-solid"
                                    :row="false">
                                    <x-form.input :errors=$errors class="form-check-input mx-5" type="checkbox"
                                        value="1" name="permissions[]" :form_control="false"
                                        attribute="data-kt-check-target=[data-select-all=permissions] data-kt-check=true" />
                                </x-form.input-div>
                            </div>
                        </div>
                        {{-- child rows --}}
                        <div id="kt_job_4_1" class="collapse show">
                            <div class="d-flex">
                                <div class="w-100 text-gray-700 fw-bold fs-4 py-10 d-flex">
                                    <div class="ps-11">
                                        How does it work
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between w-100 px-5">
                                    <x-form.input-div class="fv-row form-check form-check-custom form-check-solid"
                                        :row="false">
                                        <x-form.input :errors=$errors class="form-check-input mx-5" type="checkbox"
                                            value="1" name="permissions[]" :form_control="false"
                                            attribute="data-kt-check-target=[data-select-all=permissions] data-kt-check=true" />
                                    </x-form.input-div>
                                    <x-form.input-div class="fv-row form-check form-check-custom form-check-solid"
                                        :row="false">
                                        <x-form.input :errors=$errors class="form-check-input mx-5" type="checkbox"
                                            value="1" name="permissions[]" :form_control="false"
                                            attribute="data-kt-check-target=[data-select-all=permissions] data-kt-check=true" />
                                    </x-form.input-div>
                                    <x-form.input-div class="fv-row form-check form-check-custom form-check-solid"
                                        :row="false">
                                        <x-form.input :errors=$errors class="form-check-input mx-5" type="checkbox"
                                            value="1" name="permissions[]" :form_control="false"
                                            attribute="data-kt-check-target=[data-select-all=permissions] data-kt-check=true" />
                                    </x-form.input-div>
                                    <x-form.input-div class="fv-row form-check form-check-custom form-check-solid"
                                        :row="false">
                                        <x-form.input :errors=$errors class="form-check-input mx-5" type="checkbox"
                                            value="1" name="permissions[]" :form_control="false"
                                            attribute="data-kt-check-target=[data-select-all=permissions] data-kt-check=true" />
                                    </x-form.input-div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <x-form.form-buttons cancelUrl="{{ url('/dashboard-role') }}" />
</div>
