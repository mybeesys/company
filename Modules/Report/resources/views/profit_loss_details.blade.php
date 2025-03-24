<div class="row">

    <div class="col-md-6">
        <table class="table table-bordered">
            <tr>
                <th>{{ __('report::general.total_purchase') }}:</th>
                <td><span class="display_currency" data-currency_symbol="true">{{ $data['total_purchase'] }}</span></td>
            </tr>
            <tr>
                <th>{{ __('report::general.total_purchase_discount') }}:</th>
                <td><span class="display_currency"
                        data-currency_symbol="true">{{ $data['total_purchase_discount'] }}</span>
                </td>
            </tr>
            <tr>
                <th>{{ __('report::general.total_purchase_return') }}:</th>
                <td><span class="display_currency" data-currency_symbol="true">{{ $data['total_purchase_return'] }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="col-md-6">
        <table class="table table-bordered">
            <tr>
                <th>{{ __('report::general.total_sell') }}:</th>
                <td><span class="display_currency" data-currency_symbol="true">{{ $data['total_sell'] }}</span></td>
            </tr>
            <tr>
                <th>{{ __('report::general.total_sell_discount') }}:</th>
                <td><span class="display_currency" data-currency_symbol="true">{{ $data['total_sell_discount'] }}</span>
                </td>
            </tr>
            <tr>
                <th>{{ __('report::general.total_sell_return') }}:</th>
                <td><span class="display_currency" data-currency_symbol="true">{{ $data['total_sell_return'] }}</span>
                </td>
            </tr>
        </table>
    </div>
</div>
<div class="col-xs-12">
    <h3>{{ __('report::general.gross_profit') }}:  {{ $data['gross_profit'] }}</h3>
    {{-- <p><span class="display_currency" data-currency_symbol="true"></span></p> --}}
    <p class="text-muted py-2">
        @lang('report::general.gross_profit_calculation')
    </p>

    <h3 class="text- mb-0">
        {{ __('report::general.net_profit') }}:
        <span class="display_currency" data-currency_symbol="true">{{$data['net_profit']}}</span>
    </h3>
    <p class="text-muted py-2">
        @lang('report::general.net_profit_calculation')
    </p>


</div>
