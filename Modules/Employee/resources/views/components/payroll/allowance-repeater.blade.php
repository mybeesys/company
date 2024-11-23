@props(['allowances' => null, 'allowances_types', 'disabled' => false])
<div id="allowance_repeater">
    <div class="form-group">
        <div data-repeater-list="allowance_repeater" class="d-flex flex-column gap-3">
            @foreach (old('allowance_repeater', $allowances?->isEmpty() ? [null] : $allowances ?? [null]) as $index => $allowance)
                <div data-repeater-item class="d-flex flex-wrap align-items-center gap-3">
                    <x-form.input-div class="w-100 min-w-125px">
                        <x-form.select name="allowance_repeater[{{ $index }}][allowance_type]" :disabled=$disabled
                            optionName="translatedName" :options="$allowances_types" :errors="$errors" data_allow_clear="false"
                            required placeholder="{{ __('employee::fields.allowance_type') }}"
                            value="{{ is_array($allowance) ? $allowance['allowance_type'] ?? '' : $allowance?->adjustment_type_id }}" />
                    </x-form.input-div>
                    <x-form.input-div class="w-100 min-w-75px">
                        <x-form.input :errors="$errors" type="number" placeholder="{{ __('employee::fields.amount') }}"
                            :disabled=$disabled name="allowance_repeater[{{ $index }}][amount]" required
                            value="{{ is_array($allowance) ? $allowance['amount'] ?? '' : $allowance?->amount }}" />
                    </x-form.input-div>
                    <x-form.input-div class="w-100 min-w-125px">
                        <x-form.select name="allowance_repeater[{{ $index }}][amount_type]"
                            placeholder="{{ __('employee::fields.amount_type') }}" :disabled=$disabled required
                            data_allow_clear="false" :options="[
                                ['id' => 'fixed', 'name' => __('employee::general.fixed')],
                                ['id' => 'percent', 'name' => __('employee::general.percent')],
                            ]" :errors="$errors"
                            value="{{ is_array($allowance) ? $allowance['amount_type'] ?? '' : $allowance?->amount_type }}" />
                    </x-form.input-div>
                    <x-form.input-div class="w-100 min-w-125px">
                        <x-form.input :placeholder="__('employee::fields.applicable_date')" name="allowance_repeater[{{ $index }}][applicable_date]"
                            value="{{ is_array($allowance) ? $allowance['applicable_date'] ?? '' : $allowance?->applicable_date }}"
                            required />
                    </x-form.input-div>
                    <button type="button" data-repeater-delete class="btn btn-sm btn-icon btn-light-danger"
                        @disabled($disabled)>
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                    <input type="hidden" name="allowance_repeater[{{ $index }}][allowance_id]"
                        value="{{ is_array($allowance) ? $allowance['allowance_id'] ?? '' : $allowance?->id }}">
                </div>
            @endforeach
        </div>
    </div>
    <div class="form-group mt-7">
        <button type="button" data-repeater-create class="btn btn-sm btn-light-primary" @disabled($disabled)>
            <i class="ki-outline ki-plus fs-2"></i>@lang('employee::general.add_more_allowances')</button>
    </div>
</div>
