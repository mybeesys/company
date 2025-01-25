@props(['adjustments_types', 'employees'])
<x-general.modal module="employee" id='add_adjustment_modal' title='add_adjustment' class='mw-800px'>
    <div class="d-flex flex-wrap gap-2">
        <x-form.input-div class="mb-10 w-100">
            <x-form.select name="type" :label="__('employee::fields.adjustment_type')" :options="[
                ['id' => 'allowance', 'name' => __('employee::fields.allowance')],
                ['id' => 'deduction', 'name' => __('employee::fields.deduction')],
            ]" :errors="$errors" data_allow_clear="false"
                required :placeholder="__('employee::fields.adjustment_type')">
            </x-form.select>
        </x-form.input-div>
        <x-form.input-div class="mb-10 w-100">
            <x-form.select name="adjustment_type" :label="__('employee::fields.adjustment_type_name')" :options="[]" disabled :errors="$errors"
                data_allow_clear="false" required :placeholder="__('employee::fields.adjustment_type_name')">
            </x-form.select>
        </x-form.input-div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <x-form.input-div class="mb-10 w-100">
            <x-form.select name="employee_id" :label="__('employee::fields.employee')" :optionName="get_name_by_lang()" :options="$employees" :errors="$errors"
                data_allow_clear="false" required :placeholder="__('employee::fields.employee')">
            </x-form.select>
        </x-form.input-div>
        <x-form.input-div class="mb-10 w-100">
            <x-form.input name="amount" type="number" :label="__('employee::fields.amount')" :errors="$errors" required
                :placeholder="__('employee::fields.amount')">
            </x-form.input>
        </x-form.input-div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <x-form.input-div class="mb-10 w-100">
            <x-form.select name="amount_type" :label="__('employee::fields.amount_type')" :options="[
                ['id' => 'fixed', 'name' => __('employee::general.fixed')],
                ['id' => 'percent', 'name' => __('employee::general.percent')],
            ]" :errors="$errors"
                data_allow_clear="false" required :placeholder="__('employee::fields.amount_type')">
            </x-form.select>
        </x-form.input-div>
        <x-form.input-div class="w-100">
            <x-form.input :placeholder="__('employee::fields.applicable_date')" :label="__('employee::fields.applicable_date')" name="applicable_date" required />
        </x-form.input-div>
    </div>
    <x-form.input-div
        class="w-lg-50 d-md-flex align-items-center fw-bold mb-10 gap-2 form-check form-check-custom form-check-solid">
        <input type="hidden" name="apply_once" value="0">
        <x-form.input type="checkbox" :errors=$errors class="form-check-input" labelClass="mb-0" :label="__('employee::fields.apply_once')"
            :form_control="false" name="apply_once" labelClass="w-50 mb-0" labelWidth />
    </x-form.input-div>
    <input type="hidden" name="id" id="id">
</x-general.modal>
