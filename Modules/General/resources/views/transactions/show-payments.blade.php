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

    <div class="text-center m-7">
        <h1 class="mb-3">@lang('general::lang.bill_payment') {{ $transaction->ref_no }}</h1>
    </div>

    <div class="separator d-flex flex-center mb-5">
        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>


    <div class="container">
        <div class="row">
            <div class="col-sm-4">
                <p>@lang('sales::fields.ref_no'): {{ $transaction->ref_no }}</p>
                <p>@lang('sales::fields.payment_status'): @lang('general::lang.' . $transaction->payment_status)</p>
                <p>@lang('general::lang.Invoice Status'): @lang('general::lang.' . $transaction->status)</p>



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



@include('general::transactions.payment-rows')


    <div class="separator d-flex flex-center m-7 p-4">
        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>




    <div class="card-header border-0 p-0">
        <h3 class="card-title align-items-start flex-column mb-3">
            <span class="card-label fw-bold fs-3 mb-1 px-3">@lang('general::lang.add_payment') </span>

        </h3>


            <div class="row">
              <div class="col-sm-2">
                <p>@lang('sales::lang.total_before_vat'):  <span class="fs-4" style="color: #17c653">{{ $transaction->final_total  }}</span></p>
            </div>
              <div class="col-sm">
                <p>@lang('employee::fields.remaining_amount'):  <span class="fs-4" style="color: red">{{$amount}}</span></p>
            </div>

            </div>

        @include('general::transactions.payment')

    </div>






@stop

@section('script')
    @parent

    <script>
        $('#client_id').select2();
    </script>

@endsection
