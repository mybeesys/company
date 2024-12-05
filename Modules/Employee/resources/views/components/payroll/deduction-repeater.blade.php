@props(['deductions' => null, 'deductions_types', 'disabled' => false])
<div id="deduction_repeater">
    <div class="form-group">
        <div data-repeater-list="deduction_repeater" class="d-flex flex-column gap-3">
            @foreach (old('deduction_repeater', $deductions?->isEmpty() ? [null] : $deductions ?? [null]) as $index => $deduction)
                <div data-repeater-item class="d-flex flex-wrap align-items-center gap-3">
                    <x-form.input-div class="w-100 min-w-125px">
                        <x-form.select name="deduction_repeater[{{ $index }}][deduction_type]" :disabled=$disabled
                            optionName="translatedName" :options="$deductions_types" :errors="$errors" data_allow_clear="false"
                            required placeholder="{{ __('employee::fields.deduction_type') }}"
                            value="{{ is_array($deduction) ? $deduction['deduction_type'] ?? '' : $deduction?->deduction_type_id }}" />
                    </x-form.input-div>
                    <x-form.input-div class="w-100 min-w-75px">
                        <x-form.input :errors="$errors" type="number" placeholder="{{ __('employee::fields.amount') }}"
                            :disabled=$disabled name="deduction_repeater[{{ $index }}][amount]" required
                            value="{{ is_array($deduction) ? $deduction['amount'] ?? '' : $deduction?->amount }}" />
                    </x-form.input-div>
                    <x-form.input-div class="w-100 min-w-125px">
                        <x-form.select name="deduction_repeater[{{ $index }}][amount_type]"
                            placeholder="{{ __('employee::fields.amount_type') }}" :disabled=$disabled required
                            data_allow_clear="false" :options="[
                                ['id' => 'fixed', 'name' => __('employee::general.fixed')],
                                ['id' => 'percent', 'name' => __('employee::general.percent')],
                            ]" :errors="$errors"
                            value="{{ is_array($deduction) ? $deduction['amount_type'] ?? '' : $deduction?->amount_type }}" />
                    </x-form.input-div>
                    <x-form.input-div class="w-100 min-w-125px">
                        <x-form.input :placeholder="__('employee::fields.applicable_date')" name="deduction_repeater[{{ $index }}][applicable_date]"
                            value="{{ is_array($deduction) ? $deduction['applicable_date'] ?? '' : $deduction?->applicable_date }}"
                            required />
                    </x-form.input-div>
                    <button type="button" data-repeater-delete class="btn btn-sm btn-icon btn-light-danger"
                        @disabled($disabled)>
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                    <input type="hidden" name="deduction_repeater[{{ $index }}][deduction_id]"
                        value="{{ is_array($deduction) ? $deduction['deduction_id'] ?? '' : $deduction?->id }}">
                </div>
            @endforeach
        </div>
    </div>
    <div class="form-group mt-7">
        <button type="button" data-repeater-create class="btn btn-sm btn-light-primary" @disabled($disabled)>
            <i class="ki-outline ki-plus fs-2"></i>@lang('employee::general.add_more_deductions')</button>
    </div>
</div>
