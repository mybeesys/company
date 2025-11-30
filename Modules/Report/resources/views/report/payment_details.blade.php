<div class="row">
    <div class="col-sm-12">
        <table class="table table-condensed">
            <tr>
                <th>@lang('report::fields.payment_method')</th>
                <th>@lang('report::fields.sale')</th>
                {{-- <th>@lang('report::fields.expense')</th> --}}
                <th>@lang('report::fields.refund')</th>
            </tr>

            @foreach($payment_types as $method)
                @php
                    $sale_field = 'total_' . $method->name_en;
                    $expense_field = 'total_' . $method->name_en . '_expense';
                    $refund_field = 'total_' . $method->name_en . '_refund';
                @endphp
                <tr>
                    <td>{{ $method->name_ar }}</td>
                    <td>
                        <span class="display_currency" data-currency_symbol="true">
                            {{ $register_details->$sale_field ?? 0 }}
                        </span>
                    </td>
                    {{-- <td>
                        <span class="display_currency" data-currency_symbol="true">
                            {{ $register_details->$expense_field ?? 0 }}
                        </span>
                    </td> --}}
                    <td>
                        <span class="display_currency" data-currency_symbol="true">
                            {{ $register_details->$refund_field ?? 0 }}
                        </span>
                    </td>
                </tr>
            @endforeach

            <tr class="success">
                <th>@lang('report::fields.total')</th>
                <td>
                    <span class="display_currency" data-currency_symbol="true">
                        {{ $register_details->total_sale ?? 0 }}
                    </span>
                </td>
                <td>
                    <span class="display_currency" data-currency_symbol="true">
                        {{ $register_details->total_expense ?? 0 }}
                    </span>
                </td>
                <td>
                    <span class="display_currency" data-currency_symbol="true">
                        {{ $register_details->total_refund ?? 0 }}
                    </span>
                </td>
            </tr>
        </table>
    </div>
</div>
