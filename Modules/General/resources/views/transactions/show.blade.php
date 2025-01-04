@extends('layouts.app')

@section('title', $transaction->ref_no)
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

@section('content')

    <div class="text-center m-7">
        @if ($transaction->type == 'quotation')
            <h1 class="mb-3">@lang('sales::lang.quotation')</h1>
        @else
            <h1 class="mb-3">@lang('general::general.Tax invoice')</h1>
        @endif
        <h6>{{ $transaction?->client?->tax_number ?? '' }}</h6>
    </div>

    <div class="separator d-flex flex-center mb-5">
        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>


    <div class="container">
        <div class="row">
            <div class="col-sm-4">
                <p>@lang('sales::fields.ref_no'): {{ $transaction->ref_no }}</p>
                <p>@lang('sales::fields.payment_status'):
                    {{ $transaction->payment_status ? __('general::lang.' . $transaction->payment_status) : '--' }}</p>
                <p>@lang('general::lang.Invoice Status'): @lang('general::lang.' . $transaction->status)</p>
                <p>@lang('sales::fields.notice'): {{ $transaction->notice ?? '--' }}</p>

            </div>
            <div class="col-sm-4">
                <p>@lang('sales::fields.client'): {{ $transaction->client->name ?? '--' }}</p>
                <p>@lang('clientsandsuppliers::fields.Billing Address'):
                    {{ $transaction->client->billingAddress?->city . ' - ' . $transaction->client->billingAddress?->street_name }}
                </p>
                <p>@lang('clientsandsuppliers::fields.mobile_number'): {{ $transaction->client->mobile_number ?? '--' }}</p>
                <p>@lang('sales::fields.email'): {{ $transaction->client->email ?? '--' }}</p>


            </div>
            <div class="col-sm-4">
                <p>@lang('sales::fields.transaction_date'): {{ $transaction->transaction_date }}</p>
                <p>@lang('sales::fields.due_date'): {{ $transaction->due_date }}</p>
                <p>@lang('sales::fields.payment_terms'): {{ __('sales::lang.terms.' . $transaction->client->payment_terms) ?? '--' }}</p>

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
                            <th class="min-w-150px product-description" style="display:none">@lang('sales::lang.description')
                            </th>
                            <th class="min-w-80px">@lang('sales::lang.qty')</th>
                            <th class="min-w-190px">@lang('sales::lang.unit_price')</th>
                            <th class="min-w-200px">@lang('sales::lang.discount')</th>
                            <th class="min-w-125px">@lang('sales::lang.total_before_vat')</th>
                            <th class="min-w-200px">@lang('sales::lang.vat_percentage')</th>
                            <th class="min-w-50px">@lang('sales::lang.vat_value')</th>
                            <th class="min-w-125px">@lang('sales::lang.amount')</th>
                            <th class="min-w-25px"></th>
                        </tr>
                    </thead>
                    <tbody id="table-body">
                        @foreach ($transaction->sell_lines as $index => $line)
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
                                        {{ $line->tax_id }}
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
                        @endforeach

                    </tbody>


                </table>

            </div>

        </div>

    </div>


    @if ($transaction->type != 'quotation')
        @include('general::transactions.payment-rows')
    @endif




    <div class="container my-7">
        <div class="row">
            <div class="col-sm-4">
                <p class="fs-5 ">@lang('sales::lang.total_before_vat'): {{ $transaction->total_before_tax }}</p>
                <p class="fs-5 ">@lang('sales::lang.invoice_discount'): (-) {{ $transaction->discount_amount ?? ' 0.00 ' }}</p>
                <p class="fs-5 ">@lang('sales::lang.totalAfterDiscount'): {{ $transaction->totalAfterDiscount }}</p>
                <p class="fs-5 ">@lang('sales::lang.vat_value'): (+) {{ $transaction->tax_amount }}</p>
                <p class="fs-5 ">@lang('sales::lang.amount'): {{ $transaction->final_total }}</p>

            </div>


            <div class="col-sm-8">
                <p class="fs-5 ">@lang('sales::lang.invoice_note'): {{ $transaction->description ?? ' -- ' }}</p>

            </div>
        </div>
    </div>





@stop

@section('script')
    @parent


@endsection
