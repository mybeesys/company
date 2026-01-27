<div class="modal fade" id="edit_payment_method_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog mw-650px">

        <form id="edit_payment_form"  method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h2>@lang('general::general.edit_payment_method')</h2>
                </div>
                <div class="modal-body">
                    <div class="fv-row mb-5">
                        <label class="form-label">@lang('accounting::lang.account')</label>
                        <select name="account_id" id="edit_account_id" class="form-select form-select-solid">
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name_ar }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fv-row mb-5">
                        <label class="form-label">@lang('general::fields.description_ar')</label>
                        <input type="text" name="description_ar" id="edit_desc_ar" class="form-control form-control-solid">
                    </div>
                    <div class="fv-row mb-5">
                        <label class="form-label">@lang('general::fields.description_en')</label>
                        <input type="text" name="description_en" id="edit_desc_en" class="form-control form-control-solid">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                </div>
            </div>
        </form>
    </div>
</div>
