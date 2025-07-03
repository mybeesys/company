@extends('layouts.app')

@section('title', __('accounting::lang.cash_flow_statement'))

@section('content')
<div class="container">
    <h2 class="mb-4">{{ __('accounting::lang.cash_flow_statement') }}</h2>

    <form action="{{ route('cash-flow') }}" method="GET">
        <div class="row">
            <div class="col-md-4">
                <label>{{ __('accounting::lang.from_date') }}</label>
                <input type="date" name="start_date" value="{{ request('start_date', $startDate) }}" class="form-control">
            </div>
            <div class="col-md-4">
                <label>{{ __('accounting::lang.to_date') }}</label>
                <input type="date" name="end_date" value="{{ request('end_date', $endDate) }}" class="form-control">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">{{ __('report::general.filter') }}</button>
            </div>
        </div>
    </form>

    <hr>

    <h4>{{ __('accounting::lang.operating_cash_flows') }}</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('accounting::lang.date') }}</th>
                <th>{{ __('accounting::lang.transaction_type') }}</th>
                <th>{{ __('accounting::lang.amount') }}</th>
                <th>{{ __('accounting::lang.movement_type') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($operatingCashFlows as $key => $flow)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($flow->operation_date)->translatedFormat('Y-m-d h:i:s A') }}</td>
                <td>{{ __('accounting::lang.' . $flow->sub_type) }}</td>
                <td class="text-{{ $flow->type == 'debit' ? 'danger' : 'success' }}">
                    {{ number_format($flow->amount, 2) }}  @get_format_currency()
                </td>
                <td>{{ $flow->type == 'debit' ? __('accounting::lang.debit') : __('accounting::lang.credit') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="d-flex justify-content-center">
        <nav>
            <ul class="pagination">
                {{ $operatingCashFlows->appends(request()->query())->links('pagination::bootstrap-4') }}
            </ul>
        </nav>
    </div>


    <hr>
    <h4>{{ __('accounting::lang.actual_cash_flows') }}</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>{{ __('accounting::lang.cash_inflows') }}</th>
                <th>{{ __('accounting::lang.cash_outflows') }}</th>
                <th>{{ __('accounting::lang.net_cash_flows') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-success">{{ number_format($cashInflows, 2) }}  @get_format_currency()</td>
                <td class="text-danger">{{ number_format($cashOutflows, 2) }}  @get_format_currency()</td>
                <td class="text-{{ $netCashFlow >= 0 ? 'success' : 'danger' }}">
                    {{ number_format($netCashFlow, 2) }} @get_format_currency()
                </td>
            </tr>
        </tbody>
    </table>

    <div class="alert alert-success" role="alert">
        <strong>{{ __('accounting::lang.income') }}</strong> {{ __('accounting::lang.income_description') }}
    </div>

    <div class="alert alert-danger" role="alert">
        <strong>{{ __('accounting::lang.expenses') }}</strong> {{ __('accounting::lang.expenses_description') }}
    </div>

</div>
@endsection
