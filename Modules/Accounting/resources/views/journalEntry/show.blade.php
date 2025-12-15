@extends('layouts.app')

@php
    $title = __('accounting::lang.view_journalEntry') . ' - ' . $acc_trans_mapping->ref_no;
@endphp

@section('title', $title)

@section('css')
<style>
    .readonly-input {
        background-color: #f5f5f5 !important;
        cursor: default;
    }
</style>
@stop

@section('content')

<div class="container">

    {{-- ===== Header ===== --}}
    <div class="row mb-5">
        <div class="col-6">
            <h1>@lang('accounting::lang.view_journalEntry')</h1>
        </div>
            <div class="col-6" style="justify-content: end;display: flex;">
                <div class="row">

                    {{-- <div class="navigation-buttons"> --}}
                    <div class="col-2">
                        @if ($previous)

                                <a href="{{ route('journal-entry-show', $previous->id) }}" class="btn btn-primary "
                                    style="padding: 5px;
                                border-radius: 50%;"><i
                                        @if (app()->getLocale() == 'en') class="ki-outline ki-arrow-left fs-1 p-0" @endif
                                        @if (app()->getLocale() == 'ar') class="ki-outline ki-arrow-right fs-1 p-0" @endif></i></a>

                        @endif
                    </div>
                    <div class="col-8">

                        <select id="acc_trans_mappings" class="form-select form-select-solid select-2" name="id">

                            @foreach ($acc_trans_mappings as $acc_trans)
                                <option value="{{ $acc_trans->id }}" @if ($acc_trans_mapping->id == $acc_trans->id) selected @endif>

                                    {{ $acc_trans->ref_no }}
                                </option>
                            @endforeach

                        </select>
                    </div>
                    <div class="col-2">

                        @if ($next)

                                <a href="{{ route('journal-entry-show', $next->id) }}" class="btn btn-primary"
                                    style="padding: 5px;
                                border-radius: 50%;"><i
                                        @if (app()->getLocale() == 'en') class="ki-outline ki-arrow-right fs-1 p-0" @endif
                                        @if (app()->getLocale() == 'ar') class="ki-outline ki-arrow-left fs-1 p-0" @endif></i></a>

                        @endif
                        {{-- </div> --}}
                    </div>
                </div>
            </div>
        <div class="col-12 text-start">
            <a href="{{ route('journal-entry-index') }}" class="btn btn-secondary">
                @lang('Back')
            </a>

            <a href="{{ url("/journal-entry-export-pdf/{$acc_trans_mapping->id}") }}"
               class="btn btn-primary mx-2">
                @lang('general.export_as_pdf')
            </a>

            <a href="{{ url("/journal-entry-export-excel/{$acc_trans_mapping->id}") }}"
               class="btn btn-primary">
                @lang('general.export_as_excel')
            </a>
        </div>
    </div>

    {{-- ===== General Info ===== --}}
    <div class="row mb-8" @if(app()->getLocale()=='ar') dir="rtl" @endif>

        <div class="col-4">
            <label class="fw-semibold mb-2">@lang('accounting::lang.journalEntry_date')</label>
            <input class="form-control readonly-input"
                   value="{{ $acc_trans_mapping->operation_date }}"
                   disabled>
        </div>

        <div class="col-4">
            <label class="fw-semibold mb-2">@lang('accounting::lang.ref_number')</label>
            <input class="form-control readonly-input"
                   value="{{ $acc_trans_mapping->ref_no }}"
                   disabled>
        </div>

        <div class="col-4">
            <label class="fw-semibold mb-2">@lang('accounting::lang.additionalNotes')</label>
            <textarea class="form-control readonly-input" rows="1" disabled>{{ $acc_trans_mapping->note }}</textarea>
        </div>

    </div>

    {{-- ===== Journal Entry Table ===== --}}
    <div class="card mb-8" @if(app()->getLocale()=='ar') dir="rtl" @endif>
        <div class="card-header">
            <h3 class="card-title">
                @lang('accounting::lang.Journal Entry Party')
            </h3>
        </div>

        <div class="card-body py-3">
            <div class="table-responsive">
                <table class="table align-middle gs-0 gy-4">
                    <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th>@lang('accounting::lang.account')</th>
                            <th>@lang('accounting::lang.cost_center')</th>
                            <th>@lang('accounting::lang.debit')</th>
                            <th>@lang('accounting::lang.credit')</th>
                            <th>@lang('accounting::lang.additionalNotes')</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            $totalDebit = 0;
                            $totalCredit = 0;
                        @endphp

                        @foreach ($acc_trans_mapping->transactions as $transaction)
                            @php
                                if ($transaction->type === 'debit') {
                                    $totalDebit += $transaction->amount;
                                } else {
                                    $totalCredit += $transaction->amount;
                                }
                            @endphp

                            <tr>
                                <td>
                                    {{ app()->getLocale() == 'ar'
                                        ? $transaction?->account->name_ar
                                        : $transaction?->account->name_en
                                    }}
                                </td>

                                <td>
                                    {{ optional($transaction->cost_center)->name_ar
                                        ?? optional($transaction->cost_center)->name_en
                                        ?? '-'
                                    }}
                                </td>

                                <td>
                                    {{ $transaction->type === 'debit'
                                        ? number_format($transaction->amount, 2)
                                        : '0.00'
                                    }}
                                </td>

                                <td>
                                    {{ $transaction->type === 'credit'
                                        ? number_format($transaction->amount, 2)
                                        : '0.00'
                                    }}
                                </td>

                                <td>{{ $transaction->note }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="fw-bold bg-light">
                            <td colspan="2">@lang('messages.total')</td>
                            <td>{{ number_format($totalDebit, 2) }}</td>
                            <td>{{ number_format($totalCredit, 2) }}</td>
                            <td></td>
                        </tr>

                        @if (round($totalDebit,2) !== round($totalCredit,2))
                            <tr>
                                <td colspan="5" class="text-danger text-center">
                                    @lang('accounting::lang.The journal entry is unbalanced with a difference of')
                                    ( {{ number_format(abs($totalDebit - $totalCredit), 2) }} )
                                </td>
                            </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>

@stop

@section('script')
<script>

      $('#acc_trans_mappings').select2();


       $('#acc_trans_mappings').on('change', function() {
                var selectedValue = this.value;
                var duplication = {{ $duplication }};
                let url = '';


                    url = '{{ url('journal-entry-show') }}/' + selectedValue;

                console.log(url, duplication, selectedValue);

                window.location.href = url;
            });

</script>
@endsection
