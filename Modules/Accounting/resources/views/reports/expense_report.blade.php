@extends('layouts.app')
@section('title', __('accounting::lang.expense_report'))

@section('content')
    <section class="content-header py-3 no-print">
        <h1 class="mb-1">{{ __('accounting::lang.expense_report') }}</h1>
        <p class="text-muted small mb-0">{{ __('accounting::lang.expense_report_intro') }}</p>
    </section>

    <div class="card card-flush shadow-sm mb-6 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('expense-report') }}" id="expenseReportFilters">
                <div class="row g-4">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('accounting::lang.from_date') }}</label>
                        <input type="date" name="start_date" class="form-control"
                            value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('accounting::lang.to_date') }}</label>
                        <input type="date" name="end_date" class="form-control"
                            value="{{ $endDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('expense::fields.debit_account') }}</label>
                        <select name="debit_account_ids[]" id="expense_report_debit_accounts" class="form-select" multiple>
                            @foreach ($expenseAccounts as $acc)
                                @php $nm = app()->getLocale() === 'ar' ? $acc->name_ar : $acc->name_en; @endphp
                                <option value="{{ $acc->id }}" @selected(in_array($acc->id, $debitAccountIds ?? [], true))>
                                    {{ $nm }} ({{ $acc->gl_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('expense::fields.cost_center') }}</label>
                        <select name="cost_center_ids[]" id="expense_report_cost_centers" class="form-select" multiple>
                            @foreach ($costCenters as $cc)
                                @php $ccNm = app()->getLocale() === 'ar' ? $cc->name_ar : $cc->name_en; @endphp
                                <option value="{{ $cc->id }}" @selected(in_array($cc->id, $costCenterIds ?? [], true))>
                                    {{ $ccNm }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('expense::fields.credit_account') }}</label>
                        <select name="credit_account_ids[]" id="expense_report_treasury" class="form-select" multiple>
                            @foreach ($treasuryAccounts as $acc)
                                @php
                                    $nm = app()->getLocale() === 'ar' ? $acc->name_ar : $acc->name_en;
                                @endphp
                                <option value="{{ $acc->id }}" @selected(in_array($acc->id, $creditAccountIds ?? [], true))>
                                    {{ $nm }} ({{ $acc->gl_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('accounting::lang.expense_report_tax_filter') }}</label>
                        <select name="tax_id" class="form-select">
                            <option value="all" @selected(($taxId ?? 'all') === 'all')>@lang('accounting::lang.expense_report_tax_all')</option>
                            <option value="none" @selected(($taxId ?? '') === 'none')>@lang('accounting::lang.expense_report_tax_none')</option>
                            @foreach ($taxes as $tax)
                                <option value="{{ $tax->id }}" @selected((string) ($taxId ?? '') === (string) $tax->id)>{{ $tax->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">{{ __('accounting::lang.search') }}</label>
                        <input type="text" name="q" class="form-control" value="{{ $keyword ?? '' }}"
                            placeholder="{{ __('accounting::lang.expense_report_search_placeholder') }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="with_attachments" value="1"
                                @checked($withAttachments ?? false)>
                            <span class="form-check-label">{{ __('accounting::lang.expense_report_with_attachments') }}</span>
                        </label>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                        <button type="submit" class="btn btn-primary">{{ __('accounting::lang.search') }}</button>
                        <a href="{{ route('expense-report') }}" class="btn btn-light">{{ __('accounting::lang.clear_filters') }}</a>
                        <button type="button" id="expenseExportPdf" class="btn btn-export-pdf">PDF</button>
                        <button type="button" id="expenseExportExcel" class="btn btn-export-excel">Excel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @php $summary = $summary ?? ['count' => 0, 'net' => 0, 'tax' => 0, 'gross' => 0]; @endphp
    <div class="row g-4 g-xl-5 mb-8">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-light-primary bg-opacity-25">
                <div class="card-body d-flex align-items-center gap-4 py-6 px-6">
                    <span class="symbol symbol-50px bg-white rounded-2 border">
                        <span class="symbol-label"><i class="ki-outline ki-element-11 fs-2x text-primary"></i></span>
                    </span>
                    <div>
                        <span class="text-gray-700 fw-semibold fs-7 text-uppercase">@lang('accounting::lang.expense_report_count')</span>
                        <span class="d-block fs-2 fw-bold text-gray-900">{{ number_format($summary['count']) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-light-success bg-opacity-25">
                <div class="card-body d-flex align-items-center gap-4 py-6 px-6">
                    <span class="symbol symbol-50px bg-white rounded-2 border">
                        <span class="symbol-label"><i class="ki-outline ki-chart-simple fs-2x text-success"></i></span>
                    </span>
                    <div>
                        <span class="text-gray-700 fw-semibold fs-7 text-uppercase">@lang('accounting::lang.expense_report_net')</span>
                        <span class="d-block fs-2 fw-bold text-gray-900">@format_currency($summary['net'])</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-light-warning bg-opacity-25">
                <div class="card-body d-flex align-items-center gap-4 py-6 px-6">
                    <span class="symbol symbol-50px bg-white rounded-2 border">
                        <span class="symbol-label"><i class="ki-outline ki-percentage fs-2x text-warning"></i></span>
                    </span>
                    <div>
                        <span class="text-gray-700 fw-semibold fs-7 text-uppercase">@lang('accounting::lang.expense_report_tax')</span>
                        <span class="d-block fs-2 fw-bold text-gray-900">@format_currency($summary['tax'])</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-light-info bg-opacity-25">
                <div class="card-body d-flex align-items-center gap-4 py-6 px-6">
                    <span class="symbol symbol-50px bg-white rounded-2 border">
                        <span class="symbol-label"><i class="ki-outline ki-wallet fs-2x text-info"></i></span>
                    </span>
                    <div>
                        <span class="text-gray-700 fw-semibold fs-7 text-uppercase">@lang('accounting::lang.expense_report_gross')</span>
                        <span class="d-block fs-2 fw-bold text-gray-900">@format_currency($summary['gross'])</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (($byAccount ?? collect())->isNotEmpty())
        <div class="card card-flush shadow-sm mb-6">
            <div class="card-header">
                <h3 class="card-title">@lang('accounting::lang.expense_report_by_account')</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-gray-200 align-middle gs-0 gy-3">
                        <thead>
                            <tr class="fw-bold text-muted text-uppercase fs-7">
                                <th>@lang('expense::fields.debit_account')</th>
                                <th class="text-end">@lang('accounting::lang.expense_report_count')</th>
                                <th class="text-end">@lang('expense::fields.net_amount')</th>
                                <th class="text-end">@lang('expense::fields.tax_amount')</th>
                                <th class="text-end">@lang('expense::fields.gross_amount')</th>
                                <th class="text-end">@lang('accounting::lang.expense_report_share')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $grossTotal = max((float) ($summary['gross'] ?? 0), 0.000001); @endphp
                            @foreach ($byAccount as $row)
                                <tr>
                                    <td class="fw-semibold text-gray-800">{{ $row->account_name }} <span class="text-muted">({{ $row->account_gl_code }})</span></td>
                                    <td class="text-end">{{ number_format((int) $row->expense_count) }}</td>
                                    <td class="text-end">@format_currency($row->net_total)</td>
                                    <td class="text-end">@format_currency($row->tax_total)</td>
                                    <td class="text-end">@format_currency($row->gross_total)</td>
                                    <td class="text-end text-muted">{{ number_format(((float) $row->gross_total / $grossTotal) * 100, 1) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold border-top">
                                <td>@lang('accounting::lang.total')</td>
                                <td class="text-end">{{ number_format($summary['count']) }}</td>
                                <td class="text-end">@format_currency($summary['net'])</td>
                                <td class="text-end">@format_currency($summary['tax'])</td>
                                <td class="text-end">@format_currency($summary['gross'])</td>
                                <td class="text-end">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card card-flush shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title mb-0">@lang('accounting::lang.expense_report_details')</h3>
            <span class="text-muted fs-7">
                {{ __('accounting::lang.from_date') }}: {{ $startDate }}
                — {{ __('accounting::lang.to_date') }}: {{ $endDate }}
            </span>
        </div>
        <div class="card-body pt-0">
            @if (($expenses ?? collect())->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-gray-200 align-middle gs-0 gy-3" id="expenseReportTable">
                        <thead>
                            <tr class="fw-bold text-muted text-uppercase fs-7">
                                <th>@lang('expense::fields.expense_date')</th>
                                <th>#</th>
                                <th>@lang('expense::fields.debit_account')</th>
                                <th>@lang('expense::fields.credit_account')</th>
                                <th>@lang('expense::fields.cost_center')</th>
                                <th>@lang('expense::fields.description')</th>
                                <th class="text-end">@lang('expense::fields.net_amount')</th>
                                <th class="text-end">@lang('expense::fields.tax_amount')</th>
                                <th class="text-end">@lang('expense::fields.gross_amount')</th>
                                <th class="text-center">@lang('expense::fields.attachments')</th>
                                <th class="text-center no-print">@lang('employee::fields.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($expenses as $expense)
                                @php
                                    $debit = $expense->debitAccount;
                                    $debitNm = $debit ? (app()->getLocale() === 'ar' ? $debit->name_ar : $debit->name_en) : '—';
                                    $credit = $expense->creditAccount;
                                    $creditNm = $credit ? (app()->getLocale() === 'ar' ? $credit->name_ar : $credit->name_en) : '—';
                                    $cc = $expense->costCenter;
                                    $ccNm = $cc ? (app()->getLocale() === 'ar' ? $cc->name_ar : $cc->name_en) : '—';
                                @endphp
                                <tr>
                                    <td>{{ $expense->date?->format('Y-m-d') ?? '—' }}</td>
                                    <td><span class="badge badge-light-info">{{ $expense->id }}</span></td>
                                    <td>
                                        <div class="fw-semibold text-gray-800">{{ $debitNm }}</div>
                                        @if ($debit)
                                            <div class="text-muted fs-8">{{ $debit->gl_code }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-gray-800">{{ $creditNm }}</div>
                                        @if ($credit)
                                            <div class="text-muted fs-8">{{ $credit->gl_code }}</div>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $ccNm }}</td>
                                    <td class="text-gray-700">{{ \Illuminate\Support\Str::limit($expense->description, 80) }}</td>
                                    <td class="text-end">@format_currency($expense->net_amount)</td>
                                    <td class="text-end">@format_currency((float) $expense->getRawOriginal('tax'))</td>
                                    <td class="text-end fw-semibold">@format_currency($expense->total)</td>
                                    <td class="text-center">{{ $expense->attachments_count ?? 0 }}</td>
                                    <td class="text-center no-print">
                                        <a href="{{ route('expenses.manage.show', $expense->id) }}" class="btn btn-sm btn-light-primary">
                                            @lang('accounting::lang.voucher_show')
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold border-top bg-light">
                                <td colspan="6" class="text-end">@lang('accounting::lang.total')</td>
                                <td class="text-end">@format_currency($summary['net'])</td>
                                <td class="text-end">@format_currency($summary['tax'])</td>
                                <td class="text-end">@format_currency($summary['gross'])</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="py-10 text-center">
                    <img src="/assets/media/illustrations/empty-content.svg" class="w-200px mb-4" alt="">
                    <h4 class="fw-semibold text-gray-800">@lang('accounting::lang.no_data')</h4>
                    <p class="text-muted">@lang('accounting::lang.expense_report_empty_hint')</p>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('script')
    <script>
        const expenseExportPdfUrl = @json(route('expense-report-export-pdf'));
        const expenseExportExcelUrl = @json(route('expense-report-export-excel'));

        function buildExpenseReportQuery() {
            const form = document.getElementById('expenseReportFilters');
            const params = new URLSearchParams(new FormData(form));
            if (!form.querySelector('[name=with_attachments]').checked) {
                params.delete('with_attachments');
            }
            return params.toString();
        }

        $(document).ready(function() {
            const select2Dir = document.documentElement.getAttribute('dir') === 'rtl' ? 'rtl' : 'ltr';

            $('#expense_report_debit_accounts, #expense_report_cost_centers').select2({
                width: '100%',
                dir: select2Dir,
                placeholder: @json(__('messages.select')),
                allowClear: true,
                closeOnSelect: false,
            });

            $('#expense_report_treasury').select2({
                width: '100%',
                dir: select2Dir,
                placeholder: @json(__('expense::lang.filter_treasury_placeholder')),
                allowClear: true,
                closeOnSelect: false,
                minimumResultsForSearch: 0,
            });

            $('#expenseExportPdf').on('click', function() {
                window.open(expenseExportPdfUrl + '?' + buildExpenseReportQuery(), '_blank');
            });

            $('#expenseExportExcel').on('click', function() {
                window.location.href = expenseExportExcelUrl + '?' + buildExpenseReportQuery();
            });
        });
    </script>
@endsection
