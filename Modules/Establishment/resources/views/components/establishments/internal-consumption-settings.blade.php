@props([
    'establishment' => null,
    'internalConsumptionExpenseAccounts' => null,
])
<div class="establishment-internal-consumption d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <x-form.form-card bodyClass="d-flex gap-5" :title="__('establishment::general.internal_consumption_settings')">
        <div class="w-100">
            <div class="row g-4 g-lg-5 mb-0">
                <div class="col-md-6 col-lg-5">
                    <div class="fv-row w-100">
                        <label class="form-label fw-semibold mb-2" for="internal_consumption_expense_account_id">
                            @lang('establishment::fields.internal_consumption_expense_account')
                        </label>
                        <select name="internal_consumption_expense_account_id" id="internal_consumption_expense_account_id"
                            class="form-select form-select-solid select-2 w-100" data-placeholder="@lang('messages.select')">
                            <option value=""></option>
                            @foreach ($internalConsumptionExpenseAccounts ?? [] as $acc)
                                <option value="{{ $acc->id }}"
                                    @selected((string) old('internal_consumption_expense_account_id', $establishment?->internal_consumption_expense_account_id) === (string) $acc->id)>
                                    {{ app()->getLocale() === 'ar'
                                        ? trim(($acc->gl_code ? $acc->gl_code.' — ' : '').$acc->name_ar.($acc->account_category ? ' ('.$acc->account_category.')' : ''))
                                        : trim(($acc->gl_code ? $acc->gl_code.' — ' : '').$acc->name_en.($acc->account_category ? ' ('.$acc->account_category.')' : '')) }}
                                </option>
                            @endforeach
                        </select>
                        <x-form.field-hint :hint="__('establishment::general.internal_consumption_expense_account_hint')" />
                    </div>
                </div>
            </div>
        </div>
    </x-form.form-card>
</div>
