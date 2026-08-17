@extends('establishment::layouts.master')

@section('title', __('establishment::general.cashier_payment_methods'))
@section('content')
    <div class="d-flex flex-column gap-5">
        <h1 class="fs-2hx fw-bold mb-0">@lang('establishment::general.cashier_payment_methods')</h1>
        <p class="text-muted fs-6 mb-0">@lang('establishment::general.cashier_payment_methods_page_hint')</p>
        <form method="POST" action="{{ route('cashier-settings.payment-methods.update') }}" class="form d-flex flex-column gap-5" id="cashier_payment_settings_form">
            @csrf
            @method('patch')
            <x-establishment::establishments.cashier-payment-methods
                :accounts="$accounts"
                :cashierPaymentRows="$cashierPaymentRows"
                :branchOptions="$branchOptions" />
            <x-form.form-buttons cancelUrl="{{ url('/establishment') }}" id="cashier_payment_settings_form" />
        </form>
    </div>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/establishment/js/cashier-payment-methods.js') }}"></script>
    <script src="{{ url('modules/establishment/js/branch-assignment.js') }}"></script>
    <script>
        $(document).ready(function() {
            initCashierPaymentMethods();
            initBranchAssignment('#cashier_payment_methods_root');
        });
    </script>
@endsection
