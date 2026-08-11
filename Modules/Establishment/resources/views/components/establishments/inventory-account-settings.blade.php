@props([
    'establishment' => null,
    'showPerpetualInventoryAccount' => false,
    'perpetualInventoryAccounts' => null,
])
<div class="establishment-inventory-account d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <x-form.form-card bodyClass="d-flex gap-5" :title="__('establishment::general.inventory_account_settings')">
        <div class="w-100">
            @if ($showPerpetualInventoryAccount)
                <div class="row g-4 g-lg-5 mb-0">
                    <div class="col-md-6 col-lg-5">
                        <div class="fv-row w-100">
                            <label class="form-label fw-semibold mb-2" for="perpetual_inventory_account_id">@lang('establishment::fields.perpetual_inventory_account')</label>
                            <select name="perpetual_inventory_account_id" id="perpetual_inventory_account_id"
                                class="form-select form-select-solid select-2 w-100" data-placeholder="@lang('messages.select')">
                                <option value=""></option>
                                @foreach ($perpetualInventoryAccounts ?? [] as $acc)
                                    <option value="{{ $acc->id }}" @selected((string) old('perpetual_inventory_account_id', $establishment?->perpetual_inventory_account_id) === (string) $acc->id)>
                                        {{ app()->getLocale() === 'ar'
                                            ? trim(($acc->gl_code ? $acc->gl_code.' — ' : '').$acc->name_ar.($acc->account_category ? ' ('.$acc->account_category.')' : ''))
                                            : trim(($acc->gl_code ? $acc->gl_code.' — ' : '').$acc->name_en.($acc->account_category ? ' ('.$acc->account_category.')' : '')) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-form.field-hint :hint="__('establishment::general.perpetual_inventory_account_hint')" />
                        </div>
                    </div>
                </div>
            @else
                <span class="text-muted fs-7">@lang('establishment::general.perpetual_inventory_account_policy_note')</span>
            @endif
        </div>
    </x-form.form-card>
</div>
