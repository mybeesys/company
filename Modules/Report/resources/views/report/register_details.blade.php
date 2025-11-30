{{-- resources/views/report/register_details.blade.php --}}
@extends('layouts.app') {{-- أو أي layout عندك --}}

@section('title', __('report::fields.register_details'))

@section('content')
<div class="container">
    @php
        $register = $register_details;
        $details = $details;
    @endphp

    <div class="card">
        <div class="card-header">
            <h3>
                @lang('report::fields.register_details')
                ( {{ \Carbon\Carbon::parse($register->open_time)->format('jS M, Y h:i A') }}
                - {{ \Carbon\Carbon::parse($close_time)->format('jS M, Y h:i A') }} )
            </h3>
        </div>

        <div class="card-body">

             @include('report::report.payment_details', ['details' => $details])

            <hr>

            @if(!empty($register->denominations))
                @php $total = 0; @endphp
                <h4>@lang('report::fields.cash_denominations')</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-right">@lang('report::fields.denomination')</th>
                            <th class="text-center">@lang('report::fields.count')</th>
                            <th class="text-left">@lang('report::fields.subtotal')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($register->denominations as $key => $value)
                        <tr>
                            <td class="text-right">{{ $key }}</td>
                            <td class="text-center">{{ $value ?? 0 }}</td>
                            <td class="text-left">@format_currency($key * $value)</td>
                        </tr>
                        @php $total += ($key * $value); @endphp
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-center">@lang('report::fields.total')</th>
                            <th>@format_currency($total)</th>
                        </tr>
                    </tfoot>
                </table>
            @endif

            {{-- بيانات المستخدم والموقع --}}
            <div class="row mt-4">
                <div class="col-md-6">
                    <b>@lang('report::fields.user'):</b> {{ $register->user_name }}<br>
                    <b>@lang('report::fields.email'):</b> {{ $register->email }}<br>
                    <b>@lang('report::fields.business_location'):</b> {{ $register->location_name }}<br>
                </div>
                @if(!empty($register->closing_note))
                <div class="col-md-6">
                    <strong>@lang('report::fields.closing_note'):</strong><br>
                    {{ $register->closing_note }}
                </div>
                @endif
            </div>

            {{-- تفاصيل المنتجات --}}
            @if(!empty($details['product_details']))
                <hr>
                <h4>@lang('report::fields.products')</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>@lang('report::fields.product')</th>
                            <th>@lang('report::fields.quantity')</th>
                            <th>@lang('report::fields.subtotal')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($details['product_details'] as $product)
                        <tr>
                            <td>{{ $product->product_name_ar }} / {{ $product->product_name_en }}</td>
                            <td>{{ $product->total_quantity }}</td>
                            <td>@format_currency($product->total_amount)</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- ملخص المعاملات --}}
            <hr>
            <h4>@lang('report::fields.summary')</h4>
            <table class="table table-bordered">
                <tr>
                    <th>@lang('report::fields.total_sales')</th>
                    <td>@format_currency($details['transaction_details']->total_sales)</td>
                </tr>
                <tr>
                    <th>@lang('report::fields.total_tax')</th>
                    <td>@format_currency($details['transaction_details']->total_tax)</td>
                </tr>
                <tr>
                    <th>@lang('report::fields.total_discount')</th>
                    <td>@format_currency($details['transaction_details']->total_discount ?? 0)</td>
                </tr>
            </table>

        </div>

        <div class="card-footer">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fa fa-print"></i> @lang('messages.print')
            </button>
            {{-- <a href="{{ url()->previous() }}" class="btn btn-secondary">@lang('messages.cancel')</a> --}}
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .card, .card * { visibility: visible; }
    .card { position: absolute; top: 0; left: 0; width: 100%; }
}
</style>
@endsection
