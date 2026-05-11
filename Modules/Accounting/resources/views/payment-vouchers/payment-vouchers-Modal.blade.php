<div class="modal fade" id="payment-vouchers-Modal" tabindex="-1" aria-labelledby="payment-vouchers-ModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="payment-vouchers-ModalLabel"><span id="payment_vouchers_title_text">@lang('menuItemLang.payment_vouchers')</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="@lang('sales::general.close')"></button>
            </div>
            <form id="payment-vouchers-form" method="post" action="{{ route('payment-vouchers-store') }}">
                @csrf
                <input type="hidden" name="_method" id="payment_vouchers_method" value="PUT" disabled>
                <div class="modal-body">
                    <div class="card" data-section="contact" style="border: 0;box-shadow: none">
                        <div class="container">

                            {{-- <div class=" align-items-center  mb-5" id="div-cash_account">
                                <label class="fs-6 fw-semibold mb-2 me-3 required"
                                    style="width: 150px;">@lang('accounting::lang.account')</label>

                                <select class="form-select select-2  form-select-solid kt_ecommerce_select2_account "
                                    required
                                    style="padding: 0px 12px;border: 1px solid var(--bs-gray-300); width: 60% !important"
                                    name="account_id" id="cash_account">

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
                            </div> --}}

                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold required">
                                        @lang('accounting::lang.account-credit')
                                        <span class="text-muted">@lang('accounting::lang.voucher_payment_credit_hint')</span>
                                    </label>
                                    <select class="form-select select-2 form-select-solid kt_ecommerce_select2_account" required
                                        name="account_id" id="cash_account">
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}">
                                                @if (app()->getLocale() == 'ar')
                                                    {{ $account->name_ar }} - <span class="fw-semibold mx-2 text-muted fs-5">@lang('accounting::lang.' . $account->account_primary_type)</span>
                                                @else
                                                    {{ $account->name_en }} - <span class="fw-semibold mx-2 text-muted fs-7">@lang('accounting::lang.' . $account->account_primary_type)</span>
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold required">
                                        @lang('accounting::lang.account-debit')
                                        <span class="text-muted">@lang('accounting::lang.voucher_payment_debit_hint')</span>
                                    </label>
                                    <select class="form-select select-2 form-select-solid kt_ecommerce_select2_account" required
                                        name="from_account" id="from_account">
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}">
                                                @if (app()->getLocale() == 'ar')
                                                    {{ $account->name_ar }} - <span class="fw-semibold mx-2 text-muted fs-5">@lang('accounting::lang.' . $account->account_primary_type)</span>
                                                @else
                                                    {{ $account->name_en }} - <span class="fw-semibold mx-2 text-muted fs-7">@lang('accounting::lang.' . $account->account_primary_type)</span>
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold required">@lang('sales::fields.date')</label>
                                    <input class="form-control form-control-solid" name="pament_on" value="{{ now()->format('Y-m-d') }}"
                                        required id="transaction_date" type="date">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold required">@lang('sales::lang.paid_amount')</label>
                                    <input class="form-control form-control-solid no-spin" required name="paid_amount" value=""
                                        placeholder="0.00" id="paid_amount" type="number">
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-6" id="dev-costCenter">
                                    <label class="form-label fw-semibold">@lang('accounting::lang.cost_center')</label>
                                    <select class="form-select select-2 form-select-solid kt_ecommerce_select2_cost_center"
                                        name="cost_center_id" id="cost_center">
                                        <option value=""></option>
                                        @foreach ($cost_centers as $cost_center)
                                            <option value="{{ $cost_center->id }}">
                                                @if (app()->getLocale() == 'ar')
                                                    {{ $cost_center->name_ar }} - <span class="fw-semibold mx-2 text-muted fs-7">{{ $cost_center->account_center_number }}</span>
                                                @else
                                                    {{ $cost_center->name_en }} - <span class="fw-semibold mx-2 text-muted fs-7">{{ $cost_center->account_center_number }}</span>
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">@lang('purchases::lang.description')</label>
                                    <input class="form-control form-control-solid" name="additionalNotes" value=""
                                        placeholder="@lang('purchases::lang.description')" id="notice" type="text">
                                </div>
                            </div>


                            {{-- moved to grid rows above --}}
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
