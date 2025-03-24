@extends('layouts.app')
@section('title', __('menuItemLang.Profit-Loss'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('menuItemLang.Profit-Loss')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        {{-- <div class="print_section"><h2>{{session()->get('business.name')}} - @lang( 'report.profit_loss' )</h2></div> --}}


        <hr class="py-1" style="width:100%;text-align:left;">

        @include('report::profit_loss_details')


        <div class="row no-print">
            <div class="col-md-12 my-3">
                <!-- Custom Tabs -->
                <div class="card-toolbar">
                    <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold my-1">
                        <li class="nav-item active">
                            <a class="nav-link justify-content-center text-active-gray-800 active" href="#profit_by_products"
                                data-bs-toggle="tab" aria-expanded="true"> @lang('report::general.profit_by_products')</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link justify-content-center text-active-gray-800 " href="#profit_by_categories"
                                data-bs-toggle="tab" aria-expanded="true"> @lang('report::general.profit_by_categories')</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link justify-content-center text-active-gray-800 " href="#profit_by_locations"
                                data-bs-toggle="tab" aria-expanded="true"> @lang('report::general.profit_by_locations')</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link justify-content-center text-active-gray-800 " href="#profit_by_invoice"
                                data-bs-toggle="tab" aria-expanded="true">@lang('report::general.profit_by_invoice')</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link justify-content-center text-active-gray-800 " href="#profit_by_date"
                                data-bs-toggle="tab" aria-expanded="true"> @lang('report::general.profit_by_date')</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link justify-content-center text-active-gray-800 " href="#profit_by_customer"
                                data-bs-toggle="tab" aria-expanded="true"> @lang('report::general.profit_by_customer')</a>
                        </li>


                        <li class="nav-item">
                            <a class="nav-link justify-content-center text-active-gray-800 " href="#profit_by_day"
                                data-bs-toggle="tab" aria-expanded="true"> @lang('report::general.profit_by_day')</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane active" id="profit_by_products">
                            @include('report::profit_by_products')
                        </div>

                        <div class="tab-pane" id="profit_by_categories">
                            @include('report::profit_by_categories')
                        </div>


                        <div class="tab-pane" id="profit_by_locations">
                            @include('report::profit_by_locations')
                        </div>

                        <div class="tab-pane" id="profit_by_invoice">
                            @include('report::profit_by_invoice')
                        </div>

                        <div class="tab-pane" id="profit_by_date">
                            @include('report::profit_by_date')
                        </div>

                        <div class="tab-pane" id="profit_by_customer">
                            @include('report::profit_by_customer')
                        </div>

                        <div class="tab-pane" id="profit_by_day">

                        </div>
                    </div>
                </div>
            </div>
        </div>


    </section>
    <!-- /.content -->
@stop
@section('script')
    {{-- <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script> --}}
    {{-- <script src="{{ url('/modules/Sales/js/report.js') }}"></script> --}}

    <script type="text/javascript">
        $(document).ready(function() {
            profit_by_products_table = $('#profit_by_products_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/reports/get-profit/product",
                },
                columns: [{
                        data: 'product',
                        name: 'product',
                        searchable: false

                    },
                    {
                        data: 'gross_profit',
                        name: 'gross_profit',
                        searchable: false
                    },
                ],
                footerCallback: function(row, data, start, end, display) {
                    var total_profit = 0;
                    for (var r in data) {
                        total_profit += data[r].gross_profit ? parseFloat(data[r].gross_profit) : 0;
                    }
                    $('#profit_by_products_table .footer_total').html((total_profit));
                }
            });

            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                var target = $(e.target).attr('href');
                if (target === '#profit_by_categories') {

                    if (typeof profit_by_categories_datatable == 'undefined') {
                        profit_by_categories_datatable = $('#profit_by_categories_table').DataTable({
                            processing: true,
                            serverSide: true,
                            ajax: {
                                url: "/reports/get-profit/category",
                            },
                            columns: [{
                                    data: 'category',
                                    name: 'category' // استخدم نفس الاسم الذي تم إرساله من الاستعلام
                                },
                                {
                                    data: 'gross_profit',
                                    searchable: false
                                }
                            ],
                            order: [], // إلغاء الترتيب الافتراضي من DataTable
                            footerCallback: function(row, data, start, end, display) {
                                var total_profit = 0;
                                for (var r in data) {
                                    total_profit += data[r].gross_profit ? parseFloat(data[r]
                                        .gross_profit) : 0;
                                }

                                $('#profit_by_categories_table .footer_total').html(total_profit
                                    .toFixed(2));
                            }
                        });

                    } else {
                        profit_by_categories_datatable.ajax.reload();
                    }
                } else if (target == '#profit_by_locations') {
                    if (typeof profit_by_locations_datatable == 'undefined') {
                        profit_by_locations_datatable = $('#profit_by_locations_table').DataTable({
                            processing: true,
                            serverSide: true,
                            "ajax": {
                                "url": "/reports/get-profit/location",
                            },
                            columns: [{
                                    data: 'location',
                                    name: 'E.name'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            footerCallback: function(row, data, start, end, display) {
                                var total_profit = 0;
                                for (var r in data) {
                                    total_profit += data[r].gross_profit ? parseFloat(data[r]
                                        .gross_profit) : 0;
                                }

                                $('#profit_by_locations_table .footer_total').html((
                                    total_profit));
                            },
                        });
                    } else {
                        profit_by_locations_datatable.ajax.reload();
                    }
                } else if (target == '#profit_by_invoice') {
                    if (typeof profit_by_invoice_datatable == 'undefined') {
                        profit_by_invoice_datatable = $('#profit_by_invoice_table').DataTable({
                            processing: true,
                            serverSide: true,
                            "ajax": {
                                "url": "/reports/get-profit/invoice",
                                // "data": function(d) {
                                //     d.start_date = $('#profit_loss_date_filter')
                                //         .data('daterangepicker')
                                //         .startDate.format('YYYY-MM-DD');
                                //     d.end_date = $('#profit_loss_date_filter')
                                //         .data('daterangepicker')
                                //         .endDate.format('YYYY-MM-DD');
                                //     d.location_id = $('#profit_loss_location_filter').val();
                                // }
                            },
                            columns: [{
                                    data: 'ref_no',
                                    name: 'sale.ref_no'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            footerCallback: function(row, data, start, end, display) {
                                var total_profit = 0;
                                for (var r in data) {
                                    total_profit += data[r].gross_profit ? parseFloat(data[r]
                                        .gross_profit) : 0;
                                }

                                $('#profit_by_invoice_table .footer_total').html(
                                    (total_profit));
                            },
                        });
                    } else {
                        profit_by_invoice_datatable.ajax.reload();
                    }
                } else if (target == '#profit_by_date') {
                    if (typeof profit_by_date_datatable == 'undefined') {
                        profit_by_date_datatable = $('#profit_by_date_table').DataTable({
                            processing: true,
                            serverSide: true,
                            "ajax": {
                                "url": "/reports/get-profit/date",
                                // "data": function(d) {
                                //     d.start_date = $('#profit_loss_date_filter')
                                //         .data('daterangepicker')
                                //         .startDate.format('YYYY-MM-DD');
                                //     d.end_date = $('#profit_loss_date_filter')
                                //         .data('daterangepicker')
                                //         .endDate.format('YYYY-MM-DD');
                                //     d.location_id = $('#profit_loss_location_filter').val();
                                // }
                            },
                            columns: [{
                                    data: 'transaction_date',
                                    name: 'sale.transaction_date'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            footerCallback: function(row, data, start, end, display) {
                                var total_profit = 0;
                                for (var r in data) {
                                    total_profit += data[r].gross_profit ? parseFloat(data[r]
                                        .gross_profit) : 0;
                                }

                                $('#profit_by_date_table .footer_total').html(
                                    (total_profit));
                            },
                        });
                    } else {
                        profit_by_date_datatable.ajax.reload();
                    }
                } else if (target == '#profit_by_customer') {
                    if (typeof profit_by_customers_table == 'undefined') {
                        profit_by_customers_table = $('#profit_by_customer_table').DataTable({
                            processing: true,
                            serverSide: true,
                            "ajax": {
                                "url": "/reports/get-profit/customer",
                                // "data": function(d) {
                                //     d.start_date = $('#profit_loss_date_filter')
                                //         .data('daterangepicker')
                                //         .startDate.format('YYYY-MM-DD');
                                //     d.end_date = $('#profit_loss_date_filter')
                                //         .data('daterangepicker')
                                //         .endDate.format('YYYY-MM-DD');
                                //     d.location_id = $('#profit_loss_location_filter').val();
                                // }
                            },
                            columns: [{
                                    data: 'customer',
                                    name: 'CU.name'
                                },
                                {
                                    data: 'gross_profit',
                                    "searchable": false
                                },
                            ],
                            footerCallback: function(row, data, start, end, display) {
                                var total_profit = 0;
                                for (var r in data) {
                                    total_profit += data[r].gross_profit ? parseFloat(data[r]
                                        .gross_profit) : 0;
                                }

                                $('#profit_by_customer_table .footer_total').html(
                                    (total_profit));
                            },
                        });
                    } else {
                        profit_by_customers_table.ajax.reload();
                    }
                } else if (target == '#profit_by_day') {
                    // var start_date = $('#profit_loss_date_filter')
                    //     .data('daterangepicker')
                    //     .startDate.format('YYYY-MM-DD');

                    // var end_date = $('#profit_loss_date_filter')
                    //     .data('daterangepicker')
                    //     .endDate.format('YYYY-MM-DD');
                    // var location_id = $('#profit_loss_location_filter').val();

                    var url = '/reports/get-profit/day';
                    $.ajax({
                        url: url,
                        dataType: 'html',
                        success: function(result) {
                            $('#profit_by_day').html(result);
                            profit_by_days_table = $('#profit_by_day_table').DataTable({
                                "searching": false,
                                'paging': false,
                                'ordering': false,
                            });
                            var total_profit = sum_table_col($('#profit_by_day_table'),'gross-profit');
                            $('#profit_by_day_table .footer_total').text(total_profit);
                            $('#profit_by_day_table');
                        },
                    });
                } else if (target == '#profit_by_products') {
                    profit_by_products_table.ajax.reload();
                }
            });
        });
    </script>

@endsection
