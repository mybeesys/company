@php
    $fmt = fn ($v) => number_format((float) $v, 2);
@endphp

<div class="row g-5" id="profit-loss-summary">
    <div class="col-lg-4">
        <div class="card border border-gray-200 h-100 shadow-sm">
            <div class="card-header border-0 pt-5 pb-3">
                <h3 class="card-title fw-bold text-gray-800 fs-5 mb-0">
                    <i class="fas fa-shopping-cart text-primary me-2"></i>
                    @lang('report::general.profit_loss_purchases_section')
                </h3>
                <span class="text-muted fs-8">@lang('report::general.amount_before_tax')</span>
            </div>
            <div class="card-body pt-0">
                <table class="table table-row-dashed table-borderless gy-4 mb-0 pl-summary-table">
                    <tr>
                        <th class="text-muted fw-semibold ps-0">@lang('report::general.total_purchase')</th>
                        <td class="text-end fw-bold tabular-nums pe-0">{{ $fmt($data['total_purchase']) }} @get_format_currency()</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-semibold ps-0">@lang('report::general.total_purchase_discount')</th>
                        <td class="text-end tabular-nums pe-0 text-danger">− {{ $fmt($data['total_purchase_discount']) }} @get_format_currency()</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-semibold ps-0">@lang('report::general.total_purchase_return')</th>
                        <td class="text-end tabular-nums pe-0 text-danger">− {{ $fmt($data['total_purchase_return']) }} @get_format_currency()</td>
                    </tr>
                    <tr class="border-top">
                        <th class="fw-bold text-gray-800 ps-0">@lang('report::general.net_purchases')</th>
                        <td class="text-end fw-bold tabular-nums pe-0">{{ $fmt($data['net_purchases']) }} @get_format_currency()</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border border-gray-200 h-100 shadow-sm">
            <div class="card-header border-0 pt-5 pb-3">
                <h3 class="card-title fw-bold text-gray-800 fs-5 mb-0">
                    <i class="fas fa-chart-line text-warning me-2"></i>
                    @lang('report::general.profit_loss_sales_section')
                </h3>
                <span class="text-muted fs-8">@lang('report::general.amount_before_tax')</span>
            </div>
            <div class="card-body pt-0">
                <table class="table table-row-dashed table-borderless gy-4 mb-0 pl-summary-table">
                    <tr>
                        <th class="text-muted fw-semibold ps-0">@lang('report::general.total_sell')</th>
                        <td class="text-end fw-bold tabular-nums pe-0">{{ $fmt($data['total_sell']) }} @get_format_currency()</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-semibold ps-0">@lang('report::general.total_sell_discount')</th>
                        <td class="text-end tabular-nums pe-0 text-danger">− {{ $fmt($data['total_sell_discount']) }} @get_format_currency()</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-semibold ps-0">@lang('report::general.total_sell_return')</th>
                        <td class="text-end tabular-nums pe-0 text-danger">− {{ $fmt($data['total_sell_return']) }} @get_format_currency()</td>
                    </tr>
                    <tr class="border-top">
                        <th class="fw-bold text-gray-800 ps-0">@lang('report::general.net_sales')</th>
                        <td class="text-end fw-bold tabular-nums pe-0">{{ $fmt($data['net_sales']) }} @get_format_currency()</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border border-gray-200 h-100 shadow-sm bg-light-success">
            <div class="card-header border-0 pt-5 pb-3 bg-transparent">
                <h3 class="card-title fw-bold text-gray-800 fs-5 mb-0">
                    <i class="fas fa-coins text-success me-2"></i>
                    @lang('report::general.profit_loss_profit_section')
                </h3>
            </div>
            <div class="card-body pt-0">
                <div class="mb-5">
                    <div class="text-muted fs-7 mb-1">@lang('report::general.gross_profit')</div>
                    <div class="fs-2 fw-bold text-success tabular-nums">{{ $fmt($data['gross_profit']) }} @get_format_currency()</div>
                    <div class="text-muted fs-8 mt-2">@lang('report::general.gross_profit_calculation')</div>
                </div>
                <div class="border-top pt-4">
                    <div class="text-muted fs-7 mb-1">@lang('report::general.net_profit')</div>
                    <div class="fs-2 fw-bold tabular-nums {{ $data['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $fmt($data['net_profit']) }} @get_format_currency()
                    </div>
                    <div class="text-muted fs-8 mt-2">@lang('report::general.net_profit_calculation')</div>
                </div>
            </div>
        </div>
    </div>
</div>
