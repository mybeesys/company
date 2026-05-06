@extends('layouts.app')

@section('title', __('accounting::lang.balance_sheet'))

@section('content')

    <section class="content-header">
        <h1>@lang('accounting::lang.balance_sheet')</h1>
    </section>


    <section class="content">
        @include('accounting::reports.partials.inventory_policy_notice')
        <div class="row">
            <!-- Date Range Filter -->
            <div class="col-md-12 mt-12">
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
                                <select name="choose_cost_center_select[]" id="choose_cost_center_select"
                                    class="form-select d-flex form-select-solid" multiple>
                                    @foreach ($costCenters as $costCenter)
                                        <option value="{{ $costCenter->id }}"
                                            @if (in_array($costCenter->id, $choose_cost_center_select ?? [])) selected @endif>
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
                        <div class="col-md-3">
                            <label for="with_zero_balances" class="form-label">@lang('accounting::lang.balance')</label>
                            <select name="with_zero_balances" id="with_zero_balances" class="form-control">
                                <option value="0" @selected(($with_zero_balances ?? 0) == 0)>@lang('accounting::lang.without_zero_balances')</option>
                                <option value="1" @selected(($with_zero_balances ?? 0) == 1)>@lang('accounting::lang.with_zero_balances')</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">@lang('report::general.filter')</button>
                            <button type="button" id="balanceSheetExportPdf" class="btn btn-export-pdf">PDF</button>
                            <button type="button" id="balanceSheetExportExcel" class="btn btn-export-excel">Excel</button>
                        </div>
                    </div>
                </form>

            </div>

            <div class="col-md-12 mb-3">
                <div class="alert {{ ($difference ?? 0) < 0.005 ? 'alert-success' : 'alert-warning' }} py-2 mb-2">
                    <strong>@lang('accounting::lang.balance'):</strong> {{ $balance_status ?? '-' }}
                    <span class="mx-2">|</span>
                    <strong>@lang('accounting::lang.difference'):</strong> @format_currency($difference ?? 0)
                </div>
            </div>

            <div class="col-md-12 my-4">
                <div class="card shadow-sm border-0 rounded-lg">

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0" id="kt_accounts_table">
                                <thead class="bg-light-primary">
                                    <tr class="text-start text-gray-700 fw-bold fs-6 text-uppercase bg-light-blue">
                                        <th class="py-3 ps-4 w-50">@lang('accounting::lang.assets')</th>
                                        <th class="py-3 w-50">@lang('accounting::lang.liab_owners_capital')</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td class="align-top pe-4" style="border-right: 1px solid #eee;">
                                            <div class="d-flex flex-column h-100">
                                                <table class="table table-hover table-borderless mb-0">
                                                    @foreach ($assets as $asset)
                                                        <tr class="border-bottom">
                                                            <td class="ps-3 py-2 fw-semibold">{{ app()->getLocale() == 'ar' ? $asset->name_ar : $asset->name_en }}</td>
                                                            <td class="text-end pe-3 py-2">@format_currency($asset->balance)</td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                        </td>

                                        <td class="align-top ps-4">
                                            <div class="d-flex flex-column h-100">
                                                <table class="table table-hover table-borderless mb-0">
                                                    @foreach ($liabilities as $liability)
                                                        <tr class="border-bottom">
                                                            <td class="ps-3 py-2 fw-semibold">{{ app()->getLocale() == 'ar' ? $liability->name_ar : $liability->name_en }}</td>
                                                            <td class="text-end pe-3 py-2">@format_currency($liability->balance)</td>
                                                        </tr>
                                                    @endforeach
                                                    @foreach ($equities as $equity)
                                                        <tr class="border-bottom">
                                                            <td class="ps-3 py-2 fw-semibold">{{ app()->getLocale() == 'ar' ? $equity->name_ar : $equity->name_en }}</td>
                                                            <td class="text-end pe-3 py-2">@format_currency($equity->balance)</td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>

                                <tfoot class="border-top">
                                    <tr class="bg-light-gray">
                                        <td class="py-3 ps-4 fw-bold">
                                            <span>@lang('accounting::lang.total_assets'): </span>
                                            <span class="float-end me-3">@format_currency($total_assets)</span>
                                        </td>
                                        <td class="py-3 ps-4 fw-bold">
                                            <span>@lang('accounting::lang.total_liab_owners'): </span>
                                            <span class="float-end me-3">@format_currency($total_liab_owners)</span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


@stop

@section('script')

    <script>
        const balanceSheetExportPdfUrl = '{{ route('balance-sheet-export-pdf') }}';
        const balanceSheetExportExcelUrl = '{{ route('balance-sheet-export-excel') }}';

        function buildBalanceSheetQuery() {
            const params = new URLSearchParams();
            const startDate = $('input[name="start_date"]').val();
            const endDate = $('input[name="end_date"]').val();
            const withZeroBalances = $('#with_zero_balances').val();
            const costCenters = $('#choose_cost_center_select').val() || [];

            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            if (withZeroBalances !== null) params.append('with_zero_balances', withZeroBalances);
            costCenters.forEach(function(value) {
                params.append('choose_cost_center_select[]', value);
            });
            return params.toString();
        }

        $(document).ready(function() {
            $('#choose_cost_center_select').select2();
            $('#with_zero_balances').select2();

            $('#balanceSheetExportPdf').on('click', function() {
                const query = buildBalanceSheetQuery();
                window.open(balanceSheetExportPdfUrl + '?' + query, '_blank');
            });

            $('#balanceSheetExportExcel').on('click', function() {
                const query = buildBalanceSheetQuery();
                window.location.href = balanceSheetExportExcelUrl + '?' + query;
            });

        });
    </script>

@stop
