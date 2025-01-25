@extends('layouts.app')

@section('title', __('general::lang.bill_payment') . '-' . $transaction->ref_no)
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
                    // 'Client Name' => $transaction?->client?->name,
                    'Final Total' => $transaction->final_total,
                ]),
            ) !!}
        </div>

    </div>





    <div class="separator d-flex flex-center mb-5">
        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>


    <div class="container">
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
                <p @if (!isset($transaction->client?->payment_terms)) class="d-none" @endif>@lang('sales::fields.payment_terms'):
                    {{ __('sales::lang.terms.' . $transaction->client?->payment_terms) ?? '--' }}</p>

            </div>
        </div>
    </div>



    @include('general::transactions.payment-rows')





    @if ($amount != 0)
        <div class="card-header border-0 p-0">
            <h3 class="card-title align-items-start flex-column mb-3">
                <span class="card-label fw-bold fs-3 mb-1 px-3">@lang('general::lang.add_payment') </span>

            </h3>


            <div class="row">
                <div class="col-sm-2">
                    <p>@lang('sales::lang.total_before_vat'): <span class="fs-4"
                            style="color: #17c653">{{ $transaction->final_total }}</span>
                    </p>
                </div>
                <div class="col-sm">
                    <p>@lang('employee::fields.remaining_amount'): <span class="fs-4" style="color: red">{{ $amount }}</span></p>
                </div>

            </div>


            @include('general::transactions.payment')


        </div>
    @endif

    @include('general::transactions.dropdown-toggle')


    <div class="separator d-flex flex-center mb-5">
        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>

    <a class="fs-7" href="{{ $company->website }}" style="color: #6a6a6a;float: left;">{{ $company->website }}</a>




@stop

@section('script')
    @parent

    <script>
        $('#client_id').select2();
    </script>

@endsection
