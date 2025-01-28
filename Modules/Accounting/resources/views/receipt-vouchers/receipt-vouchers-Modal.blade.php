<div class="modal fade" id="receipt-vouchers-Modal" tabindex="-1" aria-labelledby="receipt-vouchers-ModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receipt-vouchers-ModalLabel">@lang('menuItemLang.receipt_vouchers')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="@lang('sales::general.close')"></button>
            </div>
            <form id="receipt-vouchers-store" method="post" action="{{ route('receipt-vouchers-store') }}">
                @csrf
                <div class="modal-body">
                    <div class="card" data-section="contact" style="border: 0;box-shadow: none">
                        <div class="container">

                            <div class=" align-items-center  mb-5" id="div-cash_account">
                                <label class="fs-6 fw-semibold mb-2 me-3 required"
                                    style="width: 150px;">@lang('accounting::lang.account')</label>

                                <select class="form-select select-2  form-select-solid kt_ecommerce_select2_account "
                                    required
                                    style="padding: 0px 12px;border: 1px solid var(--bs-gray-300); width: 60% !important"
                                    name="account_id" id="cash_account">

                                    {{-- <option value="">@lang('sales::lang.payment_account_select')</option> --}}
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}">
                                            @if (app()->getLocale() == 'ar')
                                                {{ $account->name_ar }} - <span
                                                    class="fw-semibold mx-2 text-muted fs-5">@lang('accounting::lang.' . $account->account_primary_type)</span>
                                            @else
                                                {{ $account->name_en }} - <span
                                                    class="fw-semibold mx-2 text-muted fs-7">@lang('accounting::lang.' . $account->account_primary_type)</span>
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>


                            <div class=" align-items-center mb-5">


                                <label class="fs-6 fw-semibold mb-2 me-3 required"
                                    style="width: 150px;">@lang('sales::fields.date')</label>

                                <input class="form-control form-control-solid custom-height" name="pament_on"
                                    value="{{ now()->format('Y-m-d') }}" required placeholder="@lang('sales::fields.date')"
                                    id="transaction_date" type="date">
                            </div>


                            <div class=" align-items-center mb-5">

                                <label class="fs-6 fw-semibold mb-2 me-3 required "
                                    style="width: 150px;">@lang('sales::lang.paid_amount')</label>

                                <input class="form-control form-control-solid no-spin custom-height" required
                                    name="paid_amount" value="" placeholder="0.00" id="paid_amount"
                                    type="number">
                            </div>


                            <div class=" align-items-center mb-5">
                                <label class="fs-6 fw-semibold mb-2 me-3 "
                                    style="width: 150px;">@lang('purchases::lang.description')</label>
                                <input class="form-control form-control-solid custom-height" name="additionalNotes"
                                    value="" placeholder="@lang('purchases::lang.description')" id="notice" type="text">
                            </div>
                        </div>
                    </div>


                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>
