@extends('establishment::layouts.master')

@section('title', __('establishment::general.service_fee_settings'))
@section('content')
    <div class="d-flex flex-column gap-5">
        <h1 class="fs-2hx fw-bold mb-0">@lang('establishment::general.service_fee_settings')</h1>
        <form method="POST" action="{{ route('cashier-settings.service-fees.update') }}" class="form d-flex flex-column gap-5" id="service_fee_settings_form">
            @csrf
            @method('patch')
            <x-establishment::establishments.service-fee-settings
                :serviceFeeRows="$serviceFeeRows"
                :diningTypes="$diningTypes"
                :cashierPaymentRows="$cashierPaymentRows"
                :branchOptions="$branchOptions" />
            <x-form.form-buttons cancelUrl="{{ url('/establishment') }}" id="service_fee_settings_form" />
        </form>
    </div>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/establishment/js/service-fees.js') }}"></script>
    <script src="{{ url('modules/establishment/js/branch-assignment.js') }}"></script>
    <script>
        $(document).ready(function() {
            initEstablishmentServiceFees();
            initBranchAssignment('#service_fees_root');
        });
    </script>
@endsection
