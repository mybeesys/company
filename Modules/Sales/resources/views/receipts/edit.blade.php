@extends('layouts.app')

@section('title', __('messages.edit'))
@section('css')
    <style>
        .custom-height {
            height: 35px;
            width: 60%;
        }

        .me-3 {
            margin-right: 0 !important;
        }
    </style>
@stop

@section('content')
    <form method="POST" action="{{ route('receipts-payments.update', $payment) }}">
        @csrf
        @method('PUT')

        <div class="container">
            <div class="row py-2 align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-1">{{ __('messages.edit') }}</h1>
                    <div class="text-muted fs-7">
                        @if ($transaction)
                            @lang('sales::fields.invoice_number'):
                            <a href="{{ url('/transaction-show/' . $transaction->id) }}" class="fw-semibold">{{ $transaction->ref_no ?? '#' . $transaction->id }}</a>
                            — {{ __('sales::fields.payment_ref') }}: <span class="fw-semibold">{{ $payment->payment_ref_no }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-4 d-flex gap-2 justify-content-md-end mt-3 mt-md-0">
                    <a href="{{ route($supplier ? 'suppliers-receipts' : 'receipts') }}" class="btn btn-light">{{ __('messages.cancel') }}</a>
                    <button type="submit" class="btn btn-bg-primary text-white">{{ __('messages.update') }}</button>
                </div>
            </div>
        </div>

        <div class="separator d-flex flex-center my-5">
            <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
        </div>

        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-12 col-lg-6">
                    <div class="card" style="border: 0; box-shadow: none;">
                        <div class="container py-2">
                            <div class="d-flex align-items-center mb-5">
                                <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 150px; flex-shrink: 0;">@lang('accounting::lang.account')</label>
                                <select class="form-select select-2 form-select-solid kt_ecommerce_select2_account" required
                                    style="padding: 0px 12px; border: 1px solid var(--bs-gray-300); width: 60% !important; max-width: 100%;" name="account_id"
                                    id="cash_account">
                                    <option value="">@lang('sales::lang.payment_account_select')</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}" @selected((int) $payment->account_id === (int) $account->id)>
                                            @if (app()->getLocale() == 'ar')
                                                {{ $account->name_ar }} — <span class="fw-semibold text-muted fs-7">@lang('accounting::lang.' . $account->account_primary_type)</span>
                                            @else
                                                {{ $account->name_en }} — <span class="fw-semibold text-muted fs-7">@lang('accounting::lang.' . $account->account_primary_type)</span>
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="d-flex align-items-center mb-5">
                                <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 150px; flex-shrink: 0;">@lang('sales::fields.date')</label>
                                <input class="form-control form-control-solid custom-height" name="payment_on" required
                                    value="{{ \Carbon\Carbon::parse($payment->paid_on)->format('Y-m-d') }}" id="transaction_date" type="date">
                            </div>

                            <div class="d-flex align-items-center mb-5">
                                <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 150px; flex-shrink: 0;">@lang('sales::lang.paid_amount')</label>
                                <input class="form-control form-control-solid no-spin custom-height" required name="paid_amount"
                                    value="{{ old('paid_amount', $payment->amount) }}" placeholder="0.00" id="paid_amount" type="number" step="0.01" min="0.01"
                                    max="{{ number_format($maxPaidAmount, 2, '.', '') }}">
                            </div>
                            <div class="text-muted fs-7 mb-3">
                                @lang('sales::lang.max_amount_for_this_invoice'):
                                <span class="fw-semibold">{{ number_format($maxPaidAmount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card" style="border: 0; box-shadow: none;">
                        <div class="container py-2">
                            <input type="hidden" name="client_id" value="{{ $payment->payment_for }}">

                            <div class="d-flex align-items-center mb-5">
                                @if ($supplier)
                                    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px; flex-shrink: 0;">@lang('sales::fields.supplier')</label>
                                @else
                                    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px; flex-shrink: 0;">@lang('sales::fields.client')</label>
                                @endif
                                <input type="text" class="form-control form-control-solid custom-height" readonly
                                    value="{{ $contact?->name ?? '—' }}">
                            </div>

                            <div class="d-flex align-items-center mb-5">
                                <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px; flex-shrink: 0;" for="cost_center">@lang('accounting::lang.cost_center')</label>
                                <select class="form-select select-2 form-select-solid kt_ecommerce_select2_cost_center" name="cost_center_id" id="cost_center"
                                    style="padding: 0px 12px; border: 1px solid var(--bs-gray-300); width: 60% !important; max-width: 100%;">
                                    <option value=""></option>
                                    @foreach ($cost_centers as $cost_center)
                                        <option value="{{ $cost_center->id }}" @selected((int) ($costCenterId ?? 0) === (int) $cost_center->id)>
                                            @if (app()->getLocale() == 'ar')
                                                {{ $cost_center->name_ar }} — <span class="fw-semibold text-muted fs-7">{{ $cost_center->account_center_number }}</span>
                                            @else
                                                {{ $cost_center->name_en }} — <span class="fw-semibold text-muted fs-7">{{ $cost_center->account_center_number }}</span>
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="d-flex align-items-center mb-5">
                                <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px; flex-shrink: 0;" for="notice">@lang('purchases::lang.description')</label>
                                <input class="form-control form-control-solid custom-height" name="additionalNotes"
                                    value="{{ old('additionalNotes', $payment->note) }}" placeholder="@lang('purchases::lang.description')" id="notice" type="text" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="separator d-flex flex-center my-6">
            <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
        </div>
    </form>
@stop

@section('script')
    <script src="{{ url('/modules/Sales/js/select-2.js') }}"></script>
@stop
