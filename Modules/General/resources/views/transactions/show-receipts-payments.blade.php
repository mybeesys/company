@extends('layouts.app')


@php
    $title =
        $transaction->transaction->type == 'purchases' ||
        $transaction->transaction->type == 'purchases-order' ||
        $transaction->transaction->type == 'sell-return'
            ? __('menuItemLang.supplier_receipt')
            : __('menuItemLang.customer_receipt');

    $contact =
        $transaction->transaction->type == 'purchases' ||
        $transaction->transaction->type == 'purchases-order' ||
        $transaction->transaction->type == 'sell-return'
            ? __('purchases::general.supplier')
            : __('sales::fields.client');

@endphp
@section('title', $title . '-' . $transaction?->payment_ref_no)
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }

        .fa-folder:before {
            color: #17c653 !important;

        }

        #accounts_tree_container>ul {
            text-align: justify !important;

        }

        .jstree-container-ul .jstree-children {
            text-align: justify !important;
        }

        .jstree-default .jstree-search {
            font-style: oblique !important;
            color: #1b84ff !important;
            font-weight: 700 !important;
        }

        .swal2-popup {
            width: 58em !important;
            /* max-width: 0% !important; */
        }

        .jstree-default .jstree-clicked {
            background: #beebff2e !important;
            border-radius: 8px !important;
            box-shadow: none !important;
        }

        .jstree-default .jstree-anchor .jstree-hovered {
            background: #beebff2e !important;
            border-radius: 8px !important;
            box-shadow: none !important;
        }

        .btn.btn-secondary.show:hover {
            background-color: transparent !important;
        }

        .select-custom {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-color: #f3f4f6;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 10px;
            font-size: 14px;
            color: #333;
        }
    </style>


@stop
{{-- {
"id": 36,
"transaction_id": 111,
"is_return": 0,
"amount": "258.75",
"method": "cash",
"payment_type": "cash",
"paid_on": "2025-07-07",
"created_by": 1,
"note": null,
"payment_ref_no": "SP-2025/0004",
"account_id": 287,
"created_at": "2025-07-07T06:09:28.000000Z",
"updated_at": "2025-07-07T06:09:28.000000Z",
"payment_for": 6,
"payment_method_id": null
} --}}
@section('content')

    <div class="d-flex justify-content-end mb-4">
        <a href="{{ route('show-receipts-payments-export-pdf', $transaction->id) }}" class="btn btn-light-danger">
            <i class="ki-outline ki-file-down fs-4 me-2"></i>
            @lang('general.export_as_pdf')
        </a>
    </div>


    <div class="row">

        <div class="col-sm-4 " style="justify-content: start;display: flex;">
            <div class=" m-7">
                <h1 class="mb-3" style="font-weight: bold;">{{ $title }}<label
                        class="form-label text-muted px-1">{{ $transaction->payment_ref_no }}</label>

                </h1>
                <h2 class="" style="color: #6a6a6a">{{ $company->name }} </h2>
                <p class="fs-7" style="color: #6a6a6a">{{ $company->state }} - {{ $company->city }}</p>
                <p class="fs-7" style="color: #6a6a6a">@lang('menuItemLang.tel'): {{ $company->phone }} </p>

            </div>

        </div>
        <div class="col-sm-4 " style="justify-content: center;display: flex;">
        </div>

        <div class="col-sm-4 " style="justify-content: center;display: flex;">
            <div class=" m-7">

                <img alt="Logo" src="/assets/media/logos/1-14.png" style="justify-content: center;display: flex;"
                    class="h-100px theme-light-show" />
                <p class="fs-7 mb-1" style="color: #6a6a6a">VAT:{{ $company->tax_number }} </p>
                <p class="fs-7 mb-1" style="color: #6a6a6a">@lang('sales::fields.paid_on') {{ $transaction->paid_on }} </p>


            </div>
        </div>


    </div>



    <div class="">
        <div class="row mb-7">
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header py-1" style="background: #f9f9f9;    min-height: 40px;">
                        <h3 class="card-title fw-bold">{{ $contact }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">@lang('sales::fields.name')</label>
                                <p class="fs-6 fw-semibold">{{ $transaction->client->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">@lang('general::lang.Address')</label>
                                <p class="fs-6 fw-semibold">
                                    @if (!isset($transaction->client))
                                        {{ $transaction?->client?->billingAddress?->city . ' - ' . $transaction?->client?->billingAddress?->street_name }}
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">@lang('clientsandsuppliers::fields.email')</label>
                                <p class="fs-6 fw-semibold">{{ $transaction->client->email ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">@lang('clientsandsuppliers::fields.mobile_number')</label>
                                <p class="fs-6 fw-semibold">{{ $transaction->client->mobile_number ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header py-1" style="background: #f9f9f9; min-height: 40px;">
                        <h3 class="card-title fw-bold">@lang('general::lang.invoice_info')</h3>
                    </div>
                    <div class="card-body py-0">
                        <div class="row mt-1">
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted">@lang('sales::fields.ref_no')</label>
                                <p class="fs-6 fw-semibold">{{ $transaction->transaction->ref_no ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted">@lang('report::fields.transaction_date')</label>
                                <p class="fs-6 fw-semibold">{{ $transaction->transaction->transaction_date ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted">@lang('sales::lang.total_before_vat')</label>
                                <p class="fs-6 fw-semibold">{{ $transaction->transaction->total_before_tax ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="row">

                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted">@lang('general::fields.tax_amount')</label>
                                <p class="fs-6 fw-semibold">{{ $transaction->transaction->tax_amount ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted">@lang('general::lang.discount_type')</label>
                                <p class="fs-6 fw-semibold">{{ $transaction->transaction->discount_type ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted">@lang('report::fields.discount_amount')</label>
                                <p class="fs-6 fw-semibold">{{ $transaction->transaction->discount_amount ?? 'N/A' }}</p>
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-md-4 mb-0">
                                <label class="form-label text-muted">@lang('employee::fields.gross_total')</label>
                                <p class="fs-6 fw-semibold">{{ $transaction->transaction->final_total ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4 mb-0">
                                <label class="form-label text-muted">@lang('sales::fields.payment_status')</label>
                                <p class="fs-6 fw-semibold">
                                    @lang('general::lang.' . $transaction->transaction->payment_status)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">

                <div class="card">
                    <div class="card-header" style="background: #f9f9f9;    min-height: 40px;">
                        <h3 class="card-title fw-bold">@lang('sales::fields.Line Items')</h3>
                    </div>
                    <div class="card-body p-0">

                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-3">
                                <thead>
                                    <tr class="fw-bold  text-muted bg-light">
                                        <th class="">#</th>
                                        <th class="min-w-280px ">@lang('sales::lang.product')</th>

                                        <th class="min-w-80px">@lang('sales::lang.qty')</th>
                                        <th class="min-w-190px">@lang('sales::lang.unit_price')</th>
                                        <th class="min-w-120px">@lang('sales::lang.discount')</th>
                                        <th class="min-w-125px">@lang('sales::lang.total_before_vat')</th>
                                        <th class="min-w-120px">@lang('sales::lang.vat_percentage')</th>
                                        <th class="min-w-50px">@lang('sales::lang.vat_value')</th>
                                        <th class="min-w-125px">@lang('sales::lang.total_with_tax')</th>
                                        <th class="min-w-25px"></th>
                                    </tr>
                                </thead>
                                <tbody id="table-body">

                                    @php
                                        $lines =
                                            $transaction->transaction->type == 'purchases' ||
                                            $transaction->transaction->type == 'purchases-order' ||
                                            $transaction->transaction->type == 'sell-return'
                                                ? $transaction->transaction->purchases_lines
                                                : $transaction->transaction->sell_lines;
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
                                                        {{ $line->qyt }}  {{ $line?->unitTransfer?->unit1 }}
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
            </div>
        </div>


        <div class="separator d-flex flex-center mb-5">
            <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
        </div>

        <a class="fs-7" href="{{ $company->website }}"
            style="color: #6a6a6a;float: left;">{{ $company->website }}</a>




    @stop

    @section('script')
        @parent

        <script>
            $('#client_id').select2();
        </script>

    @endsection
