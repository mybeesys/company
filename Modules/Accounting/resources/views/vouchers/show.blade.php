@extends('layouts.app')

@section('title', $pageTitle ?? __('accounting::lang.ledger'))

@section('content')
    <div class="container">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @include('accounting::vouchers.partials.show_content')

                <div class="d-flex justify-content-end gap-2 mt-6">
                    <a href="{{ $backUrl }}" class="btn btn-light">{{ __('accounting::lang.back') ?? __('messages.back') }}</a>
                </div>

            </div>
        </div>
    </div>
@endsection

