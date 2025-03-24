<div class="table-responsive">
    <table class="table align-middle table-striped table-row-bordered fs-6 gy-5" id="profit_by_day_table">
        <thead>
            <tr>
                <th class="min-w-10px p-3 align-middle no-border" style="text-align: inherit;">@lang('report::general.days')</th>
                <th class="min-w-10px p-3 align-middle no-border" style="text-align: inherit;">@lang('report::general.gross_profit')</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($days as $day)
                <tr>
                    <td style="text-align: inherit;">@lang('report::general.' . $day)</td>
                    <td style="text-align: inherit;">
                        <span class="gross-profit" data-orig-value="{{ $profits[$day] ?? 0 }}">
                            @if (isset($profits[$day]))
                                @format_currency($profits[$day])
                            @else
                                0
                            @endif
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-gray font-17 footer-total">
                <td style="text-align: inherit;"><strong>@lang('sales::lang.total_before_vat'):</strong></td>
                <td style="text-align: inherit;">
                    <span class="display_currency footer_total" data-currency_symbol="true">
                        @format_currency(array_sum($profits))
                    </span>
                </td>
            </tr>
        </tfoot>
    </table>
</div>
