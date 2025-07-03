@extends('layouts.app')

@section('title', __('accounting::lang.account_payable_ageing_details'))

@section('css')
    <style>
        .table-sticky thead,
        .table-sticky tfoot {
            position: sticky;
        }

        .table-sticky thead {
            inset-block-start: 0;
            /* "top" */
        }

        .table-sticky tfoot {
            inset-block-end: 0;
            /* "bottom" */
        }

        .collapsed .collapse-tr {
            display: none;
        }
    </style>
@endsection

@section('content')

    <section class="content">
        <div class="row">
            <div class="col-md-12 col-md-offset-1">
                <div class="card ">
                    <div class="card-header py-7 d-flex justify-content-between align-items-start flex-wrap">
                        <div>
                            <h3 class="card-title mb-1">
                                @lang('accounting::lang.account_payable_ageing_details')
                            </h3>
                            <p class="mb-0 text-muted">
                                @lang('accounting::lang.account_payable_ageing_details_description')
                            </p>
                        </div>
                        <button class="btn btn-primary" onclick="printReport()" id="print-btn">
                            <i class="fas fa-print mr-2"></i>
                            @lang('general.print')
                        </button>
                    </div>
                        <div class="card-body">
                        <div class="box box-warning mt-4">
                            <div class="box-body">
                                <table class="table table-striped table-bordered table-hover" id="report-table">
                                    <thead>
                                        <tr>
                                            <th>@lang('reports.date')</th>
                                            <th>@lang('accounting::lang.transaction_type')</th>
                                            <th>@lang('sales::fields.ref_no')</th>
                                            <th>@lang('sales::fields.supplier')</th>
                                            <th>@lang('sales::fields.due_date')</th>
                                            <th>@lang('report::general.cash_due')</th>
                                        </tr>
                                    </thead>
                                    @foreach ($report_details as $key => $value)
                                        <tbody @if ($loop->index != 0) class="collapsed" @endif>
                                            <tr class="toggle-tr" style="cursor: pointer;">
                                                <th colspan="6">
                                                    <span class="collapse-icon">
                                                        <i class="fas fa-chevron-right px-2"></i>
                                                    </span>
                                                    @if ($key == 'current')
                                                        <spna style="color: #2dce89 !important;">
                                                            @lang('accounting::lang.current') </spna>
                                                    @elseif($key == '1_30')
                                                        <span style="color: #ffd026 !important;">
                                                            @lang('accounting::lang.days_past_due', ['days' => '1 - 30'])
                                                        </span>
                                                    @elseif($key == '31_60')
                                                        <span style="color: #ffa100 !important;">
                                                            @lang('accounting::lang.days_past_due', ['days' => '31 - 60'])
                                                        </span>
                                                    @elseif($key == '61_90')
                                                        <span style="color: #f5365c !important;">
                                                            @lang('accounting::lang.days_past_due', ['days' => '61 - 90'])
                                                        </span>
                                                    @elseif($key == '>90')
                                                        <span style="color: #FF0000 !important;">
                                                            @lang('accounting::lang.91_and_over_past_due')
                                                        </span>
                                                    @endif
                                                </th>
                                            </tr>
                                            @php
                                                $total = 0;
                                            @endphp
                                            @foreach ($value as $details)
                                                @php
                                                    $total += $details['due'];
                                                @endphp
                                                <tr class="collapse-tr">
                                                    <td>
                                                        {{ $details['transaction_date'] }}
                                                    </td>
                                                    <td>
                                                        @lang('menuItemLang.purchases')
                                                    </td>
                                                    <td>
                                                        {{ $details['ref_no'] }}
                                                    </td>
                                                    <td>
                                                        {{ $details['contact_name'] }}
                                                    </td>
                                                    <td>
                                                        {{ $details['due_date'] }}
                                                    </td>
                                                    <td>
                                                        @format_currency($details['due'])
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr class="collapse-tr bg-gray">
                                                <th>
                                                    @if ($key == 'current')
                                                        @lang('accounting::lang.total_for_current')
                                                    @elseif($key == '1_30')
                                                        @lang('accounting::lang.total_for_days_past_due', ['days' => '1 - 30'])
                                                    @elseif($key == '31_60')
                                                        @lang('accounting::lang.total_for_days_past_due', ['days' => '31 - 60'])
                                                    @elseif($key == '61_90')
                                                        @lang('accounting::lang.total_for_days_past_due', ['days' => '61 - 90'])
                                                    @elseif($key == '>90')
                                                        @lang('accounting::lang.total_for_91_and_over')
                                                    @endif
                                                </th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th>@format_currency($total)</th>
                                            </tr>
                                        </tbody>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </section>
@stop

@section('script')

    <script type="text/javascript">
        $(document).on('click', '.toggle-tr', function() {
            $(this).closest('tbody').toggleClass('collapsed');
            var html = $(this).closest('tbody').hasClass('collapsed') ?
                '<i class="fas fa-chevron-right px-2"></i>' : '<i class="fas fa-chevron-down px-2"></i>';
            $(this).find('.collapse-icon').html(html);
        })

        function printReport() {
            document.querySelectorAll('tbody.collapsed').forEach(tbody => {
                tbody.classList.remove('collapsed');
            });

            document.querySelectorAll('.collapse-icon').forEach(icon => {
                icon.innerHTML = '<i class="fas fa-chevron-down px-2"></i>';
            });

            var printContents = document.querySelector('.card').outerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;

            window.location.reload();
        }
    </script>

@stop
