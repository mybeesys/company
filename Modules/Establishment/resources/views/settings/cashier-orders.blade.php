@extends('establishment::layouts.master')

@php
    $t = fn (string $key) => __('establishment::cashier-orders.'.$key);
    $s = $settings;
@endphp

@section('title', $t('page_title'))
@section('css')
    @parent
    <link rel="stylesheet" href="{{ url('modules/establishment/css/cashier-order-settings.css') }}?v=20261013">
@endsection

@section('content')
    <div class="cashier-order-settings d-flex flex-column gap-5" id="cashier_order_settings_root">
        <div>
            <h1 class="fs-2hx fw-bold mb-2">{{ $t('page_title') }}</h1>
            <p class="text-muted fs-6 mb-0 mw-800px">{{ $t('page_hint') }}</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('cashier-settings.orders.update') }}" class="form" id="cashier_order_settings_form">
            @csrf
            @method('patch')
            <input type="hidden" name="preset" id="cashier_order_preset" value="{{ $s['preset'] }}">

            <div class="row g-5">
                <div class="col-xl-8">
                    @include('establishment::settings.partials.cashier-orders-form', ['s' => $s, 't' => $t])
                    <div class="mt-5">
                        <x-form.form-buttons cancelUrl="{{ url('/settings/cashier-payment-methods') }}" id="cashier_order_settings_form" />
                    </div>
                </div>
                <div class="col-xl-4">
                    @include('establishment::settings.partials.cashier-orders-preview', ['preview' => $preview, 't' => $t])
                </div>
            </div>
        </form>
    </div>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/establishment/js/cashier-order-settings.js') }}?v=20261013"></script>
@endsection
