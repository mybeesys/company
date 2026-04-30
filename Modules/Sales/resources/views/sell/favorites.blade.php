@extends('layouts.app')

@section('title', __('sales::lang.Favorite invoices'))

@section('content')
    <div class="card card-flush">
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="sell_favorites" url="#" module="sales" :addButton="false">
                <x-slot:filters></x-slot:filters>
                <x-slot:export>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#favoritesFilterModal">
                        <i class="bi bi-funnel fs-2"></i>
                    </button>
                    <button type="button" class="btn btn-warning" id="clearFilter">@lang('sales::lang.Remove filter')</button>
                    <x-tables.export-menu id="sell_favorites" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>

        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="sell_favorites" module="sales" />
        </x-cards.card-body>
    </div>

    <div class="modal fade" id="favoritesFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-funnel fs-2 mx-1"></i>@lang('sales::lang.Sales filtering')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="transaction_type" class="form-label">@lang('sales::fields.type')</label>
                            <select class="form-select" id="transaction_type">
                                <option value="">@lang('messages.view_all')</option>
                                <option value="sell">@lang('menuItemLang.invoices')</option>
                                <option value="sell-return">@lang('menuItemLang.sell-return')</option>
                                <option value="quotation">@lang('sales::lang.quotation')</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="customer" class="form-label">@lang('sales::lang.clients')</label>
                            <select class="form-select" id="customer">
                                <option value="">@lang('messages.view_all')</option>
                                @foreach ($clients as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sale_date_range" class="form-label">@lang('sales::fields.transaction_date')</label>
                            <input type="text" class="form-control" id="sale_date_range" readonly
                                placeholder="@lang('sales::fields.transaction_date')">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="due_date_range" class="form-label">@lang('sales::fields.due_date')</label>
                            <input type="text" class="form-control" id="due_date_range" readonly
                                placeholder="@lang('sales::fields.due_date')">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="payment_status" class="form-label">@lang('sales::fields.payment_status')</label>
                            <select class="form-select" id="payment_status">
                                <option value="">@lang('messages.view_all')</option>
                                <option value="paid">@lang('general::lang.paid')</option>
                                <option value="due">@lang('general::lang.due')</option>
                                <option value="partial">@lang('general::lang.partial')</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    @parent
    <script src="{{ url('js/table.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/localeSettings.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/daterangepicker.js') }}"></script>

    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_sell_favorites_table');
        const dataUrl = '{{ route('sales-favorites') }}';
        const currentLang = "{{ app()->getLocale() }}";
        const pickerLocale = (typeof localeSettings !== 'undefined' && localeSettings[currentLang])
            ? localeSettings[currentLang]
            : {
                format: 'YYYY-MM-DD',
                separator: currentLang === 'ar' ? ' إلى ' : ' to ',
                applyLabel: currentLang === 'ar' ? 'تطبيق' : 'Apply',
                cancelLabel: currentLang === 'ar' ? 'إلغاء' : 'Cancel',
                fromLabel: currentLang === 'ar' ? 'من' : 'From',
                toLabel: currentLang === 'ar' ? 'إلى' : 'To',
                daysOfWeek: currentLang === 'ar' ? ['أحد', 'إثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت'] : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                monthNames: currentLang === 'ar'
                    ? ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
                    : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                firstDay: currentLang === 'ar' ? 6 : 0
            };
        const pickerSeparator = pickerLocale.separator || (currentLang === 'ar' ? ' إلى ' : ' to ');
        let dueDateRangeValue = '';
        let saleDateRangeValue = '';

        $(document).ready(function() {
            if (!table.length) return;

            initDatatable();
            exportButtons([0, 1, 2, 3, 4, 5, 6], '#kt_sell_favorites_table');
            handleSearchDatatable();

            $('#sale_date_range').daterangepicker({
                locale: pickerLocale,
                opens: currentLang === 'ar' ? 'right' : 'left',
                autoUpdateInput: false
            }).on('apply.daterangepicker', function(ev, picker) {
                saleDateRangeValue = picker.startDate.format('YYYY-MM-DD') + pickerSeparator + picker.endDate.format('YYYY-MM-DD');
                $(this).val(saleDateRangeValue);
                dataTable.ajax.reload();
            }).on('cancel.daterangepicker', function() {
                saleDateRangeValue = '';
                $(this).val('');
                dataTable.ajax.reload();
            });

            $('#due_date_range').daterangepicker({
                locale: pickerLocale,
                opens: currentLang === 'ar' ? 'right' : 'left',
                autoUpdateInput: false
            }).on('apply.daterangepicker', function(ev, picker) {
                dueDateRangeValue = picker.startDate.format('YYYY-MM-DD') + pickerSeparator + picker.endDate.format('YYYY-MM-DD');
                $(this).val(dueDateRangeValue);
                dataTable.ajax.reload();
            }).on('cancel.daterangepicker', function() {
                dueDateRangeValue = '';
                $(this).val('');
                dataTable.ajax.reload();
            });

            $('#transaction_type, #customer, #payment_status').on('change', function() {
                dataTable.ajax.reload();
            });

            $('#sale_date_range, #due_date_range').on('focus', function() {
                $(this).trigger('click');
            });

            $('#clearFilter').on('click', function() {
                $('#transaction_type, #customer, #payment_status').val('').trigger('change');
                dueDateRangeValue = '';
                saleDateRangeValue = '';
                $('#due_date_range, #sale_date_range').val('');
                dataTable.ajax.reload();
            });
        });

        function initDatatable() {
            dataTable = $(table).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: dataUrl,
                    data: function(d) {
                        d.transaction_type = $('#transaction_type').val();
                        d.customer = $('#customer').val();
                        d.payment_status = $('#payment_status').val();
                        d.due_date_range = dueDateRangeValue;
                        d.sale_date_range = saleDateRangeValue;
                    }
                },
                info: false,
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'ref_no', name: 'ref_no' },
                    { data: 'client', name: 'client' },
                    { data: 'transaction_date', name: 'transaction_date' },
                    { data: 'due_date', name: 'due_date' },
                    { data: 'payment_status', name: 'payment_status' },
                    { data: 'final_total', name: 'final_total' },
                    { data: 'paid_amount', name: 'paid_amount' },
                    { data: 'remaining_amount', name: 'remaining_amount' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [],
                scrollX: true,
                pageLength: 10,
                drawCallback: function() {
                    KTMenu.createInstances();
                }
            });
        }
    </script>
@endsection

