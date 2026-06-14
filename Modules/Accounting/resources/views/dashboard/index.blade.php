@extends(request()->boolean('embed') ? 'layouts.embed' : 'layouts.app')

@section('title', __('accounting::lang.accounting_dashboard'))
@section('css')
    <style>
        .card-p {
            padding: 1rem 2.25rem !important;
        }

        .pe-11 {
            padding-right: 1.75rem !important;
        }
    </style>
    @include('employee::dashboard.partials.tabs-styles')
@stop
@section('content')
    <div class="container-fluid py-3">
        @include('employee::dashboard.partials.tabs-nav')

    @php
        $total_debit = $totals->total_debit;
        $total_credit = $totals->total_credit;

        $months = [];
        $debit_trend = [];
        $credit_trend = [];

        for ($i = 1; $i <= 12; $i++) {
            $monthName = date('M', mktime(0, 0, 0, $i, 1));
            $months[] = __($monthName);

            $monthData = $monthlyData->firstWhere('month', $i);

            $debit_trend[] = $monthData ? number_format($monthData->debit) : 0;
            $credit_trend[] = $monthData ? number_format($monthData->credit) : 0;
        }

    @endphp
    <div class="">
        <div class="row ">
            <div class="col-6 ">
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <h1> @lang('accounting::lang.accounting_dashboard')</h1>

                </div>
            </div>
            <div class="col-6" style="justify-content: end;display: flex;">


            </div>
        </div>
    </div>


    <div class="row mb-4 my-8">
        <div class="col-md-3">
            <div class="summary-card bg-primary-light">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exchange-alt text-primary fs-2 me-3"></i>
                    <div>
                        <h6 class="text-muted mb-1">@lang('accounting::lang.total_transactions')</h6>
                        <h3 class="mb-0">{{ number_format($totals->total_debit + $totals->total_credit) }}
                            @get_format_currency()</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card bg-success-light">
                <div class="d-flex align-items-center">
                    <i class="fas fa-balance-scale text-success fs-2 me-3"></i>
                    <div>
                        <h6 class="text-muted mb-1">@lang('accounting::lang.total_balance')</h6>
                        <h3 class="mb-0">{{ number_format($total_balance) }} @get_format_currency()</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card bg-danger-light">
                <div class="d-flex align-items-center">
                    <i class="fas fa-hand-holding-usd text-danger fs-2 me-3"></i>
                    <div>
                        <h6 class="text-muted mb-1">@lang('accounting::lang.total_debit')</h6>
                        <h3 class="mb-0">{{ number_format($totals->total_debit) }} @get_format_currency()</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card bg-info-light">
                <div class="d-flex align-items-center">
                    <i class="fas fa-money-bill-wave text-info fs-2 me-3"></i>
                    <div>
                        <h6 class="text-muted mb-1">@lang('accounting::lang.total_credit')</h6>
                        <h3 class="mb-0">{{ number_format($totals->total_credit) }} @get_format_currency()</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-12" @if (app()->getLocale() == 'ar') dir="rtl" @endif>


        <div class="card card-xl-stretch mb-xl-8 mt-5">

            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold fs-3 mb-1">@lang('accounting::lang.Overview_tree_Accounts')</span>

                    <span class="text-muted fw-semibold fs-7">@lang('accounting::lang.Overview_tree_Accounts_descrption')</span>
                </h3>

            </div>

            <div class="card-body p-0 d-flex flex-column">

                <div class="card-p pt-0 bg-body flex-grow-1">

                    <div class="d-flex flex-column flex-grow-1 ">

                        <div class="d-flex flex-wrap">

                            @foreach ($account_types as $k => $v)
                                @php
                                    $bal = 0;
                                    foreach ($tree_of_account_overview as $overview) {
                                        if ($overview->account_primary_type == $k && !empty($overview->balance)) {
                                            $bal = (float) $overview->balance;
                                        }
                                    }
                                @endphp


                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">

                                    <div class="d-flex align-items-center">

                                        <div class="fs-2 fw-bold counted" data-kt-countup="true"
                                            data-kt-countup-value="4500" data-kt-countup-prefix="$" data-kt-initialized="1">
                                            {{ $bal }}<span
                                                class="fw-semibold mx-2 text-muted fs-7">@get_format_currency()</span>
                                        </div>
                                    </div>

                                    <div class="fw-semibold fs-6 text-gray-500"> {{ $v['label'] }} @if ($bal < 0)
                                            {{ in_array($v['label'], ['asset', 'expenses']) ? ' (CR)' : ' (DR)' }}
                                        @endif
                                    </div>

                                </div>
                            @endforeach

                        </div>

                    </div>

                </div>

            </div>

        </div>






        <div class="container-fluid accounting-dashboard">


            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">

                        <div class="card-header mt-2">

                            <div class="card-title flex-column">
                                <h3 class="fw-bold ">@lang('accounting::lang.transactions_trend')</h3>
                            </div>

                        </div>

                        <div class="card-body">
                            <div id="transactionsTrendChart" style="height: 300px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-flush h-lg-100">

                        <div class="card-header mt-2">

                            <div class="card-title flex-column">
                                <h3 class="fw-bold ">@lang('accounting::lang.chart_of_accounts')</h3>
                            </div>

                        </div>

                        <div class="card-body p-9 pt-5">

                            <div class="d-flex flex-wrap">

                                <div class="position-relative d-flex flex-center h-175px w-175px me-15 mb-7"
                                    style="margin-left: 0.7rem !important;">
                                    <div
                                        class="position-absolute translate-middle start-50 top-50 d-flex flex-column flex-center">
                                        <span class="fs-2qx fw-bold">{{ $total_blance }}</span>
                                        <span class="fs-6 fw-semibold text-gray-500">@lang('messages.total')</span>
                                    </div>

                                    <canvas id="project_overview_chart" width="175" height="175"
                                        style="display: block; box-sizing: border-box; height: 175px; width: 175px;"></canvas>
                                </div>

                                <div class="d-flex flex-column justify-content-center flex-row-fluid pe-11 mb-5">

                                    @foreach ($account_types as $k => $v)
                                        @php
                                            $bal = 0;
                                            foreach ($tree_of_account_overview as $overview) {
                                                if (
                                                    $overview->account_primary_type == $k &&
                                                    !empty($overview->balance)
                                                ) {
                                                    $bal = (float) $overview->balance;
                                                }
                                            }
                                        @endphp

                                        <div class="d-flex fs-6 fw-semibold align-items-center mb-3">
                                            <div class="bullet bg-primary me-3"
                                                style="background-color: {{ $v['color'] }} !important;"></div>
                                            <div class="text-gray-500">{{ $v['label'] }} @if ($bal < 0)
                                                    {{ in_array($v['label'], ['asset', 'expenses']) ? ' (CR)' : ' (DR)' }}
                                                @endif
                                            </div>
                                            <div class="ms-auto fw-bold text-gray-700">{{ $bal }} <span
                                                    class="fw-semibold mx-2 text-muted fs-7">@get_format_currency()</span></div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">@lang('accounting::lang.recent_transactions')</h4>
                            <div class="card-toolbar">
                                {{-- <a href="#" class="btn btn-sm btn-light-primary">
                                    @lang('accounting::lang.view_all')
                                </a> --}}
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>@lang('accounting::lang.ref_no')</th>
                                            <th>@lang('accounting::lang.account')</th>
                                            <th>@lang('accounting::lang.type')</th>
                                            <th>@lang('accounting::lang.amount')</th>
                                            <th>@lang('accounting::lang.date')</th>
                                            <th>@lang('accounting::lang.cost_center')</th>
                                            {{-- <th>@lang('accounting::lang.actions')</th> --}}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recent_transactions as $transaction)
                                            <tr>
                                                <td>{{ $transaction->refNo ?? $transaction->RefNo }}</td>
                                                <td>{{ $transaction->gl_code }} -
                                                    {{ app()->getLocale() == 'ar' ? $transaction->account_name : $transaction->account_name_en }}
                                                </td>
                                                <td>
                                                    <span {{-- {{ $receipt->method == 'cash' ? 'success' : '' }} --}}
                                                        class="badge badge-light-{{ $transaction->type == 'debit' ? 'danger' : 'success' }}">
                                                        {{ __("accounting::lang.{$transaction->type}") }}
                                                    </span>
                                                </td>
                                                <td>{{ number_format($transaction->amount) }} @get_format_currency()</td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span>{{ \Carbon\Carbon::parse($transaction->operation_date)->format('Y-m-d') }}</span>
                                                        <small
                                                            class="text-muted">{{ \Carbon\Carbon::parse($transaction->operation_date)->format('h:i A') }}</small>
                                                    </div>
                                                </td>
                                                <td>{{ $transaction->cost_center_name?(app()->getLocale() == 'ar' ? $transaction->cost_center_name : $transaction->cost_center_name_en) : 'N/A' }}
                                                </td>
                                                {{-- <td>
                                                    <a href="#" class="btn btn-sm btn-icon btn-light-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td> --}}
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @stop

    @section('script')

        <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
        <script>
            // مخطط اتجاه الحركات
            var transactionsTrend = new ApexCharts(document.querySelector('#transactionsTrendChart'), {
                series: [{
                    name: '@lang('accounting::lang.debit')',
                    data: @json($debit_trend)
                }, {
                    name: '@lang('accounting::lang.credit')',
                    data: @json($credit_trend)
                }],
                chart: {
                    type: 'line',
                    height: '100%',
                    toolbar: {
                        show: false
                    }
                },
                colors: ['#F44336', '#4CAF50'],
                stroke: {
                    width: 3,
                    curve: 'smooth'
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
            transactionsTrend.render();

            // مخطط أنواع الحركات
            var transactionsType = new ApexCharts(document.querySelector('#transactionsTypeChart'), {
                series: @json($transaction_types->pluck('count')),
                labels: @json($transaction_types->pluck('sub_type')),
                chart: {
                    type: 'donut',
                    height: '100%'
                },
                colors: ['#2196F3', '#FFC107', '#9C27B0', '#607D8B'],
                legend: {
                    position: 'bottom'
                }
            });
            transactionsType.render();
        </script>

        <script>
            var ctx = document.getElementById('project_overview_chart').getContext('2d');
            const accountingPrimaryType = @json($account_types);
            const labels = Object.keys(accountingPrimaryType).map(key => accountingPrimaryType[key].label);
            const data = Object.keys(accountingPrimaryType).map(key => accountingPrimaryType[key].GLC);
            const color = Object.keys(accountingPrimaryType).map(key => accountingPrimaryType[key].color);
            const balance = Object.keys(accountingPrimaryType).map(key => accountingPrimaryType[key].balance);
            console.log(balance);

            var projectOverviewChart = new Chart(ctx, {
                type: 'pie', // 'line', 'bar', 'pie', 'doughnut'
                data: {
                    labels: labels,
                    datasets: [{
                        data: balance,
                        backgroundColor: color,
                    }]
                },
                options: {
                    responsive: true,

                    cutout: '80%',
                    plugins: {
                        legend: {
                            position: false,
                        }
                    }
                }
            });
        </script>
    @stop
