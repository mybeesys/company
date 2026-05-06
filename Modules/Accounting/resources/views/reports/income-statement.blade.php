@extends('layouts.app')
@section('title', __('accounting::lang.income_list'))

@section('css')
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
        }

        .income-kpi {
            border: 1px solid #eef0f4;
            border-radius: 10px;
            padding: 12px 14px;
            background: #fcfcfd;
            min-height: 86px;
        }
    </style>
@stop

@section('content')
    <section class="content-header py-3 ">
        <h2>{{ $company->name }}</h2>

        <h4 class="mb-4">@lang('accounting::lang.income_list')</h4>
    </section>

    <div class="container-fluid" id="income-report">
        @include('accounting::reports.partials.inventory_policy_notice')
        <div class="row">
            <div class="col-md-12">
                <form method="GET" class="mb-4 no-print">
                    <div class="row">
                        <div class="col-md-3">
                            <input type="date" name="start_date" class="form-control"
                                value="{{ request('start_date') ?? $start_date }}">
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="end_date" class="form-control"
                                value="{{ request('end_date') ?? $end_date }}">
                        </div>
                        <div class="col-md-3">
                        <div class="form-group" style="top: -18px;position: relative;">
                            <label for="choose_cost_center_select">{{ __('accounting::lang.cost_center') }}:</label>
                            <select name="choose_cost_center_select[]"  id="choose_cost_center_select"
                                class="form-select d-flex form-select-solid" multiple>
                                @foreach ($costCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}"   @if(in_array($costCenter->id, $choose_cost_center_select ?? [])) selected @endif>
                                        @if (app()->getLocale() == 'ar')
                                            {{ $costCenter->account_center_number . ' - ' . $costCenter->name_ar }}
                                        @else
                                            {{ $costCenter->account_center_number . ' - ' . $costCenter->name_en }}
                                        @endif

                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">@lang('report::general.filter')</button>
                            <button type="button" id="incomeStatementExportPdf" class="btn btn-export-pdf">PDF</button>
                            <button type="button" id="incomeStatementExportExcel" class="btn btn-export-excel">Excel</button>
                        </div>
                    </div>
                </form>

                {{-- <div class="mb-3 text-center">
                    <strong>@lang('accounting::lang.date_range'):</strong>
                    {{ \Carbon\Carbon::parse($start_date)->format('Y-m-d') }} -
                    {{ \Carbon\Carbon::parse($end_date)->format('Y-m-d') }}
                </div> --}}

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="income-kpi">
                            <div class="text-muted fs-7">@lang('accounting::lang.Revenues')</div>
                            <div class="fw-bold fs-4">@format_currency($data['revenue_net'] ?? 0)</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="income-kpi">
                            <div class="text-muted fs-7">@lang('accounting::lang.account_types.expenses')</div>
                            <div class="fw-bold fs-4">@format_currency($data['total_expense'] ?? 0)</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="income-kpi">
                            <div class="text-muted fs-7">@lang('report::general.gross_profit')</div>
                            <div class="fw-bold fs-4">@format_currency($data['gross_profit'] ?? 0)</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="income-kpi">
                            <div class="text-muted fs-7">@lang('accounting::lang.net_profit')</div>
                            <div class="fw-bold fs-4">@format_currency(($data['income_before_tax'] ?? 0) - ($data['tax_amount'] ?? 0))</div>
                        </div>
                    </div>
                </div>

                <table class="table table-bordered">
                    <thead class="text-white" style="background-color: #e4e9f1b7">
                        <tr>
                            <th style="width: 50%">@lang('accounting::lang.Revenues')</th>
                            <th style="width: 50%">@lang('employee::fields.amount')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($revenueAccounts as $account)
                            <tr>
                                <td>{{ app()->getLocale() == 'ar' ? $account->name_ar : $account->name_en }}</td>
                                <td>@format_currency($account->amount)</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">@lang('messages.no_data_found')</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <td><strong>@lang('accounting::lang.total')</strong></td>
                            <td><strong>@format_currency($data['revenue_net'] ?? 0)</strong></td>
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
                        @forelse ($expenseAccounts as $account)
                            <tr>
                                <td>{{ app()->getLocale() == 'ar' ? $account->name_ar : $account->name_en }}</td>
                                <td>@format_currency($account->amount)</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">@lang('messages.no_data_found')</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <td><strong>@lang('accounting::lang.total')</strong></td>
                            <td><strong>@format_currency($data['total_expense'] ?? 0)</strong></td>
                        </tr>
                        <tr style="background-color: #e4e9f1b7">
                            <td>
                                <h3>@lang('report::general.gross_profit')</h3>
                            </td>
                            <td>
                                <h3>@format_currency($data['gross_profit'] ?? 0)</h3>
                            </td>
                        </tr>
                        <tr style="background-color: #f6f8fb">
                            <td><strong>@lang('accounting::lang.income_before_tax')</strong></td>
                            <td><strong>@format_currency($data['income_before_tax'] ?? 0)</strong></td>
                        </tr>
                        <tr style="background-color: #f6f8fb">
                            <td><strong>@lang('accounting::lang.tax_amount')</strong></td>
                            <td><strong>@format_currency($data['tax_amount'] ?? 0)</strong></td>
                        </tr>
                        <tr style="background-color: #e4e9f1b7">
                            <td><h3>@lang('accounting::lang.net_profit')</h3></td>
                            <td><h3>@format_currency(($data['income_before_tax'] ?? 0) - ($data['tax_amount'] ?? 0))</h3></td>
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
        const incomeExportPdfUrl = '{{ route('income-statement-export-pdf') }}';
        const incomeExportExcelUrl = '{{ route('income-statement-export-excel') }}';

        function buildIncomeQuery() {
            const params = new URLSearchParams();
            const startDate = $('input[name="start_date"]').val();
            const endDate = $('input[name="end_date"]').val();
            const costCenters = $('#choose_cost_center_select').val() || [];

            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            costCenters.forEach(function(value) {
                params.append('choose_cost_center_select[]', value);
            });

            return params.toString();
        }

       $(document).ready(function() {
            $('#choose_cost_center_select').select2();

            $('#incomeStatementExportPdf').on('click', function() {
                const query = buildIncomeQuery();
                window.open(incomeExportPdfUrl + '?' + query, '_blank');
            });

            $('#incomeStatementExportExcel').on('click', function() {
                const query = buildIncomeQuery();
                window.location.href = incomeExportExcelUrl + '?' + query;
            });

       });
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
