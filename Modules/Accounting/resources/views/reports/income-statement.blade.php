@extends('layouts.app')
@section('title', __('accounting::lang.income_list'))

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-center mb-10">@lang('accounting::lang.income_list')</h2>

                {{-- <hr style="width:100%;text-align:left;margin-left:0"> --}}

                {{-- <!-- Date Range Filter -->
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-4">
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">تصفية</button>
                        </div>
                    </div>
                </form> --}}

                <!-- Revenue Section -->
                <table class="table table-bordered">
                    <thead class=" text-white" style="background-color: #a4b0c3">
                        <tr>
                            <th>@lang('accounting::lang.Revenues')</th>
                            <th>@lang('employee::fields.amount')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $total_revenues = 0; @endphp
                        @foreach ($accounts as $account)
                            @if (str_starts_with($account->gl_code, '3'))
                                @php $total_revenues += $account->credit_balance - $account->debit_balance; @endphp
                                <tr>
                                    <td>{{ $account->name_ar }}</td>
                                    <td>@format_currency($account->credit_balance - $account->debit_balance)</td>
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

                <!-- Cost of Goods Sold Section -->
                <table class="table table-bordered">
                    <thead class=" text-white" style="background-color: #a4b0c3">
                        <tr>
                            <th>@lang('accounting::lang.cost of goods sold')</th>
                            <th>@lang('employee::fields.amount')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $total_cogs = 0; @endphp
                        @foreach ($accounts as $account)
                            @if (str_starts_with($account->gl_code, '51'))
                                @php $total_cogs += $account->debit_balance - $account->credit_balance; @endphp
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
                            <td><strong>@format_currency($total_cogs)</strong></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Gross Profit Calculation -->
                @php $gross_profit = $total_revenues - $total_cogs; @endphp
                <div class="alert alert-success text-center">الأرباح الإجمالية: @format_currency($gross_profit)</div>

                <!-- Operating Expenses Section -->
                <table class="table table-bordered">
                    <thead class=" text-white" style="background-color: #a4b0c3">
                        <tr>
                            <th>مصاريف التشغيل</th>
                            <th>@lang('employee::fields.amount')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $total_operating_expenses = 0; @endphp
                        @foreach ($accounts as $account)
                            @if (str_starts_with($account->gl_code, '21'))
                                @php $total_operating_expenses += $account->debit_balance - $account->credit_balance; @endphp
                                <tr>
                                    <td>{{ $account->name }}</td>
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
                    </tfoot>
                </table>

                <!-- Operating Income Calculation -->
                @php $income_from_operation = $gross_profit - $total_operating_expenses; @endphp
                <div class="alert alert-success text-center">الدخل من التشغيل: @format_currency($income_from_operation)</div>

            </div>
        </div>
    </div>
@endsection
