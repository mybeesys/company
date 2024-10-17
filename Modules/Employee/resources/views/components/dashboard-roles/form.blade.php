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
                    label="{{ __('employee::general.deactivate/activate') }}" checked="{{ $dashboardRole?->isActive }}" />
            </x-form.switch-div>
        </div>
    </x-form.form-card>
    <div class="table-responsive">

        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">

            <thead>
                <tr class="fw-bold fs-6 text-gray-800 text-center border-0 bg-light">
                    <th class="rounded-start"></th>
                    <th class="">@lang('employee::general.show')</th>
                    <th class="">@lang('employee::general.create')</th>
                    <th class="">@lang('employee::general.edit')</th>
                    <th class=" rounded-end">@lang('employee::general.deletion')</th>
                </tr>
            </thead>

            <tbody class="border-bottom border-dashed">
                <tr class="fw-semibold fs-6 text-gray-800 text-center">
                    <td class="text-start ps-6 fs-4">Number of end products or domains</td>
                    <td>
                        <x-form.input-div class="form-check form-check-custom form-check-solid justify-content-center">
                            <x-form.input :errors=$errors class="form-check-input mx-5" type="checkbox"
                                value="1"
                                name="permissions[]" :form_control="false"
                                attribute="data-kt-check-target=[data-select-all=permissions] data-kt-check=true" />
                        </x-form.input-div>
                    </td>
                    <td>
                        <div class="form-check form-check-custom justify-content-center">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" />
                        </div>
                    </td>
                    <td>
                        <div class="form-check form-check-custom justify-content-center">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" />
                        </div>
                    </td>
                    <td>
                        <div class="form-check form-check-custom justify-content-center">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" />
                        </div>
                    </td>
                </tr>
                <tr class="text-center">
                    <td class="text-start ps-6">
                        <div class="fw-semibold fs-4 text-gray-800">End product with paid services</div>
                    </td>
                    <td>
                        <div class="form-check form-check-custom justify-content-center">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" />
                        </div>
                    </td>
                    <td>
                        <div class="form-check form-check-custom justify-content-center">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" />
                        </div>
                    </td>
                    <td>
                        <div class="form-check form-check-custom justify-content-center">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" />
                        </div>
                    </td>
                    <td>
                        <div class="form-check form-check-custom justify-content-center">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" />
                        </div>
                    </td>
                </tr>
                <tr class="text-center">
                    <td class="text-start ps-6">
                        <div class="fw-semibold fs-4 text-gray-800">End product with paid services</div>
                    </td>
                    <td>
                        <div class="form-check form-check-custom justify-content-center">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" />
                        </div>
                    </td>
                    <td>
                        <div class="form-check form-check-custom justify-content-center">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" />
                        </div>
                    </td>
                    <td>
                        <div class="form-check form-check-custom justify-content-center">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" />
                        </div>
                    </td>
                    <td>
                        <div class="form-check form-check-custom justify-content-center">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" />
                        </div>
                    </td>
                </tr>
            </tbody>
            <!--end::Table body-->
        </table>
        <!--end::Table-->
    </div>
    <x-form.form-buttons cancelUrl="{{ url('/dashboard-role') }}" />
</div>
