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

        #transactions_div > span > span.selection > span  {
            border: 1px solid #0095ff !important;
            border-radius: 11px;
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

        @if ($duplicateDefaults ?? null)
            <div class="container mb-5">
                <div class="alert alert-primary py-3 mb-0">
                    @lang('sales::lang.duplicate_receipt_prefill_hint')
                </div>
            </div>
        @endif

        <div class="">
            <div class="row py-3 g-4 align-items-start">
                <div class="col-12 col-lg-6">

                    <input type="hidden" name="payment_type" value="receipts" />
                    {{-- receipts information --}}
                    @include('sales::receipts.create.receipts-info')

                </div>
                <div class="col-12 col-lg-6">

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
                                name="allocation_option" value="auto_allocate" class="form-check-input my-2"
                                @checked(! ($duplicateDefaults ?? null))>
                        </div>
                    </div>

                    <div class="d-flex align-items-center">
                        <label class="fs-6 fw-semibold mb-2 px-2 me-3" style="width: auto;">
                            @lang('sales::lang.Automatically allocate payments')
                            {{-- <span class=" mt-2 px-1" data-bs-toggle="tooltip" title="@lang('sales::lang.allocate_payments_note')">
                                <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                            </span> --}}
                        </label>
                        <div class="form-check">
                            <input type="radio" style="border: 1px solid #9f9f9f;" id="specified_invoices"
                                name="allocation_option" value="specified_invoices" class="form-check-input my-2"
                                @checked(($duplicateDefaults ?? null) !== null)>
                        </div>
                    </div>
                </div>


                <div id="transactions_div" class="{{ ($duplicateDefaults ?? null) ? 'mb-4' : 'd-none' }}">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <label class="fs-6 fw-semibold mb-0 required">@lang('sales::fields.select_transactions')</label>
                        <div class="text-muted fs-7">
                            {{ $supplier ? 'سندات الموردين: فواتير شراء فقط' : 'سندات العملاء: فواتير مبيعات فقط' }}
                        </div>
                    </div>

                    <div class="table-responsive border rounded-2">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-3 mb-0">
                            <thead class="bg-light">
                                <tr class="fw-semibold text-gray-700">
                                    <th style="width:38px;"></th>
                                    <th>@lang('sales::fields.invoice_number')</th>
                                    <th>@lang('accounting::lang.operation_date')</th>
                                    <th class="text-end">@lang('sales::fields.invoice_amount')</th>
                                    <th class="text-end">@lang('sales::fields.remaining_amount')</th>
                                </tr>
                            </thead>
                            <tbody id="transactions_tbody">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-6">...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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


            // Hide transactions select by default
            const $transactionsDiv = $('#transactions_div');
            const $clientSelect = $('#client_id');
            const $transactionsTbody = $('#transactions_tbody');
            const receiptsScope = @json($supplier ? 'supplier' : 'customer');


            const translations = {
                invoice_amount: "@lang('sales::fields.invoice_amount')",
                remaining_amount: "@lang('sales::fields.remaining_amount')",
                date: "@lang('accounting::lang.operation_date')",
                transaction_types: {
                    sell: "@lang('accounting::lang.sell')",
                    purchases: "@lang('accounting::lang.purchase')",
                }
            };
            $('#specified_invoices').on('change', function() {
                if ($(this).is(':checked')) {
                    $transactionsDiv.removeClass('d-none');
                    let clientId = $clientSelect.val();
                    getTransaction(clientId);
                }
            });

            $('#seniority_invoices').on('change', function() {
                if ($(this).is(':checked')) {
                    $transactionsDiv.addClass('d-none');
                    $transactionsTbody.html('<tr><td colspan="5" class="text-center text-muted py-6">...</td></tr>');
                }
            });

            $clientSelect.on('change', function() {

                let clientId = $(this).val();
                getTransaction(clientId);
            });

            function getTransaction(clientId) {
                if (clientId && $('#specified_invoices').is(':checked')) {
                    $.ajax({
                        url: `/get-transactions/${clientId}?scope=${encodeURIComponent(receiptsScope)}`,
                        method: 'GET',
                        success: function(data) {
                            if (!Array.isArray(data) || data.length === 0) {
                                $transactionsTbody.html('<tr><td colspan="5" class="text-center text-muted py-6">{{ __('messages.no_data_found') }}</td></tr>');
                                return;
                            }

                            const rows = data.map(function(t) {
                                const ref = (t.ref_no || ('#' + t.id));
                                const date = (t.transaction_date || '--');
                                const total = (t.final_total || '0.00');
                                const remaining = (t.remaining_amount || '0.00');
                                return `
                                    <tr>
                                        <td class="text-center">
                                            <input class="form-check-input invoice-check" type="checkbox" name="transactions[]" value="${t.id}">
                                        </td>
                                        <td class="fw-semibold text-gray-800">${ref}</td>
                                        <td class="text-gray-700">${date}</td>
                                        <td class="text-end">${total}</td>
                                        <td class="text-end fw-semibold">${remaining}</td>
                                    </tr>
                                `;
                            }).join('');
                            $transactionsTbody.html(rows);
                        },
                        error: function(error) {
                            console.error('Error fetching transactions:', error);
                        }
                    });
                }
            }

            const receiptDuplicateDefaults = @json($duplicateDefaults ?? null);
            if (receiptDuplicateDefaults) {
                setTimeout(function() {
                    var cid = receiptDuplicateDefaults.client_id;
                    if (receiptDuplicateDefaults.account_id) {
                        $('#cash_account').val(String(receiptDuplicateDefaults.account_id)).trigger('change');
                    }
                    if (receiptDuplicateDefaults.cost_center_id) {
                        $('#cost_center').val(String(receiptDuplicateDefaults.cost_center_id)).trigger('change');
                    }
                    if (cid) {
                        $('#client_id').val(String(cid)).trigger('change');
                    }
                    if ($('#specified_invoices').is(':checked') && cid) {
                        getTransaction(cid);
                    }
                }, 500);
            }

            $('#sell_save').on('submit', function(e) {
                if ($('#specified_invoices').is(':checked')) {
                    const anyChecked = $('.invoice-check:checked').length > 0;
                    if (!anyChecked) {
                        e.preventDefault();
                        alert(@json(__('messages.required_fields_warning')));
                    }
                }
            });
        });
    </script>
@stop
