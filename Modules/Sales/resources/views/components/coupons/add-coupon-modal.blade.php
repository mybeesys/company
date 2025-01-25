@props(['products', 'categories', 'establishments'])
<x-general.modal module="sales" id='add_coupon_modal' title='add_coupon' class='modal-xl'>
    <div class="d-flex flex-wrap gap-4">
        <x-form.input-div class="mb-10 w-100 px-2">
            <x-form.input required :errors=$errors placeholder="{{ __('sales::fields.name') }}" value=""
                name="name" :label="__('sales::fields.name')" />
        </x-form.input-div>
        {{-- <x-form.input-div class="mb-10 w-100 px-2">
            <x-form.input :errors=$errors placeholder="{{ __('sales::fields.code') }}" value="" name="code"
                label="{{ __('sales::fields.code') }} ({{ __('sales::general.auto_generate_hint') }})" />
        </x-form.input-div> --}}
        <x-form.input-div class="mb-10 w-100 px-2">
            <label for="code" class="form-label required">@lang('sales::fields.code')</label>
            <div class="input-group">
                <x-form.input :errors=$errors placeholder="code" name="code" required>
                    <button type="button" id="generate_code"
                        class="btn btn-light-primary">@lang('employee::general.generate_pin')</button>
                </x-form.input>
            </div>
        </x-form.input-div>
        <x-form.input-div class="mb-10 w-100">
            <x-form.select name="establishments_ids" :label="__('employee::fields.establishment')" :options=$establishments :errors="$errors"
                data_allow_clear="false" required placeholder="{{ __('employee::fields.establishment') }}"
                attribute="multiple" no_default>
                <button type="button" id="est-select-all-btn"
                    class="btn btn-primary px-4 py-1 fs-7 ms-2 mb-1">{{ __('employee::general.select_all') }}</button>
                <button type="button" id="est-deselect-all-btn"
                    class="btn btn-secondary px-4 py-1 fs-7 mb-1">{{ __('employee::general.deselect_all') }}</button>
            </x-form.select>
        </x-form.input-div>
    </div>
    <div class="d-flex flex-wrap gap-4">
        <x-form.input-div class="mb-10 w-100">
            <x-form.select name="discount_apply_to" :label="__('sales::fields.discount_apply_to')" :options="[
                ['id' => 'all', 'name' => __('sales::fields.all')],
                ['id' => 'category', 'name' => __('sales::fields.category')],
                ['id' => 'product', 'name' => __('sales::fields.product')],
            ]" :errors="$errors"
                data_allow_clear="false" required :placeholder="__('sales::fields.discount_apply_to')">
            </x-form.select>
        </x-form.input-div>
        <x-form.input-div class="mb-10 w-100">
            <x-form.select name="categories_ids" :label="__('sales::fields.category')" :options="$categories" :errors="$errors"
                data_allow_clear="false" attribute="multiple" no_default :optionName="session('locale') === 'ar' ? 'name_ar' : 'name_en'">
                <button type="button" id="category-select-all-btn"
                    class="btn btn-primary px-4 py-1 fs-7 ms-2 mb-1">{{ __('employee::general.select_all') }}</button>
                <button type="button" id="category-deselect-all-btn"
                    class="btn btn-secondary px-4 py-1 fs-7 mb-1">{{ __('employee::general.deselect_all') }}</button>
            </x-form.select>
        </x-form.input-div>
        <x-form.input-div class="mb-10 w-100">
            <x-form.select name="products_ids" :label="__('sales::fields.product')" :options="$products" :errors="$errors"
                data_allow_clear="false" attribute="multiple" no_default :optionName="session('locale') === 'ar' ? 'name_ar' : 'name_en'">
                <button type="button" id="product-select-all-btn"
                    class="btn btn-primary px-4 py-1 fs-7 ms-2 mb-1">{{ __('employee::general.select_all') }}</button>
                <button type="button" id="product-deselect-all-btn"
                    class="btn btn-secondary px-4 py-1 fs-7 mb-1">{{ __('employee::general.deselect_all') }}</button>
            </x-form.select>
        </x-form.input-div>
    </div>

    <div class="d-flex flex-wrap gap-4">
        <x-form.input-div class="mb-10 w-100">
            <x-form.input name="coupon_count" type="number" :label="__('sales::fields.coupon_count')" :errors="$errors" required
                :placeholder="__('sales::fields.coupon_count')">
            </x-form.input>
        </x-form.input-div>
        <x-form.input-div class="mb-10 w-100">
            <x-form.input name="person_use_time_count" type="number" :label="__('sales::fields.person_use_time_count')" :errors="$errors" required
                :placeholder="__('sales::fields.person_use_time_count')">
            </x-form.input>
        </x-form.input-div>
        <x-form.input-div class="mb-10 w-100">
            <x-form.input name="start_date" :label="__('sales::fields.start_date')" :errors="$errors" required :placeholder="__('sales::fields.start_date')">
            </x-form.input>
        </x-form.input-div>
        <x-form.input-div class="mb-10 w-100">
            <x-form.input name="end_date" :label="__('sales::fields.end_date')" :errors="$errors" required :placeholder="__('sales::fields.end_date')">
            </x-form.input>
        </x-form.input-div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <x-form.input-div class="mb-10 w-100">
            <x-form.input name="value" type="number" :label="__('sales::fields.value')" :errors="$errors" required
                :placeholder="__('sales::fields.value')">
            </x-form.input>
        </x-form.input-div>
        <x-form.input-div class="mb-10 w-100">
            <x-form.select name="value_type" :label="__('sales::fields.value_type')" :options="[
                ['id' => 'fixed', 'name' => __('sales::general.fixed')],
                ['id' => 'percent', 'name' => __('sales::general.percent')],
            ]" :errors="$errors"
                data_allow_clear="false" required :placeholder="__('sales::fields.value_type')">
            </x-form.select>
        </x-form.input-div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <x-form.input-div
            class="w-lg-50 d-md-flex align-items-center fw-bold mb-10 gap-2 form-check form-check-custom form-check-solid">
            <input type="hidden" name="apply_to_clients_groups" value="0">
            <x-form.input type="checkbox" :errors=$errors class="form-check-input" labelClass="mb-0" :label="__('sales::fields.apply_to_clients_groups')"
                :form_control="false" name="apply_to_clients_groups" labelClass="w-50 mb-0" labelWidth />
        </x-form.input-div>

        <x-form.input-div
            class="w-lg-50 d-md-flex align-items-center fw-bold mb-10 gap-2 form-check form-check-custom form-check-solid">
            <input type="hidden" name="is_active" value="0">
            <x-form.input type="checkbox" :errors=$errors class="form-check-input" labelClass="mb-0" :label="__('sales::fields.is_active')"
                :form_control="false" name="is_active" labelClass="w-50 mb-0" labelWidth />
        </x-form.input-div>
    </div>
    <input type="hidden" name="id" id="id">

</x-general.modal>
