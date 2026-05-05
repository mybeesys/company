@extends('layouts.app')

@section('title', __('menuItemLang.customer_receipts'))
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }
    </style>


@stop
@section('content')


    {{-- @if (count($transactions) == 0)
        <div class="card1 h-md-100 my-5" dir="ltr">
            <div class="card-body d-flex flex-column flex-center">
                <div class="mb-2 px-20" style="place-items: center;">


                    <div class="py-10 text-center">
                        <img src="/assets/media/illustrations/empty-content.svg" class="theme-light-show w-200px"
                            alt="">
                        <img src="/assets/media/illustrations/empty-content.svg" class="theme-dark-show w-200px"
                            alt="">
                    </div>
                    <h4 class="fw-semibold text-gray-800 text-center  lh-lg">
                        <span class="fw-bolder"> @lang('sales::lang.You do not have any Receipts')</span> <br>
                        @lang('sales::lang.create_suggestion_Receipts')
                    </h4>
                    <a href="{{ route('create-receipts') }}"
                        class="btn btn-primary fv-row flex-md-root my-3 min-w-150px mw-250px">@lang('sales::general.add_receipts')</a>
                </div>

            </div>
        </div>
    @else --}}
    <div class="card card-flush">
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="receipts" url="create-receipts" module="sales" :addButton="false">
                <x-slot:filters>
                </x-slot:filters>
                <x-slot:export>
                    <a class="btn btn-primary fv-row flex-md-root min-w-150px mw-150px receipt-voucher-new-btn" data-bs-toggle="modal"
                        data-bs-target="#receipt-vouchers-Modal">
                        @lang('sales::general.add_receipts')
                    </a>
                    <x-tables.export-menu id="sell" />

                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>

        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="sell" module="accounting" />
        </x-cards.card-body>
    </div>
    {{-- @endif --}}


    @include('accounting::receipt-vouchers.receipt-vouchers-Modal')



@stop

@section('script')
    @parent
     <script src="{{ url('js/table.js') }}"></script>
    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_sell_table');;
        const dataUrl = '{{ route('receipt-vouchers') }}';
        const receiptStoreUrl = '{{ route('receipt-vouchers-store') }}';
        const receiptUpdateBase = '{{ url('receipt-vouchers') }}';
        const receiptFormDataUrl = '{{ route('receipt-vouchers-form-data') }}';
        const receiptDefaultTitle = @json(__('menuItemLang.receipt_vouchers'));
        const receiptEditTitle = @json(__('employee::fields.edit') . ' — ' . __('menuItemLang.receipt_vouchers'));
        const receiptDupTitle = @json(__('accounting::fields.duplication') . ' — ' . __('menuItemLang.receipt_vouchers'));

        function resetReceiptVoucherForm() {
            $('#receipt_vouchers_method').prop('disabled', true);
            $('#receipt-vouchers-form').attr('action', receiptStoreUrl);
            const form = document.getElementById('receipt-vouchers-form');
            if (form) form.reset();
            $('#receipt-vouchers-Modal #from_account, #receipt-vouchers-Modal #cash_account').val(null).trigger('change');
            $('#receipt_vouchers_title_text').text(receiptDefaultTitle);
        }

        function fillReceiptVoucherForm(data) {
            $('#receipt-vouchers-Modal #from_account').val(String(data.from_account)).trigger('change');
            $('#receipt-vouchers-Modal #cash_account').val(String(data.account_id)).trigger('change');
            $('#receipt-vouchers-Modal #transaction_date').val(data.pament_on);
            $('#receipt-vouchers-Modal #paid_amount').val(data.paid_amount);
            $('#receipt-vouchers-Modal #notice').val(data.additionalNotes || '');
        }

        $(document).ready(function() {
            if (!table.length) return;
            initDatatable();
            exportButtons([0, 1, 2, 3, 4, 5, 6], '#kt_sell_table');
            handleSearchDatatable();
            handleFormFiltersDatatable();

            $('#receipt-vouchers-Modal #from_account, #receipt-vouchers-Modal #cash_account').select2({
                dropdownParent: $('#receipt-vouchers-Modal')
            });

            $('.receipt-voucher-new-btn').on('click', function() {
                resetReceiptVoucherForm();
            });

            $(document).on('click', '.receipt-voucher-edit', function(e) {
                e.preventDefault();
                const id = $(this).data('line-id');
                $.getJSON(receiptFormDataUrl, { id: id })
                    .done(function(data) {
                        fillReceiptVoucherForm(data);
                        $('#receipt_vouchers_method').prop('disabled', false);
                        $('#receipt-vouchers-form').attr('action', receiptUpdateBase + '/' + id);
                        $('#receipt_vouchers_title_text').text(receiptEditTitle);
                        $('#receipt-vouchers-Modal').modal('show');
                    })
                    .fail(function(xhr) {
                        const j = xhr.responseJSON || {};
                        const msg = j.message || (j.errors && j.errors.id && j.errors.id[0]) || xhr.statusText;
                        alert(msg);
                    });
            });

            $(document).on('click', '.receipt-voucher-duplicate', function(e) {
                e.preventDefault();
                const id = $(this).data('line-id');
                $.getJSON(receiptFormDataUrl, { id: id })
                    .done(function(data) {
                        resetReceiptVoucherForm();
                        fillReceiptVoucherForm(data);
                        $('#receipt_vouchers_title_text').text(receiptDupTitle);
                        $('#receipt-vouchers-Modal').modal('show');
                    })
                    .fail(function(xhr) {
                        const j = xhr.responseJSON || {};
                        const msg = j.message || (j.errors && j.errors.id && j.errors.id[0]) || xhr.statusText;
                        alert(msg);
                    });
            });

            $('#receipt-vouchers-Modal').on('hidden.bs.modal', function() {
                resetReceiptVoucherForm();
            });
        });




        function initDatatable() {
            dataTable = $(table).DataTable({
                processing: true,
                serverSide: true,
                ajax: dataUrl,

                info: false,

                columns: [{
                        data: 'id',
                        name: 'id',
                    },
                    {
                        data: 'account',
                        name: 'account'
                    },
                    {
                        data: 'type',
                        name: 'type'
                    }, {
                        data: 'operation_date',
                        name: 'operation_date'
                    },

                    {
                        data: 'amount',
                        name: 'amount'
                    },
                    {
                        data: 'created_by',
                        name: 'created_by'
                    },
                    {
                        data: 'note',
                        name: 'note'
                    },

                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }


                ],
                order: [],
                scrollX: true,
                pageLength: 10,
                drawCallback: function() {
                    KTMenu.createInstances();
                }
            });
        };

        function handleFormFiltersDatatable() {
            const filters = $('[data-kt-filter="filter"]');
            const resetButton = $('[data-kt-filter="reset"]');
            const status = $('[data-kt-filter="status"]');
            const deleted = $('[data-kt-filter="deleted_records"]');

            filters.on('click', function(e) {
                const deletedValue = deleted.val();

                dataTable.ajax.url('{{ route('receipt-vouchers') }}?' + $.param({
                    deleted_records: deletedValue
                })).load();

                const statusValue = status.val();
                dataTable.column(6).search(statusValue).draw();
            });

            resetButton.on('click', function(e) {
                status.val(null).trigger('change');
                deleted.val(null).trigger('change');
                dataTable.search('').columns().search('').ajax.url(dataUrl)
                    .load();
            });
        };
    </script>
@endsection
