@extends('layouts.app')
@section('title', __('accounting::lang.journal_report'))

@section('content')
    <div class="container mt-4">
        <h2>{{ __('accounting::lang.journal_report') }}</h2>

        <form method="GET" action="{{ route('journal-report') }}" class="my-6">
            <div class="row">
                <div class="col-md-4">
                    <label>{{ __('accounting::lang.from_date') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-4">
                    <label>{{ __('accounting::lang.to_date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary mt-6">{{ __('accounting::lang.search') }}</button>
                </div>

            </div>
          </form>

        @if (isset($journals) && $journals->isNotEmpty())
            @foreach ($journals as $journal)
                <div class="card mb-4">
                    <div class="card-body">
                        <h5>{{ __('accounting::lang.ref_no') }}: {{ $journal->ref_no }}</h5>
                        <p>{{ __('accounting::lang.operation_date') }}: {{ $journal->operation_date }}</p>
                        <p>{{ __('accounting::lang.note') }}: {{ $journal->note }}</p>

                        <table class="table table-bordered mt-3">
                            <thead>
                                <tr>
                                    <th>{{ __('accounting::lang.account_name') }}</th>
                                    <th>{{ __('accounting::lang.gl_code') }}</th>
                                    <th>{{ __('accounting::lang.debit') }}</th>
                                    <th>{{ __('accounting::lang.credit') }}</th>
                                    <th>{{ __('accounting::lang.note') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($journal->transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->name_ar }} ({{ $transaction->name_en }})</td>
                                        <td>{{ $transaction->gl_code }}</td>
                                        <td>{{ $transaction->type === 'debit' ? number_format($transaction->amount, 2) : '-' }}
                                        </td>
                                        <td>{{ $transaction->type === 'credit' ? number_format($transaction->amount, 2) : '-' }}
                                        </td>
                                        <td>{{ $transaction->note }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @else
            {{-- <div class="alert alert-info">{{ __('accounting::lang.no_data_available') }}</div> --}}
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
                            <span class="fw-bolder"> @lang('accounting::lang.no_data')</span> <br>
                        </h4>
                    </div>

                </div>
            </div>
        @endif
    </div>
@endsection
