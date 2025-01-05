@props(['adjustments' => null, 'type'])
<div id="{{ $type }}_repeater">
    <div class="form-group">
        <div data-repeater-list="{{ $type }}_repeater" class="d-flex flex-column gap-3">
            <div data-repeater-item class="d-flex flex-wrap align-items-center gap-3">
                <x-form.input-div class="w-100 min-w-150px">
                    <x-form.select name="{{ $type }}_repeater[][adjustment_type]" optionName="translatedName"
                        :options="[]" :errors="$errors" data_allow_clear="false" required
                        placeholder="{{ __('employee::fields.' . $type . '_type') }}" />
                </x-form.input-div>
                <x-form.input-div class="w-100 min-w-75px">
                    <x-form.input :errors="$errors" type="number" placeholder="{{ __('employee::fields.amount') }}"
                        name="{{ $type }}_repeater[][amount]" required attribute="step=0.01" />
                </x-form.input-div>
                <x-form.input-div class="w-100 min-w-125px">
                    <x-form.select name="{{ $type }}_repeater[][amount_type]"
                        placeholder="{{ __('employee::fields.amount_type') }}" required data_allow_clear="false"
                        :options="[
                            ['id' => 'fixed', 'name' => __('employee::general.fixed')],
                            ['id' => 'percent', 'name' => __('employee::general.percent')],
                        ]" :errors="$errors" />
                </x-form.input-div>
                <button type="button" data-repeater-delete class="btn btn-sm btn-icon btn-light-danger">
                    <i class="ki-outline ki-cross fs-1"></i>
                </button>
                <input type="hidden" name="{{ $type }}_repeater[][id]" />
            </div>
        </div>
    </div>
    <div class="form-group mt-7">
        <button type="button" data-repeater-create class="btn btn-sm btn-light-primary">
            <i class="ki-outline ki-plus fs-2"></i>@lang('employee::general.add_more_' . $type . 's')
        </button>
    </div>
</div>
