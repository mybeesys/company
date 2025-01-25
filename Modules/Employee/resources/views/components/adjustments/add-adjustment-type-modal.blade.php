<x-general.modal module="employee" id='add_adjustment_type_modal' title='add_adjustment_type' class='mw-800px'>
    <div class="d-flex flex-wrap gap-2 mb-10">
        <x-form.input-div class="w-100">
            <x-form.input name="name" :placeholder="__('employee::fields.name')" :label="__('employee::fields.name')" required />
        </x-form.input-div>
        <x-form.input-div class="w-100">
            <x-form.input name="name_en" :placeholder="__('employee::fields.name_en')" :label="__('employee::fields.name_en')" required />
        </x-form.input-div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <x-form.input-div class="mb-10 w-100">
            <x-form.select name="adjustment_type_type" :label="__('employee::fields.adjustment_type')" :options="[
                ['id' => 'allowance', 'name' => __('employee::fields.allowance')],
                ['id' => 'deduction', 'name' => __('employee::fields.deduction')],
            ]" :errors="$errors"
                data_allow_clear="false" required :placeholder="__('employee::fields.adjustment_type')">
            </x-form.select>
        </x-form.input-div>
    </div>
    <input type="hidden" name="id" id="adjustment_type_id">
</x-general.modal>
