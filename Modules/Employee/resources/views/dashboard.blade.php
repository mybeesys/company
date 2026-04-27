@extends('layouts.app')
@section('title', __('menuItemLang.dashboard'))

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css" rel="stylesheet" type="text/css">
    <style>
        .summary-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
        }

        .sales-card {
            background-color: #f0f7ff;
            border-left: 4px solid #c3e1ff;
        }

        .purchases-card {
            background-color: #f8f5ff;
            border-left: 4px solid #d1bbff;
        }

        .expenses-card {
            background-color: #fff8f5;
            border-left: 4px solid #ffbbbb;
        }

        .bank-card {
            background-color: #f8f5ff;
            border-left: 4px solid #dbc9ff;
        }

        .receivables-card {
            background-color: #f1faf6;
            border-left: 4px solid #baffea;
        }

        .quick-actions .btn-action {
            padding: 12px 15px;
            margin: 5px;
            border-radius: 8px;
            font-weight: 600;
        }

        .recent-list {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .recent-list .list-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .recent-list .list-item:last-child {
            border-bottom: none;
        }
        .quick-actions {
                gap: 1.5rem;
            }

            .quick-action-btn {
                width: 180px;
                height: 120px;
                border-radius: 12px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                position: relative;
                overflow: hidden;
                transition: all 0.3s ease;
                text-decoration: none;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                color: rgb(0, 0, 0) !important;
            }

            .quick-action-btn:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            }

            .icon-wrapper {
                font-size: 2rem;
                margin-bottom: 12px;
                z-index: 2;
            }

            .btn-label {
                font-weight: 600;
                font-size: 0.95rem;
                text-align: center;
                z-index: 2;
            }

            .hover-effect {
                position: absolute;
                width: 150px;
                height: 150px;
                background: rgba(255, 255, 255, 0.15);
                border-radius: 50%;
                top: -50px;
                right: -50px;
                transition: all 0.5s ease;
                z-index: 1;
            }

            .quick-action-btn:hover .hover-effect {
                transform: scale(3);
            }

            .bg-primary {
                background: linear-gradient(135deg, #decce2, #decce2);
            }

            .bg-success {
                background: linear-gradient(135deg, #c8eddc, #6acb9e);
            }

            .bg-info {
                background: linear-gradient(135deg, #cef3fb, #76c9db);
            }

            .bg-purple {
                background: linear-gradient(135deg, #fff8cc, #e1d277);
            }

            .bg-indigo {
                background: linear-gradient(135deg, #dfd2ff, #dfd2ff);
            }

            .bg-warning {
                background: linear-gradient(135deg, #f7ccdd, #f7ccdd);
            }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4">
        <div class="row mb-8">
            <div class="col-12">
                <div class="d-flex flex-wrap justify-content-center quick-actions">
                    <a href="create-invoice" class="quick-action-btn bg-primary">
                        <div class="icon-wrapper">
                            <i class="fas fa-file-invoice fs-2" style="color: #b000d7"></i>
                        </div>
                        <span class="btn-label">@lang('employee::main.new_sales_invoice')</span>
                        <div class="hover-effect"></div>
                    </a>

                    <a href="create-purchases-invoice" class="quick-action-btn bg-success">
                        <div class="icon-wrapper">
                            <i class="fas fa-file-invoice fs-2" style="color: #00d774"></i>
                        </div>
                        <span class="btn-label">@lang('employee::main.new_purchase_invoice')</span>
                        <div class="hover-effect"></div>
                    </a>

                    <a href="client-create" class="quick-action-btn bg-info">
                        <div class="icon-wrapper">
                            <i class="fas fa-user-plus fs-2" style="color: #00b1d7"></i>
                        </div>
                        <span class="btn-label">@lang('employee::main.new_client')</span>
                        <div class="hover-effect"></div>
                    </a>

                    <a href="supplier-create" class="quick-action-btn bg-purple">
                        <div class="icon-wrapper">
                            <i class="fas fa-truck fs-2" style="color: #d7b900"></i>
                        </div>
                        <span class="btn-label">@lang('employee::main.new_supplier')</span>
                        <div class="hover-effect"></div>
                    </a>

                    <a href="employee" class="quick-action-btn bg-indigo">
                        <div class="icon-wrapper">
                            <i class="fas fa-users fs-2" style="color: #3d00d5"></i>
                        </div>
                        <span class="btn-label">@lang('employee::main.employees')</span>
                        <div class="hover-effect"></div>
                    </a>

                    <a href="journal-entry-create" class="quick-action-btn bg-warning">
                        <div class="icon-wrapper">
                            <i class="fas fa-book fs-2" style="color: #d68da8"></i>
                        </div>
                        <span class="btn-label">@lang('employee::main.new_journal_entry')</span>
                        <div class="hover-effect"></div>
                    </a>
                </div>
            </div>
        </div>


        <div class="row mb-4">
            <div class="col-md-3">

                <div class="summary-card sales-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">@lang('employee::main.daily_sales')</h6>
                            <h3 class="mb-0">{{ $formattedTodaySales }} @get_format_currency()</h3>
                            @if ($yesterdaySales == 0)
                                <small class="text-muted">@lang('employee::main.no_yesterday_data')</small>
                            @else
                                <small class="{{ $dailyChangePercent >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $dailyChangePercent >= 0 ? '+' : '' }}{{ $dailyChangePercent }}% @lang('employee::main.from_yesterday')
                                </small>
                            @endif
                        </div>
                        <i class="fas fa-chart-line fs-1 text-primary"></i>
                    </div>
                    <hr>
                    <div>
                        <h6 class="text-muted mb-2">@lang('employee::main.monthly_sales')</h6>
                        <h4 class="mb-0">{{ $formattedCurrentMonthSales }} @get_format_currency()</h4>
                        <small class="{{ $monthlyChangePercent >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $monthlyChangePercent >= 0 ? '+' : '' }}{{ $monthlyChangePercent }}% @lang('employee::main.from_last_month')
                        </small>
                    </div>
                </div>
            </div>


            <div class="col-md-3">

                <div class="summary-card purchases-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">@lang('employee::main.daily_purchases')</h6>
                            <h3 class="mb-0">{{ $formattedTodayPurchases }} @get_format_currency()</h3>
                            @if ($yesterdayPurchases == 0)
                                <small class="text-muted">@lang('employee::main.no_yesterday_data')</small>
                            @else
                                <small class="{{ $dailyChangePercent_purchases >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $dailyChangePercent_purchases >= 0 ? '+' : '' }}{{ $dailyChangePercent_purchases }}%
                                    @lang('employee::main.from_yesterday')
                                </small>
                            @endif
                        </div>
                        <i class="fas fa-shopping-cart fs-1 text-primary"></i>
                    </div>
                    <hr>
                    <div>
                        <h6 class="text-muted mb-2">@lang('employee::main.monthly_purchases')</h6>
                        <h4 class="mb-0">{{ $formattedCurrentMonthPurchases }} @get_format_currency()</h4>
                        <small class="{{ $monthlyChangePercent_purchases >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $monthlyChangePercent_purchases >= 0 ? '+' : '' }}{{ $monthlyChangePercent_purchases }}%
                            @lang('employee::main.from_last_month')
                        </small>
                    </div>
                </div>
            </div>


            <div class="col-md-3">
                <div class="summary-card expenses-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">@lang('employee::main.Today expenses')</h6>
                            <h3 class="mb-0">{{ $formattedTodayExpenses }} @get_format_currency()</h3>
                            @if ($yesterdayExpenses == 0)
                                <small class="text-muted">@lang('employee::main.no_yesterday_data')</small>
                            @else
                                <small class="{{ $dailyChangePercent >= 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $dailyChangePercent >= 0 ? '+' : '' }}{{ $dailyChangePercent }}%  @lang('employee::main.from_yesterday')
                                </small>
                            @endif
                        </div>
                        <i class="fas fa-money-bill-wave fs-1 text-danger"></i>
                    </div>
                    <hr>
                    <div>
                        <h6 class="text-muted mb-2">@lang('employee::main.Monthly expenses')</h6>
                        <h4 class="mb-0">{{ $formattedCurrentMonthExpenses }} @get_format_currency()</h4>
                            <small class="{{ $monthlyChangePercent >= 0 ? 'text-danger' : 'text-success' }}">
                                {{ $monthlyChangePercent >= 0 ? '+' : '' }}{{ $monthlyChangePercent }}%   @lang('employee::main.from_last_month')
                            </small>
                      </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="summary-card receivables-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">@lang('employee::main.customers_balances_list')</h6>
                            <h3 class="mb-0">{{ $formatted_total_due }} @get_format_currency()</h3>
                            <small class="text-danger">{{ $total_unpaid_invoices }}
                                @lang('employee::main.unpaid')</small>
                        </div>
                        <i class="fas fa-users fs-1 text-success"></i>
                    </div>
                    <hr>
                    <div>
                        <h6 class="text-muted mb-2">@lang('employee::main.supplier_balances')</h6>
                        <h3 class="mb-0">{{ $formatted_total_due_supplier }} @get_format_currency()</h3>
                        <small class="text-danger">{{ $total_unpaid_purchases_invoices }}
                            @lang('employee::main.unpaid')</small>

                    </div>
                </div>
            </div>
        </div>


        <div class="card">
            <div class="card-header">
                <h5 class="card-title">@lang('employee::main.customers_balances_list')</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive p-0">
                    <table class="table table-bordered p-0">
                        <thead>
                            <tr>
                                <th>@lang('employee::main.customer_name')</th>
                                <th>@lang('employee::main.phone')</th>
                                <th class="text-end">@lang('employee::main.total_invoices')</th>
                                <th class="text-end">@lang('employee::main.total_payments')</th>
                                <th class="text-end">@lang('employee::main.balance')</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($customersBalances as $customer)
                                <tr>
                                    <td>{{ $customer->name }}</td>
                                    <td>{{ $customer->phone_number ?? '--' }}</td>
                                    <td class="text-end">{{ number_format($customer->total_invoices) }}
                                        @get_format_currency()
                                    </td>
                                    <td class="text-end">{{ number_format($customer->total_payments) }}
                                        @get_format_currency()
                                    </td>
                                    <td class="text-end font-weight-bold">{{ number_format($customer->balance) }}
                                        @get_format_currency()</td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr class="font-weight-bold">
                                <td colspan="2">@lang('reports.total')</td>
                                <td class="text-end">{{ number_format($customersBalances->sum('total_invoices')) }}
                                    @get_format_currency()</td>
                                <td class="text-end">{{ number_format($customersBalances->sum('total_payments')) }}
                                    @get_format_currency()</td>
                                <td class="text-end">{{ number_format($customersBalances->sum('balance')) }}
                                    @get_format_currency()</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>



        <div class="card mt-8">
            <div class="card-header">
                <h5 class="card-title">@lang('employee::main.supplier_balances_list')</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>@lang('employee::main.customer_name')</th>
                                <th>@lang('employee::main.phone')</th>
                                <th class="text-end">@lang('employee::main.total_invoices')</th>
                                <th class="text-end">@lang('employee::main.total_payments')</th>
                                <th class="text-end">@lang('employee::main.balance')</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($supplierBalances as $supplier)
                                <tr>
                                    <td>{{ $supplier->name }}</td>
                                    <td>{{ $supplier->phone_number ?? '--' }}</td>
                                    <td class="text-end">{{ number_format($supplier->total_invoices) }}
                                        @get_format_currency()
                                    </td>
                                    <td class="text-end">{{ number_format($supplier->total_payments) }}
                                        @get_format_currency()
                                    </td>
                                    <td class="text-end font-weight-bold">{{ number_format($supplier->balance) }}
                                        @get_format_currency()</td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr class="font-weight-bold">
                                <td colspan="2">@lang('reports.total')</td>
                                <td class="text-end">{{ number_format($supplierBalances->sum('total_invoices')) }}
                                    @get_format_currency()</td>
                                <td class="text-end">{{ number_format($supplierBalances->sum('total_payments')) }}
                                    @get_format_currency()</td>
                                <td class="text-end">{{ number_format($supplierBalances->sum('balance')) }}
                                    @get_format_currency()</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card mt-8">
                    <div class="card-header">
                        <h5 class="card-title">@lang('employee::main.Sales vs Expenses - Last 6 Months')</h5>
                    </div>
                    <div class="card-body">
                        <div id="sales-expenses-chart" style="height: 300px;"></div>
                    </div>
                </div>
            </div>

        </div>

    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script>
        var lang = '{{ app()->getLocale() }}';

        var monthLabels = lang === 'ar' ? @json($monthLabelsAr) : @json($monthLabelsEn);

        var salesExpensesChart = new ApexCharts(
            document.querySelector("#sales-expenses-chart"), {
                series: [{
                    name: lang === 'ar' ? 'المبيعات' : 'Sales',
                    data: @json($salesArray)
                }, {
                    name: lang === 'ar' ? 'المصروفات' : 'Expenses',
                    data: @json($expensesArray)
                }],
                chart: {
                    type: 'bar',
                    height: 300,
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'Tajawal, sans-serif'
                },
                colors: ['#3699FF', '#FF5B5B'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        endingShape: 'rounded'
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                xaxis: {
                    categories: monthLabels,
                    labels: {
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: lang === 'ar' ? 'المبلغ (\u20C1)' : 'Amount (\u20C1)',
                        offsetX: lang === 'ar' ? -40 : 0,
                        offsetY: 0

                    },
                    labels: {
                        formatter: val => val.toLocaleString()
                    }
                },
                tooltip: {
                    y: {
                        formatter: val => val.toLocaleString() + ' \u20C1'
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: '14px',
                    markers: {
                        radius: 12
                    }
                }
            }
        );
        salesExpensesChart.render();

    </script>
@endsection
