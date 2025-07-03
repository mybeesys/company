@extends('layouts.app')

@section('title', __('accounting::lang.balance_sheet'))

@section('content')

    <section class="content-header">
        <h1>@lang('accounting::lang.balance_sheet')</h1>
    </section>


    <section class="content">
        <div class="row">
            <!-- Date Range Filter -->
            <div class="col-md-12 mt-12">
                <form method="GET" class="mb-4 no-print">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="date" name="start_date" class="form-control"
                                value="{{ request('start_date') ?? $start_date }}">
                        </div>
                        <div class="col-md-4">
                            <input type="date" name="end_date" class="form-control"
                                value="{{ request('end_date') ?? $end_date }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">@lang('report::general.filter')</button>
                        </div>
                    </div>
                </form>

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
                                                    @php $total_assets = 0; @endphp
                                                    @foreach ($assets as $asset)
                                                        @php $total_assets += $asset->balance; @endphp
                                                        <tr class="border-bottom">
                                                            <td class="ps-3 py-2 fw-semibold">{{ $asset->name_ar }}</td>
                                                            <td class="text-end pe-3 py-2">@format_currency($asset->balance)</td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                        </td>

                                        <td class="align-top ps-4">
                                            <div class="d-flex flex-column h-100">
                                                <table class="table table-hover table-borderless mb-0">
                                                    @php $total_liab_owners = 0; @endphp
                                                    @foreach ($liabilities as $liability)
                                                        @php $total_liab_owners += $liability->balance; @endphp
                                                        <tr class="border-bottom">
                                                            <td class="ps-3 py-2 fw-semibold">{{ $liability->name_ar }}</td>
                                                            <td class="text-end pe-3 py-2">@format_currency($liability->balance)</td>
                                                        </tr>
                                                    @endforeach
                                                    @foreach ($equities as $equity)
                                                        @php $total_liab_owners += $equity->balance; @endphp
                                                        <tr class="border-bottom">
                                                            <td class="ps-3 py-2 fw-semibold">{{ $equity->name_ar }}</td>
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
