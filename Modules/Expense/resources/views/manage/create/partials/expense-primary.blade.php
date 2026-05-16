<div class="card border-0 shadow-none" data-section="expense-primary">
    <div class="row g-6">
        <div class="col-md-6 fv-row fv-plugins-icon-container" id="div-expense-treasury">
            <label class="fs-6 fw-semibold mb-2 required" for="exp_credit_account">@lang('expense::lang.field_credit_account')</label>
            <select class="form-select form-select-solid w-100" required name="credit_accounting_account_id" id="exp_credit_account">
                @foreach ($treasuryAccounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->gl_code }} —
                        {{ app()->getLocale() === 'ar' ? $acc->name_ar : $acc->name_en }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 fv-row fv-plugins-icon-container">
            <label class="fs-6 fw-semibold mb-2 required" for="exp_category_id">@lang('expense::lang.field_category')</label>
            <select class="form-select form-select-solid w-100" required name="expense_category_id" id="exp_category_id">
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 fv-row fv-plugins-icon-container">
            <label class="fs-6 fw-semibold mb-2 required" for="expense_date">@lang('expense::lang.field_date')</label>
            <input class="form-control form-control-solid w-100" type="date" name="date" required id="expense_date"
                value="{{ now()->format('Y-m-d') }}">
        </div>

        <div class="col-md-6 fv-row fv-plugins-icon-container">
            <label class="fs-6 fw-semibold mb-2 required" for="exp_amount">@lang('expense::lang.field_amount')</label>
            <input class="form-control form-control-solid w-100 no-spin" type="number" step="0.01" min="0.01" name="amount" id="exp_amount" required>
        </div>

        <div class="col-md-6 fv-row fv-plugins-icon-container">
            <label class="fs-6 fw-semibold mb-2" for="tax_id">@lang('expense::lang.field_tax')</label>
            <select class="form-select form-select-solid w-100" name="tax_id" id="tax_id">
                <option value="">@lang('expense::lang.tax_option_none')</option>
                @foreach ($taxes as $t)
                    @php($pct = \Modules\Expense\Services\ExpenseTaxCalculator::effectivePercent($t))
                    <option value="{{ $t->id }}" data-percent="{{ $pct }}">
                        {{ app()->getLocale() === 'ar' ? $t->name : ($t->name_en ?: $t->name) }}
                        ({{ number_format($pct, 2) }}%)</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 fv-row fv-plugins-icon-container d-flex align-items-end">
            <div class="form-check form-check-custom form-check-solid py-2 mb-md-2">
                <input class="form-check-input" type="checkbox" name="amount_includes_tax" value="1" id="amt_inc_tax" disabled>
                <label class="form-check-label fs-6 fw-semibold" for="amt_inc_tax">@lang('expense::lang.field_amount_includes_tax')</label>
            </div>
        </div>
    </div>

    <div class="row mt-6">
        <div class="col-12">
            <div class="d-none" id="tax_preview_row">
                <div class="rounded border border-gray-300 border-dashed p-4 p-lg-5 bg-light-subtle">
                    <div class="row g-4 g-lg-6">
                        <div class="col-sm-4">
                            <label class="form-label fs-7 text-muted mb-1">@lang('expense::lang.field_net_preview')</label>
                            <div id="net_preview" class="fw-bold text-primary fs-5">—</div>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label fs-7 text-muted mb-1">@lang('expense::lang.field_tax_preview')</label>
                            <div id="tax_preview" class="fw-bold text-primary fs-5">—</div>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label fs-7 text-muted mb-1">@lang('expense::lang.field_gross_preview')</label>
                            <div id="gross_preview" class="fw-bold text-primary fs-5">—</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
