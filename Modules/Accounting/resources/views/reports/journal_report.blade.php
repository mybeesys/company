@extends('layouts.app')
@section('title', __('accounting::lang.journal_report'))

@section('content')
    <style>
        .sar-icon {
            width: 12px;
            height: 12px;
            vertical-align: middle;
            margin-inline-start: 4px;
            object-fit: contain;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
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
                <div class="col-md-3">
                    <label>{{ __('accounting::lang.ref_no') }}</label>
                    <input type="text" name="ref_no" class="form-control" value="{{ $refNo ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label>{{ __('accounting::lang.note') }}</label>
                    <input type="text" name="note" class="form-control" value="{{ $note ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label for="choose_cost_center_select">{{ __('accounting::lang.cost_center') }}</label>
                    <select name="choose_cost_center_select[]" id="choose_cost_center_select" class="form-select d-flex form-select-solid" multiple>
                        @foreach ($costCenters as $costCenter)
                            <option value="{{ $costCenter->id }}" @if (in_array($costCenter->id, $choose_cost_center_select ?? [])) selected @endif>
                                {{ app()->getLocale() == 'ar' ? $costCenter->name_ar : $costCenter->name_en }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="button" id="journalExportPdf" class="btn btn-light-primary">PDF</button>
                    <button type="button" id="journalExportExcel" class="btn btn-light-success">Excel</button>
                </div>

            </div>
        </form>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="alert alert-light-primary mb-0">
                    <strong>{{ __('accounting::lang.debit') }}:</strong> @format_currency($totalDebit ?? 0)
                    <span>ريال</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-light-success mb-0">
                    <strong>{{ __('accounting::lang.credit') }}:</strong> @format_currency($totalCredit ?? 0)
                    <span>ريال</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert {{ ($difference ?? 0) < 0.005 ? 'alert-light-info' : 'alert-light-warning' }} mb-0">
                    <strong>{{ __('accounting::lang.difference') }}:</strong> @format_currency($difference ?? 0)
                    <span>ريال</span>
                </div>
            </div>
        </div>

        @if (isset($journals) && $journals->isNotEmpty())
            @foreach ($journals as $journal)
                <div class="card mb-4 journal-card" id="journal-card-{{ $journal->id }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start no-print">
                            <div>
                                <h5>{{ __('accounting::lang.ref_no') }}: {{ $journal->ref_no }}</h5>
                                <p>{{ __('accounting::lang.operation_date') }}: {{ $journal->operation_date }}</p>
                                <p>{{ __('accounting::lang.note') }}: {{ $journal->note }}</p>
                            </div>
                            <button type="button" class="btn btn-light-primary btn-sm"
                                onclick="printJournalCard('journal-card-{{ $journal->id }}')">
                                {{ __('general.print') }}
                            </button>
                        </div>
                        <div class="d-none d-print-block">
                            <h5>{{ __('accounting::lang.ref_no') }}: {{ $journal->ref_no }}</h5>
                            <p>{{ __('accounting::lang.operation_date') }}: {{ $journal->operation_date }}</p>
                            <p>{{ __('accounting::lang.note') }}: {{ $journal->note }}</p>
                        </div>

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
                                        <td>{!! $transaction->type === 'debit' ? number_format((float) $transaction->amount, 2) . ' ريال' : '-' !!}
                                        </td>
                                        <td>{!! $transaction->type === 'credit' ? number_format((float) $transaction->amount, 2) . ' ريال' : '-' !!}
                                        </td>
                                        <td>{{ $transaction->note }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="2">{{ __('accounting::lang.total') }}</td>
                                    <td>{{ number_format((float) ($journal->journal_debit ?? 0), 2) }} ريال</td>
                                    <td>{{ number_format((float) ($journal->journal_credit ?? 0), 2) }} ريال</td>
                                    <td>{{ __('accounting::lang.difference') }}: {{ number_format((float) ($journal->journal_diff ?? 0), 2) }} ريال</td>
                                </tr>
                            </tfoot>
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

@section('script')
    <script>
        const journalExportPdfUrl = '{{ route('journal-report-export-pdf') }}';
        const journalExportExcelUrl = '{{ route('journal-report-export-excel') }}';

        function buildJournalQuery() {
            const params = new URLSearchParams();
            const startDate = $('input[name="start_date"]').val();
            const endDate = $('input[name="end_date"]').val();
            const refNo = $('input[name="ref_no"]').val();
            const note = $('input[name="note"]').val();
            const costCenters = $('#choose_cost_center_select').val() || [];

            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            if (refNo) params.append('ref_no', refNo);
            if (note) params.append('note', note);
            costCenters.forEach(function(value) {
                params.append('choose_cost_center_select[]', value);
            });
            return params.toString();
        }

        $(document).ready(function() {
            $('#choose_cost_center_select').select2();

            $('#journalExportPdf').on('click', function() {
                const query = buildJournalQuery();
                window.open(journalExportPdfUrl + '?' + query, '_blank');
            });

            $('#journalExportExcel').on('click', function() {
                const query = buildJournalQuery();
                window.location.href = journalExportExcelUrl + '?' + query;
            });
        });

        function printJournalCard(cardId) {
            const card = document.getElementById(cardId);
            if (!card) return;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>{{ __('accounting::lang.journal_report') }}</title>
                        <style>
                            body { font-family: Arial, sans-serif; padding: 16px; }
                            table { width: 100%; border-collapse: collapse; margin-top: 12px; }
                            th, td { border: 1px solid #d1d5db; padding: 6px; text-align: start; }
                            th { background: #f3f4f6; }
                        </style>
                    </head>
                    <body>${card.innerHTML}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }
    </script>
@endsection
