@extends('layouts.app')

@section('title', __('accounting::lang.balance_sheet'))

@section('content')

    <section class="content-header">
        <h1>@lang('accounting::lang.balance_sheet')</h1>
    </section>


    <section class="content">
        <div class="row">
            <!-- Date Range Filter -->
            <div class="col-md-3 mt-5" >
                <div class="form-group">
                    <label for="date_range_filter">@lang('accounting::lang.date_range'):</label>
                    <input type="text" name="date_range_filter" id="date_range_filter" class="form-control"
                        placeholder="@lang('accounting::lang.select_a_date_range')" readonly>
                </div>
            </div>

            <!-- Balance Sheet Table -->
            <div class="col-md-12 my-5 col-md-offset-1">
                <div class="box box-warning shadow-lg rounded-lg p-4">
                    <div class="box-header with-border text-center mb-4">
                        <h2 class="box-title text-2xl font-bold">@lang('accounting::lang.balance_sheet')</h2>
                        <p>{{ \Carbon\Carbon::parse($start_date)->format('Y-m-d') }} ~
                            {{ \Carbon\Carbon::parse($end_date)->format('Y-m-d') }}</p>
                    </div>

                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-bordered fs-6 gy-5" id="kt_accounts_table">
                                <thead>
                                    <tr class="text-start text-gray-600 fw-bold fs-7 text-uppercase gs-0 w-100"
                                        style="background: rgb(219 226 247);">
                                        <th class="success">@lang('accounting::lang.assets')</th>
                                        <th class="warning">@lang('accounting::lang.liab_owners_capital')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="col-md-6">
                                            <table class="table">
                                                @php $total_assets = 0; @endphp
                                                @foreach ($assets as $asset)
                                                    @php $total_assets += $asset->balance; @endphp
                                                    <tr>
                                                        <th>{{ $asset->name_ar }}</th>
                                                        <td>@format_currency($asset->balance)</td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </td>
                                        <td class="col-md-6">
                                            <table class="table">
                                                @php $total_liab_owners = 0; @endphp
                                                @foreach ($liabilities as $liability)
                                                    @php $total_liab_owners += $liability->balance; @endphp
                                                    <tr>
                                                        <th>{{ $liability->name_ar }}</th>
                                                        <td>@format_currency($liability->balance)</td>
                                                    </tr>
                                                @endforeach
                                                @foreach ($equities as $equity)
                                                    @php $total_liab_owners += $equity->balance; @endphp
                                                    <tr>
                                                        <th>{{ $equity->name_ar }}</th>
                                                        <td>@format_currency($equity->balance)</td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td class="col-md-6">
                                            <span><strong>@lang('accounting::lang.total_assets'): </strong></span>
                                            <span>@format_currency($total_assets)</span>
                                        </td>
                                        <td class="col-md-6">
                                            <span><strong>@lang('accounting::lang.total_liab_owners'): </strong></span>
                                            <span>@format_currency($total_liab_owners)</span>
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
        $(document).ready(function() {

            dateRangeSettings.startDate = moment('{{ $start_date }}');
            dateRangeSettings.endDate = moment('{{ $end_date }}');

            $('#date_range_filter').daterangepicker(
                dateRangeSettings,
                function(start, end) {
                    $('#date_range_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(
                        moment_date_format));
                    apply_filter();
                }
            );
            $('#date_range_filter').on('cancel.daterangepicker', function(ev, picker) {
                $('#date_range_filter').val('');
                apply_filter();
            });

            function apply_filter() {
                var start = '';
                var end = '';

                if ($('#date_range_filter').val()) {
                    start = $('input#date_range_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#date_range_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }

                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('start_date', start);
                urlParams.set('end_date', end);
                window.location.search = urlParams;
            }
        });
    </script>

@stop
