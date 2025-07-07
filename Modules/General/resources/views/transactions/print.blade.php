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
            text-align: {{ session()->get('locale') == 'ar' ? 'right' : 'left' }};
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
            text-align: {{ session()->get('locale') == 'ar' ? 'right' : 'left' }};
            /* border: 1px solid; */
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', sans-serif;
        }

        .table_component caption {
            caption-side: top;
            text-align: {{ session()->get('locale') == 'ar' ? 'right' : 'left' }};
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
                @if ($transaction->type == 'quotation')
                    <h1 class="mb-3">@lang('sales::lang.quotation')</h1>
                @elseif ($transaction->type == 'purchases-order')
                    <h1 class="mb-3">@lang('purchases::lang.purchase order')</h1>
                @else
                    <h1 class="mb-3">@lang('general::general.Tax invoice')</h1>
                @endif
                <h6>{{ $transaction?->client?->tax_number ?? '' }}</h6>
                <p class="fs-7" style="color: #6a6a6a">{{ $company->state }} - {{ $company->city }}</p>
            </div>
        </div>
        <div class="col-sm-4 " style="justify-content: center;display: flex;"></div>
    </div>

    <div class="separator d-flex flex-center mb-5">
        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>

    <div class="">
        <div class="row">
            <div class="col-sm-4">
                <p>@lang('general::lang.invoice_no'): {{ $transaction->ref_no }}</p>


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
                {{-- <p>@lang('sales::fields.due_date'): {{ $transaction->due_date }}</p> --}}
                @if ($transaction->client)
                    <p @if (!isset($transaction->client->payment_terms)) class="d-none" @endif>@lang('sales::fields.payment_terms'):
                        {{ __('sales::lang.terms.' . $transaction->client->payment_terms) ?? '--' }}</p>
                @endif

            </div>
        </div>
    </div>










    <div class="card mb-5 mb-xl-8" @if (app()->getLocale() == 'ar') dir="rtl" @endif>

        <div class="card-header border-0 p-0">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold fs-3 mb-1 px-3">@lang('sales::fields.Line Items')</span>

            </h3>

        </div>

        {{-- Products --}}
        <div class="card-body p-0">
            <div class="table-responsive">

                <table class="table align-middle gs-0 gy-4 text-center" id="salesTable">
                    <thead>
                        <tr class="fw-bold  text-muted bg-light">
                            <th class="">#</th>
                            <th class="min-w-280px ">@lang('sales::lang.product')</th>

                            <th class="min-w-80px">@lang('sales::lang.qty')</th>
                            <th class="min-w-190px">@lang('sales::lang.unit_price')</th>
                            <th class="min-w-80px">@lang('sales::lang.discount')</th>
                            <th class="min-w-80px">@lang('sales::lang.total_before_vat')</th>
                            <th class="min-w-80px">@lang('sales::lang.vat_percentage')</th>
                            <th class="min-w-80px">@lang('sales::lang.vat_value')</th>
                            <th class="min-w-80px">@lang('sales::lang.amount')</th>
                        </tr>
                    </thead>
                    <tbody id="table-body">
                        @php
                            $lines =
                                $transaction->type == 'purchases' || $transaction->type == 'purchases-order'
                                    ? $transaction->purchases_lines
                                    : $transaction->sell_lines;
                        @endphp

                        @foreach ($lines as $index => $line)
                            @if ($line->product)
                                <tr>
                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $index + 1 }}
                                        </a>
                                    </td>
                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $line->product->SKU . ' / ' . $line->product->name_ar }}
                                        </a>
                                    </td>
                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $line->qyt }}
                                        </a>
                                    </td>

                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $line->unit_price_before_discount }}
                                        </a>
                                    </td>

                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $line->discount_amount ?? 0 }}
                                        </a>
                                    </td>

                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $line->total_before_vat }}
                                        </a>
                                    </td>

                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $line->tax_id }} %
                                        </a>
                                    </td>

                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $line->tax_value }}
                                        </a>
                                    </td>

                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $line->unit_price_inc_tax }}
                                        </a>
                                    </td>

                                </tr>
                            @endif
                        @endforeach

                    </tbody>


                </table>

            </div>

        </div>

    </div>


    {{-- @if ($transaction->type != 'quotation')
        @include('general::transactions.payment-rows')
    @endif --}}




    <div class=" my-7">
        <div class="row">
            <div class="col-sm-4">
                <p class="fs-5 ">@lang('sales::lang.total_before_vat'): {{ $transaction->total_before_tax }}</p>
                <p class="fs-5 ">@lang('sales::lang.invoice_discount'): (-) {{ $transaction->discount_amount ?? ' 0.00 ' }}</p>
                <p class="fs-5 ">@lang('sales::lang.totalAfterDiscount'): {{ $transaction->totalAfterDiscount }}</p>
                <p class="fs-5 ">@lang('sales::lang.vat_value'): (+) {{ $transaction->tax_amount }}</p>
                <p class="fs-5 ">@lang('sales::lang.amount'): {{ $transaction->final_total }}</p>

            </div>


            <div class="col-sm-4">
                <p class="fs-5 "@if ($transaction->description == null) style="display: none;" @endif>@lang('sales::lang.invoice_note'):
                    {{ $transaction->description ?? ' -- ' }}</p>

            </div>

            <div class="col-sm-4 " style="justify-content: center;display: flex;">
                {!! QrCode::size(120)->generate(
                    json_encode([
                        'Inovice No' => $transaction->ref_no,
                        'Client Name' => $transaction->client?->name,
                        'Final Total' => $transaction->final_total,
                    ]),
                ) !!}
            </div>
        </div>
    </div>

    @if ($transaction->settings_terms_notes)
        @php
            $data = json_decode($transaction->settings_terms_notes ?? '{}', true);
            $locale = app()->getLocale();
            $terms = $locale == 'en' ? $data['terms_en'] ?? null : $data['terms_ar'] ?? null;
            $note = $locale == 'en' ? $data['note_en'] ?? null : $data['note_ar'] ?? null;
        @endphp
        <div id="terms_notes_section">
            @if ($terms)
                <div class="align-items-center">
                    <label class="fs-6 fw-semibold me-3">@lang('general::general.terms_and_conditions'):</label>
                    <label class="fs-5 fw-semibold my-2 me-3">{!! $terms !!}</label>
                </div>
            @endif

            @if ($note)
                <div class="align-items-center mb-2">
                    <label class="fs-6 fw-semibold my-2 me-3">@lang('general::general.note'):</label>
                    <label class="fs-5 fw-semibold me-3">{{ $note }}</label>
                </div>
            @endif
        </div>

    @endif
    <div class="separator d-flex flex-center mb-5">
        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>

    <a class="fs-7" href="{{ $company->website }}" style="color: #6a6a6a;float: left;">{{ $company->website }}</a>

    @include('layouts.js-references')

</body>

</html>
