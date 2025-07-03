@extends('layouts.app')

@section('title', __('accounting::lang.account_recievable_ageing_report'))
@section('style')
    <style>
        @media print {
            #print-btn {
                display: none; }
        }
    </style>
@endsection

@section('content')

<section class="">
    <div class="row">
        <div class="col-md-12 col-md-offset-1">
            <div class="card ">
                <div class="card-header text-center">
                    <h3 class="card-title">
                        @lang('accounting::lang.account_recievable_ageing_report')
                    </h3>
                    <button class="btn btn-primary py-4 my-5 float-left" style="height: max-content" onclick="printReport()" id="print-btn">
                        @lang('general.print')
                    </button>
                </div>
                <div class="card-body">
                    <div class="box box-warning mt-4">
                        <div class="box-body">
                            <table class="table table-striped table-bordered table-hover" id="report-table">
                                <thead>
                                    <tr>
                                        <th>@lang('general::general.customer_name')</th>
                                        <th class="text-success">@lang('accounting::lang.current')</th>
                                        <th class="text-warning">@lang('accounting::lang.1_30_days')</th>
                                        <th class="text-warning">@lang('accounting::lang.31_60_days')</th>
                                        <th class="text-danger">@lang('accounting::lang.61_90_days')</th>
                                        <th class="text-danger">@lang('accounting::lang.91_and_over')</th>
                                        <th>@lang('sales::lang.total_before_vat')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $total_current = 0;
                                        $total_1_30 = 0;
                                        $total_31_60 = 0;
                                        $total_61_90 = 0;
                                        $total_greater_than_90 = 0;
                                        $grand_total = 0;
                                    @endphp
                                    @foreach($report_details as $report)
                                        <tr>
                                            @php
                                                $total_current += $report['<1'];
                                                $total_1_30 += $report['1_30'];
                                                $total_31_60 += $report['31_60'];
                                                $total_61_90 += $report['61_90'];
                                                $total_greater_than_90 += $report['>90'];
                                                $grand_total += $report['total_due'];
                                            @endphp
                                            <td>
                                                <strong>{{$report['name']}}</strong>
                                            </td>
                                            <td class="text-success">
                                                @format_currency($report['<1'])
                                            </td>
                                            <td class="text-warning">
                                                @format_currency($report['1_30'])
                                            </td>
                                            <td class="text-warning">
                                                @format_currency($report['31_60'])
                                            </td>
                                            <td class="text-danger">
                                                @format_currency($report['61_90'])
                                            </td>
                                            <td class="text-danger">
                                                @format_currency($report['>90'])
                                            </td>
                                            <td>
                                                @format_currency($report['total_due'])
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>@lang('sales::lang.total_before_vat')</th>
                                        <td class="text-success">
                                            @format_currency($total_current)
                                        </td>
                                        <td class="text-warning">
                                            @format_currency($total_1_30)
                                        </td>
                                        <td class="text-warning">
                                            @format_currency($total_31_60)
                                        </td>
                                        <td class="text-danger">
                                            @format_currency($total_61_90)
                                        </td>
                                        <td class="text-danger">
                                            @format_currency($total_greater_than_90)
                                        </td>
                                        <td class="font-weight-bold">@format_currency($grand_total)</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

@endsection

@section('script')
    <script>
        function printReport() {
            var printContents = document.querySelector('.card').outerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
    </script>
@endsection

