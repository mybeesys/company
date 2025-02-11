@extends('report::layouts.master')

@section('title', __('menuItemLang.sales_report'))
@section('content')
    <x-cards.card class="p-5">
        <div class="d-flex flex-column gap-5">
            <div class="d-flex flex-wrap gap-4 w-md-50">
                <x-form.input-div>
                    <x-form.input class="form-control-solid" :label="__('employee::general.period')" name="periodPicker" />
                </x-form.input-div>

                <div class="d-flex justify-content-end mt-7" data-kt-user-table-toolbar="base">
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                        data-kt-menu-placement="{{ session('locale') == 'ar' ? 'bottom-start' : 'bottom-end' }}">
                        <i class="ki-outline ki-filter fs-2"></i>@lang('general.filters')
                    </button>

                    <div class="menu menu-sub menu-sub-dropdown min-w-300px min-w-md-700px" data-kt-menu="true">

                        <div class="d-flex">
                            <div class="border border-left w-100">
                                <div class="px-7 py-5">
                                    <div class="fs-5 text-gray-900 fw-bold">@lang('general.filters')</div>
                                </div>
                                <div class="separator border-gray-200"></div>
                                <div class="px-7 py-5" data-kt-filter="form">
                                    <x-form.input-div class="mb-5">
                                        <x-form.select :label="__('employee::fields.establishment')" name="establishment" data_allow_clear="false" :options="[]"
                                            value="" />
                                    </x-form.input-div>
                                </div>
                            </div> 
                            <div class="border border-left w-100">
                                <div class="px-7 py-5">
                                    <div class="fs-5 text-gray-900 fw-bold">@lang('general.filters')</div>
                                </div>
                                <div class="separator border-gray-200"></div>
                                <div class="px-7 py-5" data-kt-filter="form">
                                    
                                </div>
                            </div>  
                        </div>
                        <div class="d-flex justify-content-end gap-4 py-5 px-10">
                            <button type="reset"
                                class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                data-kt-menu-dismiss="true" data-kt-filter="reset">@lang('general.reset')</button>
                            <button type="submit" class="btn btn-primary fw-semibold px-6"
                                data-kt-menu-dismiss="true" data-kt-filter="filter">@lang('general.apply')</button>
                        </div>
                    </div>
                </div>

            </div>
            <div>
                <canvas id="kt_chartjs_2" class="mh-300px"></canvas>
            </div>
            <div class="d-flex gap-4">
                <x-form.form-card class="w-100" :title="__('report::general.sales_summary_report')">
                    <div class="accordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="net_sales_header">
                                <button class="accordion-button fs-4 fw-semibold collapsed py-4" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#net_sales_body" aria-expanded="false"
                                    aria-controls="net_sales_body">
                                    @lang('report::general.net_sales'): 0.00
                                </button>
                            </h2>
                            <div id="net_sales_body" class="accordion-collapse collapse" aria-labelledby="net_sales_header"
                                data-bs-parent="#net_sales">
                                <div class="accordion-body p-0">

                                    <div class="accordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="gross_sales_header">
                                                <button class="accordion-button fs-5 fw-semibold collapsed py-4"
                                                    type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#gross_sales_body" aria-expanded="false"
                                                    aria-controls="gross_sales_body">
                                                    @lang('report::general.gross_sales'): 0.00
                                                </button>
                                            </h2>
                                            <div id="gross_sales_body" class="accordion-collapse collapse"
                                                aria-labelledby="gross_sales_header" data-bs-parent="#gross_sales">
                                                <div class="accordion-body p-0">

                                                    <div class="accordion">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="total_products_sales_header">
                                                                <button
                                                                    class="accordion-button fs-6 fw-semibold collapsed py-4"
                                                                    type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#total_products_sales_body"
                                                                    aria-expanded="false"
                                                                    aria-controls="total_products_sales_body">
                                                                    @lang('report::general.total_products_sales'): 0.00
                                                                </button>
                                                            </h2>
                                                            <div id="total_products_sales_body"
                                                                class="accordion-collapse collapse"
                                                                aria-labelledby="total_products_sales_header"
                                                                data-bs-parent="#total_products_sales">
                                                                <div class="accordion-body p-0">
                                                                    <div class="border-bottom px-6 py-4">
                                                                        <span>@lang('report::general.taxable_sales'): 0.00</span>
                                                                    </div>
                                                                    <div class=" px-6 py-4">
                                                                        <span>@lang('report::general.non_taxable_sales'): 0.00</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="accordion">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="total_service_fees_header">
                                                                <button
                                                                    class="accordion-button fs-6 fw-semibold collapsed py-4"
                                                                    type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#total_service_fees_body"
                                                                    aria-expanded="false"
                                                                    aria-controls="total_service_fees_body">
                                                                    @lang('report::general.total_service_fees'): 0.00
                                                                </button>
                                                            </h2>
                                                            <div id="total_service_fees_body"
                                                                class="accordion-collapse collapse"
                                                                aria-labelledby="total_service_fees_header"
                                                                data-bs-parent="#total_service_fees">
                                                                <div class="accordion-body p-0">
                                                                    <div class="border-bottom px-6 py-4">
                                                                        <span>@lang('report::general.taxable_service_fees'): 0.00</span>
                                                                    </div>
                                                                    <div class=" px-6 py-4">
                                                                        <span>@lang('report::general.non_taxable_service_fees'): 0.00</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>


                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="total_discounts_header">
                                                <button class="accordion-button fs-5 fw-semibold collapsed py-4"
                                                    type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#total_discounts_body" aria-expanded="false"
                                                    aria-controls="total_discounts_body">
                                                    @lang('report::general.total_discounts'): 0.00
                                                </button>
                                            </h2>
                                            <div id="total_discounts_body" class="accordion-collapse collapse"
                                                aria-labelledby="total_discounts_header"
                                                data-bs-parent="#total_discounts">
                                                <div class="accordion-body p-0">
                                                    <div class="border-bottom px-6 py-4">
                                                        <span>@lang('report::general.items_discounts'): 0.00</span>
                                                    </div>
                                                    <div class=" px-6 py-4">
                                                        <span>@lang('report::general.orders_discounts'): 0.00</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="total_tax_and_surcharges">
                                <button class="accordion-button fs-4 fw-semibold collapsed py-4" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#total_tax_and_surcharges_body"
                                    aria-expanded="false" aria-controls="total_tax_and_surcharges_body">
                                    @lang('report::general.total_tax_and_surcharges'): 0.00
                                </button>
                            </h2>
                            <div id="total_tax_and_surcharges_body" class="accordion-collapse collapse"
                                aria-labelledby="total_tax_and_surcharges_header"
                                data-bs-parent="#total_tax_and_surcharges">
                                <div class="accordion-body p-0">
                                    <div class="border-bottom px-6 py-4">
                                        <span class="fs-6 fw-semibold">@lang('report::general.sales_tax'): 0.00</span>
                                    </div>
                                    <div class="border-bottom px-6 py-4">
                                        <span class="fs-6 fw-semibold">@lang('report::general.pass_through_fee_tax'): 0.00</span>
                                    </div>
                                    <div class=" px-6 py-4">
                                        <span class="fs-6 fw-semibold">@lang('report::general.surcharges'): 0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="border border-top-0">
                            <h2 class="fs-4 fw-semibold px-6 py-4 mb-0">@lang('report::general.liabilities'): 0.00</h4>
                        </div>
                        <div class="border border-top-0 bg-secondary rounded-bottom">
                            <h2 class="fs-4 fw-semibold px-6 py-4 mb-0">@lang('report::general.total_payments'): 0.00</h4>
                        </div>
                    </div>
                </x-form.form-card>
                <x-form.form-card class="w-100" :title="__('report::general.analytics')">
                    <div class="accordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="revenue_centers_net_sales_header">
                                <button class="accordion-button fs-4 fw-semibold collapsed py-4" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#revenue_centers_net_sales_body"
                                    aria-expanded="false" aria-controls="revenue_centers_net_sales_body">
                                    @lang('report::general.revenue_centers_net_sales')
                                </button>
                            </h2>
                            <div id="revenue_centers_net_sales_body" class="accordion-collapse collapse"
                                aria-labelledby="revenue_centers_net_sales_header"
                                data-bs-parent="#order_type_break_down">
                                <div class="accordion-body">
                                    ...
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="order_type_break_down_header">
                                <button class="accordion-button fs-4 fw-semibold collapsed py-4" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#order_type_break_down_body"
                                    aria-expanded="false" aria-controls="order_type_break_down_body">
                                    @lang('report::general.order_type_break_down')
                                </button>
                            </h2>
                            <div id="order_type_break_down_body" class="accordion-collapse collapse"
                                aria-labelledby="order_type_break_down_header" data-bs-parent="#order_type_break_down">
                                <div class="accordion-body">
                                    ...
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="total_credit_payments_by_status_header">
                                <button class="accordion-button fs-4 fw-semibold collapsed py-4" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#total_credit_payments_by_status_body"
                                    aria-expanded="false" aria-controls="total_credit_payments_by_status_body">
                                    @lang('report::general.total_credit_payments_by_status')
                                </button>
                            </h2>
                            <div id="total_credit_payments_by_status_body" class="accordion-collapse collapse"
                                aria-labelledby="total_credit_payments_by_status_header"
                                data-bs-parent="#total_credit_payments_by_status">
                                <div class="accordion-body">
                                    ...
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="cash_due_header">
                                <button class="accordion-button fs-4 fw-semibold collapsed py-4" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#cash_due_body" aria-expanded="false"
                                    aria-controls="cash_due_body">
                                    @lang('report::general.cash_due')
                                </button>
                            </h2>
                            <div id="cash_due_body" class="accordion-collapse collapse" aria-labelledby="cash_due_header"
                                data-bs-parent="#cash_due">
                                <div class="accordion-body">
                                    ...
                                </div>
                            </div>
                        </div>
                    </div>
                </x-form.form-card>
            </div>
        </div>
    </x-cards.card>
@endsection

@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="/vfs_fonts.js"></script>
    <script>
        "use strict";
        let dataTable;
        let salesLineChart;
        const table = $('#kt_employee_table');

        $(document).ready(function() {
            function cb(start, end) {
                $("#kt_daterangepicker_4").html(start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY"));
            }

            $("#periodPicker").daterangepicker({
                // timePicker: true,
                startDate: moment().startOf('month'),
                endDate: moment().endOf('month'),
                ranges: {
                    "{{ __('report::general.today') }}": [moment(), moment()],
                    "{{ __('report::general.last_7_days') }}": [moment().subtract(6, "days"), moment()],
                    "{{ __('report::general.this_month') }}": [moment().startOf("month"), moment().endOf(
                        "month")],
                    "{{ __('report::general.last_month') }}": [moment().subtract(1, "month").startOf(
                        "month"), moment().subtract(1,
                        "month").endOf("month")]
                },
                locale: {
                    customRangeLabel: "{{ __('report::general.custom_range') }}"
                }
            }, cb);

            $('#periodPicker').on('apply.daterangepicker', function(ev, picker) {
                getReportData(picker.startDate.format('YYYY-MM-DD HH:mm:ss'),
                    picker.endDate.format('YYYY-MM-DD HH:mm:ss'));
            });

            getReportData(
                moment().startOf('month').format('YYYY-MM-DD HH:mm:ss'),
                moment().endOf('month').format('YYYY-MM-DD HH:mm:ss')
            );
        });

        function getReportData(fromDate, toDate) {
            ajaxRequest("{{ route('sales-reports.get-sales-data') }}", "GET", {
                from: fromDate,
                to: toDate
            }, false, false).done(function(response) {
                updateChart(response.data);
            });
        }

        function updateChart(data) {
            var ctx = document.getElementById('kt_chartjs_2');
            var primaryColor = KTUtil.getCssVariableValue('--kt-primary');

            if (salesLineChart) {
                salesLineChart.destroy();
            }

            const config = {
                type: 'line',
                data: {
                    labels: data.labels, // You'll need to add this to your response
                    datasets: [{
                        label: 'Sales Count',
                        data: data.sales_count, // Use the data from your response
                        borderColor: primaryColor,
                        tension: 0,
                        borderWidth: 3,
                        pointBackgroundColor: primaryColor,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        fill: false
                    }]
                },

                options: {
                    plugins: {
                        title: {
                            display: false
                        },
                        legend: {
                            display: true
                        }
                    },
                    responsive: true,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: {
                                drawBorder: false
                            }
                        }
                    }
                }
            };

            salesLineChart = new Chart(ctx, config);
        }
    </script>
@endsection
