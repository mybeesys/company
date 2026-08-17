@extends('establishment::layouts.master')

@section('title', __('establishment::general.internal_consumption_settings'))
@section('content')
    <div class="d-flex flex-column gap-5">
        <h1 class="fs-2hx fw-bold mb-0">@lang('establishment::general.internal_consumption_settings')</h1>
        <form method="POST" action="{{ route('cashier-settings.internal-consumption.update') }}" class="form d-flex flex-column gap-5" id="internal_consumption_settings_form">
            @csrf
            @method('patch')
            <x-establishment::establishments.internal-consumption-settings
                :accounts="$accounts"
                :internalConsumptionRows="$internalConsumptionRows"
                :branchOptions="$branchOptions" />
            <x-form.form-buttons cancelUrl="{{ url('/establishment') }}" id="internal_consumption_settings_form" />
        </form>
    </div>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/establishment/js/internal-consumption-types.js') }}"></script>
    <script src="{{ url('modules/establishment/js/branch-assignment.js') }}"></script>
    <script>
        $(document).ready(function() {
            initInternalConsumptionTypes();
            initBranchAssignment('#internal_consumption_types_root');
        });
    </script>
@endsection
