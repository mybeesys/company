@extends('layouts.app')
@section('title', __('menuItemLang.sales-dashbord'))

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css" rel="stylesheet" type="text/css">

    <style>
        .sales-dashboard {
            /* padding: 20px; */
            background-color: #f8f9fa;
        }

        .summary-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .metric-card {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            height: 100%;
        }

        .metric-card i {
            font-size: 2rem;
            margin-right: 15px;
        }

        .metric-content h3 {
            margin-bottom: 5px;
            font-weight: 700;
        }

        .metric-content p {
            margin-bottom: 5px;
            color: #6c757d;
        }

        .bg-primary-light {
            background-color: #e3f2fd;
        }

        .bg-info-light {
            background-color: #e1f5fe;
        }

        .bg-warning-light {
            background-color: #fff8e1;
        }

        .bg-success-light {
            background-color: #e8f5e9;
        }

        .chart-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            border-top: none;
            font-weight: 600;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }

        .bg-success {
            background-color: #4caf50;
        }

        .bg-warning {
            background-color: #ff9800;
        }

        .bg-primary {
            background-color: #2196f3;
        }

        .bg-danger {
            background-color: #f44336;
        }

        .bg-info {
            background-color: #00bcd4;
        }

        .bg-secondary {
            background-color: #9e9e9e;
        }
    </style>
@endsection

@section('content')
    <div class="">
        <div class="summary-card sales-summary">
            <div class="row">


                <div class="col-md-3">
                    <div class="metric-card bg-primary-light">
                        <i class="fas fa-shopping-bag text-primary px-2"></i>
                        <div class="metric-content">
                            <h3>{{ $formattedTodaySales }} @get_format_currency()</h3>
                            <p>@lang('employee::main.daily_sales')</p>
                            @if ($yesterdaySales == 0)
                                <small class="text-muted">@lang('employee::main.no_yesterday_data')</small>
                            @else
                                <small class="{{ $dailyChangePercent >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $dailyChangePercent >= 0 ? '+' : '' }}{{ $dailyChangePercent }}% @lang('employee::main.from_yesterday')
                                </small>
                            @endif

                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="metric-card bg-info-light">
                        <i class="fas fa-file-invoice text-info px-2"></i>
                        <div class="metric-content">
                            <h3>{{ $stats->total_invoices }} </h3>
                            <p>@lang('sales::lang.total_invoices')</p>
                            <span class="text-active-primary">@lang('sales::lang.this_month')</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="metric-card bg-warning-light">
                        <i class="fas fa-calculator text-warning px-2"></i>
                        <div class="metric-content">
                            <h3>{{ $stats->average_invoice }} @get_format_currency()</h3>
                            <p>@lang('sales::lang.average_invoice')</p>
                            <span class="text-active-primary">@lang('sales::lang.this_month')</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="metric-card bg-success-light">
                        <i class="fas fa-users text-success px-2"></i>
                        <div class="metric-content">
                            <h3>{{ $stats->active_customers }}</h3>
                            <p>@lang('sales::lang.active_customers')</p>
                            <span class="text-active-primary">@lang('sales::lang.this_month')</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="chart-card">
                    <div class="chart-header">
                        <h4>@lang('sales::lang.monthly_sales_stats')</h4>
                    </div>
                    <div id="salesTrendChart" style="height: 200px;"></div>
                </div>
            </div>
        </div>

        <div class="recent-transactions mt-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">@lang('sales::lang.recent_sales_transactions')</h4>
                    <div class="card-actions">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>#</th>
                                    <th>@lang('sales::lang.client')</th>
                                    <th>@lang('report::fields.transaction_date')</th>
                                    <th>@lang('report::fields.type')</th>
                                    <th>@lang('sales::fields.payment_status')</th>
                                    <th>@lang('sales::fields.invoice_amount')</th>
                                    <th>@lang('sales::fields.piad_amount')</th>
                                    <th>@lang('sales::fields.remaining_amount')</th>

                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    use Modules\General\Utils\TransactionUtils;
                                @endphp
                                @foreach ($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->ref_no }}</td>
                                        <td>{{ $transaction->client->name ?? 'N/A' }}</td>
                                        <td>{{ $transaction->transaction_date ?? 'N/A' }}</td>
                                        <td><span
                                                class="badge badge-light-success px-3 py-3 fs-base">@lang('general::lang.' . $transaction->type)</span>
                                        </td>
                                        <td>
                                            @if ($transaction->payment_status == 'paid')
                                                <span class="badge badge-light-info px-3 py-3 fs-base">

                                                    @lang('general::lang.paid') </span>
                                            @elseif ($transaction->payment_status == 'due')
                                                <span class="badge badge-light-danger px-3 py-3 fs-base">

                                                    @lang('general::lang.due') </span>
                                            @elseif ($transaction->payment_status == 'partial')
                                                <span class="badge badge-light-success px-3 py-3 fs-base">

                                                    @lang('general::lang.partial') </span>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $transaction->total_before_tax ?? '0.00' }} @get_format_currency()
                                        </td>
                                        <td>
                                            @php
                                                $transactionUtil = new TransactionUtils();

                                                $paid_amount = $transactionUtil->getTotalPaid($transaction->id);
                                                $amount = $transaction->final_total - $paid_amount;
                                                if ($amount < 0) {
                                                    $amount = 0;
                                                }
                                                $amount = number_format($amount, 2);

                                                $paid_amount = number_format($paid_amount, 2);
                                            @endphp
                                            {{ $paid_amount ?? ' 0.00 ' }} @get_format_currency()
                                        </td>

                                        <td>
                                            {{ $amount }} @get_format_currency()
                                        </td>


                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="customer-receipts-dashboard mt-8">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="summary-card bg-primary-light">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-receipt text-primary fs-2 me-3"></i>
                        <div>
                            <h6 class="text-muted mb-1">@lang('sales::lang.total_receipts')</h6>
                            <h3 class="mb-0">{{ $receiptsStats->total_receipts }}</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card bg-success-light">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-money-bill-wave text-success fs-2 me-3"></i>
                        <div>
                            <h6 class="text-muted mb-1">@lang('sales::lang.total_collected')</h6>
                            <h3 class="mb-0">{{ number_format($receiptsStats->total_collected) }} @get_format_currency()</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card bg-warning-light">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-check text-warning fs-2 me-3"></i>
                        <div>
                            <h6 class="text-muted mb-1">@lang('sales::lang.this_month')</h6>
                            <h3 class="mb-0">{{ number_format($receiptsStats->monthly_collected) }} @get_format_currency()
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card bg-danger-light">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle text-danger fs-2 me-3"></i>
                        <div>
                            <h6 class="text-muted mb-1">@lang('sales::lang.overdue')</h6>
                            <h3 class="mb-0">{{ number_format($receiptsStats->overdue_amount) }} @get_format_currency()</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">@lang('sales::lang.recent_receipts')</h4>
                <div class="card-toolbar">
                    <a href="{{ url('/receipts') }}" class="btn btn-sm btn-light-primary">
                        @lang('sales::lang.view_all')
                    </a>
                </div>
            </div>
            <div class="card-body py-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-3">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th>@lang('sales::lang.receipt_no')</th>
                                <th>@lang('sales::lang.client')</th>
                                <th>@lang('sales::lang.invoice')</th>
                                <th>@lang('sales::lang.amount')</th>
                                <th>@lang('sales::lang.date')</th>
                                <th>@lang('sales::lang.method')</th>
                                <th>@lang('sales::lang.actions')</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600">
                            @foreach ($recentReceipts as $receipt)
                                <tr>
                                    <td>
                                        <span class="badge badge-light-info">{{ $receipt->payment_ref_no }}</span>
                                    </td>
                                    <td>{{ $receipt->client->name ?? 'N/A' }}</td>
                                    <td>
                                        <a href="{{ url('/transaction-show/' . $receipt->transaction->id) }}"
                                            class="text-primary">
                                            {{ $receipt->transaction->ref_no }}
                                        </a>
                                    </td>
                                    <td>{{ number_format($receipt->amount) }} @get_format_currency()</td>
                                    <td>{{ $receipt->paid_on ? $receipt->paid_on : 'N/A' }}</td>
                                    <td>
                                        <span
                                            class="badge badge-light-{{ $receipt->method == 'cash' ? 'success' : 'primary' }}">
                                            {{ __('sales::lang.' . $receipt->method) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ url('/show-receipts-payments/' . $receipt->id) }}"
                                            class="btn btn-sm btn-icon btn-light-primary" title="@lang('general.print')">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>



@endsection
@section('script')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script>
        // مخطط اتجاه المبيعات
        var salesTrend = new ApexCharts(document.querySelector('#salesTrendChart'), {
            series: [{
                name: '@lang('sales::lang.sales')',
                data: @json($salesData)
            }],
            chart: {
                type: 'line',
                height: '100%',
                toolbar: {
                    show: false
                }
            },
            colors: ['#2196F3'],
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            markers: {
                size: 5
            },
            xaxis: {
                categories: @json($months)
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toLocaleString() + ' @get_format_currency()';
                    }
                }
            }
        });
        salesTrend.render();
    </script>
@endsection
