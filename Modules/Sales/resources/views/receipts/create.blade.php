@extends('layouts.app')

@section('title', __('sales::general.add_receipts'))
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }

        .custom-width {
            min-width: 60%;
            width: 60%;
        }

        .custom-height {
            height: 35px;
            width: 60%;
        }

        .custom-input {
            height: 35px;
        }

        .custom-header {
            background-color: #f1f1f4 !important;
            min-height: 50px !important;
        }

        .me-3 {
            margin-right: 0 !important;
        }

        .table.gy-4 td {
            padding-left: 2px;
        }

        #discount_type+.select2-container {
            width: max-content !important;
        }

        #unit+.select2-container {
            width: max-content !important;
        }
    </style>


@stop
@section('content')
    <form id="sell_save" method="POST" action="{{ route('store-receipts') }}">
        @csrf

        <div class="container">
            <div class="row py-2">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2  gap-lg-3">
                        <h1> @lang('sales::general.add_receipts')</h1>

                    </div>

                </div>
                <div class="col-6 d-flex" style="justify-content: flex-end">
                    <button type="submit" style="border-radius: 6px;width: 29%;" class="btn btn-bg-primary text-white ">
                        @lang('messages.save')
                    </button>
                </div>
            </div>
        </div>
        <div class="separator d-flex flex-center my-5">
            <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
        </div>

        <div class="">
            <div class="row py-3">
                <div class="col-sm">

                    {{-- receipts information --}}
                    @include('sales::receipts.create.receipts-info')

                </div>
                <div class="col-6">

                    @include('sales::receipts.create.client-info')

                </div>

                <div class="separator d-flex flex-center my-6">
                    <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
                </div>


                <div class="d-flex align-items-center">
                    <div class="d-flex align-items-center me-5">
                        <label class="fs-6 fw-semibold mb-2 px-2 me-3" style="width: auto;">
                            @lang('sales::lang.Automatically allocate')
                        </label>
                        <div class="form-check">
                            <input type="radio" style="border: 1px solid #9f9f9f;" id="seniority_invoices"
                                name="allocation_option" checked value="seniority_invoices" class="form-check-input my-2">
                        </div>
                    </div>

                    <div class="d-flex align-items-center">
                        <label class="fs-6 fw-semibold mb-2 px-2 me-3" style="width: auto;">
                            @lang('sales::lang.Automatically allocate payments')
                            <span class=" mt-2 px-1" data-bs-toggle="tooltip" title="@lang('sales::lang.allocate_payments_note')">
                                <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                            </span>
                        </label>
                        <div class="form-check">
                            <input type="radio" style="border: 1px solid #9f9f9f;" id="specified_invoices"
                                name="allocation_option" value="specified_invoices" class="form-check-input my-2">
                        </div>
                    </div>
                </div>


                <div id="transactions_div" class="d-none">
                    <label for="transactions" class="fs-6 fw-semibold px-3 mb-2 required">@lang('sales::fields.select_transactions')</label>
                    <select id="transactions" class="form-select d-flex form-select-solid" style="width: 50% !important"
                        multiple name="transactions[]">
                        <!-- Transactions will be dynamically populated -->
                    </select>
                </div>

            </div>


            <div class="separator d-flex flex-center my-6">
                <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
            </div>


    </form>

    @include('sales::sell.create.add-client')




@stop

@section('script')
    <script src="{{ url('/modules/Sales/js/clients.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/select-2.js') }}"></script>
    <script>
        $('#addNewAccountBtn').on('click', function() {
            $('#addClientModal').modal('show');
        });


        $(document).ready(function() {


            $("#dev-mobile_number").hide();
            $("#dev-billing_address").hide();
            $("#dev-email").hide();
            $("#dev-tax_number").hide();
            // Hide transactions select by default
            const $transactionsDiv = $('#transactions_div');
            const $transactionsSelect = $('#transactions');
            const $clientSelect = $('#client_id');


            const translations = {
                invoice_amount: "@lang('sales::fields.invoice_amount')",
                remaining_amount: "@lang('sales::fields.remaining_amount')",
                date: "@lang('accounting::lang.operation_date')",
                transaction_types: {
                    sell: "@lang('accounting::lang.sell')",
                    purchase: "@lang('accounting::lang.purchase')",
                }
            };
            $('#specified_invoices').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#transactions').attr('required', 'required');

                    $transactionsDiv.removeClass('d-none');
                }
            });

            $('#seniority_invoices').on('change', function() {
                if ($(this).is(':checked')) {
                    $transactionsDiv.addClass('d-none');
                    $('#transactions').removeAttr('required');

                    $transactionsSelect.empty();
                }
            });

            $clientSelect.on('change', function() {

                let clientId = $(this).val();
                getTransaction(clientId);
            });

            function getTransaction(clientId) {
                if (clientId && $('#specified_invoices').is(':checked')) {
                    $.ajax({
                        url: `/get-transactions/${clientId}`,
                        method: 'GET',
                        success: function(data) {
                            $transactionsSelect.empty();
                            $.each(data, function(index, transaction) {
                                $transactionsSelect.append(
                                    $('<option>', {
                                        value: transaction.id,
                                        text: `${transaction.id} - (${translations.transaction_types[transaction.type]}) / ${transaction.ref_no} - ${translations.invoice_amount} (${transaction.final_total}), ${translations.remaining_amount} (${transaction.remaining_amount}) -  ${translations.date} (${transaction.transaction_date})`
                                    })
                                );
                            });
                        },
                        error: function(error) {
                            console.error('Error fetching transactions:', error);
                        }
                    });
                }
            }
        });
    </script>
@stop
