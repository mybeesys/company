<!DOCTYPE html>
@php
    $local = session()->get('locale');
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
    $rtl_files = $local == 'ar' ? '.rtl' : '';

@endphp
<html dir="{{ $dir }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('layouts.css-references')

    <title>Print: {{ $transaction->ref_no }}</title>
    <style>
        * {
            font-family: DejaVu Sans !important;
        }

        body {
            font-size: 16px;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', sans-serif;
            padding: 10px;
            margin: 10px;

            color: #777;
        }


        body {
            color: #777;

            text-align: {
                    {
                    session()->get('locale')=='ar' ? 'right': 'left'
                }
            }

            ;
        }



        .table_component {
            overflow: auto;
        }

        .table_component table {
            border: 1px solid #dededf;
            /* height: 99%; */
            table-layout: auto;
            border-collapse: collapse;
            border-spacing: 1px;
            /* text-align: right; */
            page-break-before: avoid;
            page-break-after: avoid;
            direction: ltr;
            width: 100%;

            text-align: {
                    {
                    session()->get('locale')=='ar' ? 'right': 'left'
                }
            }

            ;
            /* border: 1px solid; */
            font-family: 'DejaVu Sans',
            'Roboto',
            'Montserrat',
            'Open Sans',
            sans-serif;
        }

        .table_component caption {
            caption-side: top;

            text-align: {
                    {
                    session()->get('locale')=='ar' ? 'right': 'left'
                }
            }

            ;
        }

        .table_component th {
            border: 1px solid #dededf;
            background-color: #eceff1;
            color: #000000;
            padding: 7px;
            text-align: center;
        }

        .table_component td {
            border: 1px solid #dededf;
            background-color: #ffffff;
            color: #000000;
            padding: 7px;
        }

        td {
            padding: 10px;
            margin: 10px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>

    <script>
        window.onload = function() {
            window.print();
        };

        window.onafterprint = function() {
            @if ($transaction->type == 'purchases')
                window.location.href = "{{ url('purchase-invoices') }}";
            @elseif ($transaction->type == 'sell')
                window.location.href = "{{ url('invoices') }}";
            @elseif ($transaction->type == 'quotation')
                window.location.href = "{{ url('quotations') }}";
            @elseif ($transaction->type == 'purchases-order')
                window.location.href = "{{ url('purchases-order') }}";
            @endif

        };
    </script>
</head>


<body>

    <div class="row">
        <div class="col-sm-4 " style="justify-content: center;display: flex;">
            <img alt="Logo" src="/assets/media/logos/1-14.png" class="h-100px theme-light-show" />
        </div>
        <div class="col-sm-4 " style="justify-content: center;display: flex;">
            <div class="text-center m-7">
                <h1 class="mb-3">@lang('general::lang.bill_payment') {{ $transaction->ref_no }}</h1>
                <p class="fs-7" style="color: #6a6a6a">{{ $company->state }} - {{ $company->city }}</p>

            </div>
        </div>

        <div class="col-sm-4 " style="justify-content: center;display: flex;">
            {!! QrCode::size(90)->generate(
                json_encode([
                    'Inovice No' => $transaction->ref_no,
                    'Client Name' => $transaction->client?->name,
                    'Final Total' => $transaction->final_total,
                ]),
            ) !!}
        </div>

    </div>




    <div class="separator d-flex flex-center mb-5">
        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>


    <div class="">
        <div class="row">
            <div class="col-sm-4">
                <p>@lang('sales::fields.ref_no'): {{ $transaction->ref_no }}</p>

                <p @if (!isset($transaction->payment_status)) class="d-none" @endif>@lang('sales::fields.payment_status'):
                    {{ $transaction->payment_status ? __('general::lang.' . $transaction->payment_status) : '--' }}</p>
                <p>@lang('general::lang.Invoice Status'): @lang('general::lang.' . $transaction->status)</p>
                <p @if (!isset($transaction->notice)) class="d-none" @endif>@lang('sales::fields.notice'):
                    {{ $transaction->notice ?? '--' }}</p>

            </div>
            @if ($transaction->client)
                <div class="col-sm-4">
                    @if ($transaction->type == 'purchases' || $transaction->type == 'purchases-order')
                        <p>@lang('purchases::general.supplier'): {{ $transaction->client->name ?? '--' }}</p>
                    @else
                        <p>@lang('sales::fields.client'): {{ $transaction->client->name ?? '--' }}</p>
                    @endif
                    <p @if (!isset($transaction->client->billingAddress?->city)) class="d-none" @endif>@lang('general::lang.Address'):
                        {{ $transaction->client->billingAddress?->city . ' - ' . $transaction->client->billingAddress?->street_name }}
                    </p>
                    <p @if (!isset($transaction->client->mobile_number)) class="d-none" @endif>@lang('clientsandsuppliers::fields.mobile_number'):
                        {{ $transaction->client->mobile_number ?? '--' }}</p>
                    <p @if (!isset($transaction->client->email)) class="d-none" @endif>@lang('sales::fields.email'):
                        {{ $transaction->client->email ?? '--' }}</p>


                </div>
            @endif

            <div class="col-sm-4">
                <p>@lang('sales::fields.transaction_date'): {{ $transaction->transaction_date }}</p>
                <p>@lang('sales::fields.due_date'): {{ $transaction->due_date }}</p>
                <p>@lang('sales::fields.payment_terms'): {{ __('sales::lang.terms.' . $transaction->client->payment_terms) ?? '--' }}</p>

            </div>
        </div>
    </div>



    @include('general::transactions.payment-rows')

    <div class="separator d-flex flex-center mb-5">
        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>

    <a class="fs-7" href="{{ $company->website }}" style="color: #6a6a6a;float: left;">{{ $company->website }}</a>

    @include('layouts.js-references')

</body>

</html>
