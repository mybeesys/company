@props(['adjustments' => null, 'adjustment_types', 'disabled' => false, 'type'])
<div id="{{ $type }}_repeater">
    <div class="form-group">
        <div data-repeater-list="{{ $type }}_repeater" class="d-flex flex-column gap-3">
            @foreach (old($type . '_repeater', $adjustments?->isEmpty() ? [null] : $adjustments ?? [null]) as $index => $adjustment)
                <div data-repeater-item class="d-flex flex-wrap align-items-center gap-3">
                    <x-form.input-div class="w-100 min-w-125px">
                        <x-form.select name="{{ $type }}_repeater[{{ $index }}][{{ $type }}_type]"
                            :disabled="$disabled" optionName="translatedName" :options="$adjustment_types" :errors="$errors"
                            data_allow_clear="false" required
                            placeholder="{{ __('employee::fields.' . $type . '_type') }}"
                            value="{{ is_array($adjustment) ? $adjustment[$type . '_type'] ?? '' : $adjustment?->adjustment_type_id ?? '' }}" />
                    </x-form.input-div>
                    <x-form.input-div class="w-100 min-w-75px">
                        <x-form.input :errors="$errors" type="number" placeholder="{{ __('employee::fields.amount') }}"
                            :disabled=$disabled name="{{ $type }}_repeater[{{ $index }}][amount]"
                            required
                            value="{{ is_array($adjustment) ? $adjustment['amount'] ?? '' : $adjustment?->amount }}" />
                    </x-form.input-div>
                    <x-form.input-div class="w-100 min-w-125px">
                        <x-form.select name="{{ $type }}_repeater[{{ $index }}][amount_type]"
                            placeholder="{{ __('employee::fields.amount_type') }}" :disabled=$disabled required
                            data_allow_clear="false" :options="[
                                ['id' => 'fixed', 'name' => __('employee::general.fixed')],
                                ['id' => 'percent', 'name' => __('employee::general.percent')],
                            ]" :errors="$errors"
                            value="{{ is_array($adjustment) ? $adjustment['amount_type'] ?? '' : $adjustment?->amount_type }}" />
                    </x-form.input-div>
                    <x-form.input-div class="w-100 min-w-125px">
                        <x-form.input :placeholder="__('employee::fields.applicable_date')"
                            name="{{ $type }}_repeater[{{ $index }}][applicable_date]"
                            value="{{ is_array($adjustment) ? $adjustment['applicable_date'] ?? '' : $adjustment?->applicable_date }}"
                            required />
                    </x-form.input-div>
                    <button type="button" data-repeater-delete class="btn btn-sm btn-icon btn-light-danger"
                        @disabled($disabled)>
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                    <input type="hidden"
                        name="{{ $type }}_repeater[{{ $index }}][{{ $type }}_id]"
                        value="{{ is_array($adjustment) ? $adjustment[$type . '_id'] ?? '' : $adjustment?->id }}">
                </div>
            @endforeach
        </div>
    </div>
    <div class="form-group mt-7">
        <button type="button" data-repeater-create class="btn btn-sm btn-light-primary" @disabled($disabled)>
            <i class="ki-outline ki-plus fs-2"></i>@lang('employee::general.add_more_' . $type . 's')</button>
    </div>
</div>
