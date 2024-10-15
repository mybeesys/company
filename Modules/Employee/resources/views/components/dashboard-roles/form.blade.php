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
        <!--begin::Table-->
        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
            <!--begin::Table head-->
            <thead>
                <tr class="fw-bold fs-6 text-gray-800 text-center border-0 bg-light">
                    <th class="rounded-start"></th>
                    <th class="">Regular</th>
                    <th class="">Multiple</th>
                    <th class="">Extended</th>
                    <th class="">Extended</th>
                    <th class="">Extended</th>
                    <th class=" rounded-end">Extended</th>
                </tr>
            </thead>
            <!--end::Table head-->
            <!--begin::Table body-->
            <tbody class="border-bottom border-dashed">
                <tr class="fw-semibold fs-6 text-gray-800 text-center">
                    <td class="text-start ps-6 fs-4">Number of end products or domains</td>
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
