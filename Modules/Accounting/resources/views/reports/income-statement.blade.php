@extends('layouts.app')
@section('title', __('accounting::lang.income_list'))

@section('css')
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
@stop

@section('content')
    <section class="content-header py-3 ">
        <h2>{{ $company->name }}</h2>

        <h4 class="mb-4">@lang('accounting::lang.income_list')</h4>
    </section>

    <div class="container-fluid" id="income-report">
        <div class="row">
            <div class="col-md-12">
                <form method="GET" class="mb-4 no-print">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="date" name="start_date" class="form-control"
                                value="{{ request('start_date') ?? $start_date }}">
                        </div>
                        <div class="col-md-4">
                            <input type="date" name="end_date" class="form-control"
                                value="{{ request('end_date') ?? $end_date }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">@lang('report::general.filter')</button>
                        </div>
                    </div>
                </form>

                {{-- <div class="mb-3 text-center">
                    <strong>@lang('accounting::lang.date_range'):</strong>
                    {{ \Carbon\Carbon::parse($start_date)->format('Y-m-d') }} -
                    {{ \Carbon\Carbon::parse($end_date)->format('Y-m-d') }}
                </div> --}}

                <table class="table table-bordered">
                    <thead class="text-white" style="background-color: #e4e9f1b7">
                        <tr>
                            <th style="width: 50%">@lang('accounting::lang.Revenues')</th>
                            <th style="width: 50%">@lang('employee::fields.amount')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $total_revenues = 0; @endphp
                        @foreach ($accounts as $account)
                            @if ($account->acc_type == 'income')
                                @php $total_revenues += $account->credit_balance - $account->debit_balance; @endphp
                                <tr>
                                    <td>{{ $account->name_ar }}</td>
                                    <td>@format_currency($total_revenues)</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <td><strong>@lang('accounting::lang.total')</strong></td>
                            <td><strong>@format_currency($total_revenues)</strong></td>
                        </tr>
                    </tfoot>
                </table>

                <table class="table table-bordered">
                    <thead class="text-white" style="background-color: #e4e9f1b7">
                        <tr>
                            <th style="width: 50%">@lang('accounting::lang.account_types.expenses')</th>
                            <th style="width: 50%">@lang('employee::fields.amount')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $total_operating_expenses = 0; @endphp
                        @foreach ($accounts as $account)
                            @if ($account->acc_type == 'expenses')
                                @php $total_operating_expenses += $account->debit_balance - $account->credit_balance; @endphp
                                <tr>
                                    <td>{{ $account->name_ar }}</td>
                                    <td>@format_currency($account->debit_balance - $account->credit_balance)</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <td><strong>@lang('accounting::lang.total')</strong></td>
                            <td><strong>@format_currency($total_operating_expenses)</strong></td>
                        </tr>
                        @php $gross_profit = $total_revenues - $total_operating_expenses; @endphp
                        <tr style="background-color: #e4e9f1b7">
                            <td>
                                <h3>@lang('report::general.gross_profit')</h3>
                            </td>
                            <td>
                                <h3>@format_currency($gross_profit)</h3>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <div class="text-center my-4 no-print">
                    <button class="btn btn-success" onclick="printIncomeReport()">
                        <i class="fa fa-print"></i> @lang('general.print')
                    </button>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function printIncomeReport() {
            let printContent = document.getElementById('income-report').innerHTML;
            let originalContent = document.body.innerHTML;
            document.body.innerHTML = `
            <div style="text-align:center; padding: 10px;">
                <h2>{{ $company->name }}</h2>
                <h4>@lang('accounting::lang.income_list')</h4>
                <p><strong>@lang('accounting::lang.date_range'):</strong> {{ \Carbon\Carbon::parse($start_date)->format('Y-m-d') }} - {{ \Carbon\Carbon::parse($end_date)->format('Y-m-d') }}</p>
            </div>
            ${printContent}
        `;

            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        }
    </script>
@stop
