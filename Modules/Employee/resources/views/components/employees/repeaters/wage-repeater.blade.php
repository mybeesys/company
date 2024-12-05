@props(['employee' => null, 'establishments', 'disabled' => false, 'wageTypes'])
<div id="wage_repeater">
    <div class="form-group">
        <div data-repeater-list="wage_repeater" class="d-flex flex-column gap-3">
            @foreach (old('wage_repeater', $employee?->wages->isEmpty() ? [null] : $employee?->wages ?? [null]) as $index => $wage)
                <div data-repeater-item class="d-flex flex-column">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <x-form.input-div class="w-100 min-w-150px">
                            <x-form.input :errors="$errors" type="number" :placeholder="__('employee::fields.wage')" :disabled=$disabled required
                                name="wage_repeater[{{ $index }}][wage]" :value="is_array($wage) ? $wage['wage'] ?? '' : $wage?->rate" />
                        </x-form.input-div>
                        <x-form.input-div class="w-100 min-w-150px">
                            <x-form.select name="wage_repeater[{{ $index }}][wage_type]" :disabled=$disabled required
                                :options=$wageTypes :errors="$errors" :value="is_array($wage) ? $wage['wage_type'] ?? '' : $wage?->wage_type"
                                placeholder="{{ __('employee::fields.wage_type') }}" />
                        </x-form.input-div>
                        <x-form.input-div class="w-100 min-w-150px">
                            <x-form.select name="wage_repeater[{{ $index }}][establishment]" data_allow_clear="false"
                                :disabled=$disabled :options="$establishments" :errors="$errors" required
                                value="{{ is_array($wage) ? $role['establishment'] ?? '' : $wage?->establishment_id }}" />
                        </x-form.input-div>
                        <input type="hidden" name="wage_repeater[{{ $index }}][wage_id]"
                            value="{{ is_array($wage) ? $wage['wage_id'] ?? '' : $wage?->id }}">
                        <button type="button" data-repeater-delete class="btn btn-sm btn-icon btn-light-danger"
                            @disabled($disabled)>
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="separator border-secondary my-5"></div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="form-group mt-7">
        <button type="button" data-repeater-create class="btn btn-sm btn-light-primary" @disabled($disabled)>
            <i class="ki-outline ki-plus fs-2"></i>@lang('employee::general.add_more_wages')</button>
    </div>
</div>
