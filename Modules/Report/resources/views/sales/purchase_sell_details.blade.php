@php
    $fmt = fn ($v) => number_format((float) $v, 2);
    $signed = function (float $v) use ($fmt) {
        if ($v > 0) {
            return '+'.$fmt($v);
        }
        if ($v < 0) {
            return '−'.$fmt(abs($v));
        }

        return $fmt(0);
    };
    $diffTotalClass = ($data['difference_total'] ?? 0) >= 0 ? 'text-success' : 'text-danger';
    $diffDueClass = ($data['difference_due'] ?? 0) >= 0 ? 'text-success' : 'text-danger';
@endphp

<div class="row g-5" id="purchase-sell-summary">
    <div class="col-lg-4">
        <div class="card border border-gray-200 h-100 shadow-sm">
            <div class="card-header border-0 pt-5 pb-3">
                <h3 class="card-title fw-bold text-gray-800 fs-5 mb-0">
                    <i class="fas fa-shopping-cart text-primary me-2"></i>
                    @lang('report::general.profit_loss_purchases_section')
                </h3>
                <span class="text-muted fs-8">@lang('report::general.amount_inc_tax')</span>
            </div>
            <div class="card-body pt-0">
                <table class="table table-row-dashed table-borderless gy-4 mb-0 ps-summary-table">
                    <tr>
                        <th class="text-muted fw-semibold ps-0">@lang('report::general.total_purchase_inc_tax')</th>
                        <td class="text-end fw-bold tabular-nums pe-0">{{ $fmt($data['total_purchase_inc_tax']) }} @get_format_currency()</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-semibold ps-0">@lang('report::general.total_purchase_return')</th>
                        <td class="text-end tabular-nums pe-0 text-danger">− {{ $fmt($data['total_purchase_return_inc_tax']) }} @get_format_currency()</td>
                    </tr>
                    <tr class="border-top">
                        <th class="fw-bold text-gray-800 ps-0">@lang('report::general.net_purchases')</th>
                        <td class="text-end fw-bold tabular-nums pe-0">{{ $fmt($data['net_purchases_inc_tax']) }} @get_format_currency()</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-semibold ps-0">@lang('report::general.purchase_due')</th>
                        <td class="text-end tabular-nums pe-0">{{ $fmt($data['purchase_due']) }} @get_format_currency()</td>
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
                <span class="text-muted fs-8">@lang('report::general.amount_inc_tax')</span>
            </div>
            <div class="card-body pt-0">
                <table class="table table-row-dashed table-borderless gy-4 mb-0 ps-summary-table">
                    <tr>
                        <th class="text-muted fw-semibold ps-0">@lang('report::general.total_sell_inc_tax')</th>
                        <td class="text-end fw-bold tabular-nums pe-0">{{ $fmt($data['total_sell_inc_tax']) }} @get_format_currency()</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-semibold ps-0">@lang('report::general.total_sell_return')</th>
                        <td class="text-end tabular-nums pe-0 text-danger">− {{ $fmt($data['total_sell_return_inc_tax']) }} @get_format_currency()</td>
                    </tr>
                    <tr class="border-top">
                        <th class="fw-bold text-gray-800 ps-0">@lang('report::general.net_sales')</th>
                        <td class="text-end fw-bold tabular-nums pe-0">{{ $fmt($data['net_sales_inc_tax']) }} @get_format_currency()</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-semibold ps-0">@lang('report::general.invoice_due')</th>
                        <td class="text-end tabular-nums pe-0">{{ $fmt($data['invoice_due']) }} @get_format_currency()</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border border-gray-200 h-100 shadow-sm bg-light-primary">
            <div class="card-header border-0 pt-5 pb-3 bg-transparent">
                <h3 class="card-title fw-bold text-gray-800 fs-5 mb-0">
                    <i class="bi bi-arrow-left-right text-primary me-2"></i>
                    @lang('report::general.purchase_sell_comparison_section')
                </h3>
                <span class="text-muted fs-8">@lang('report::general.purchase_sell_period_hint')</span>
            </div>
            <div class="card-body pt-0">
                <div class="mb-6">
                    <div class="text-muted fs-7 mb-1">@lang('report::general.difference_total')</div>
                    <div class="fs-2 fw-bold tabular-nums {{ $diffTotalClass }}">
                        {{ $signed((float) $data['difference_total']) }} @get_format_currency()
                    </div>
                    <div class="text-muted fs-8 mt-1">@lang('report::general.net_sales') − @lang('report::general.net_purchases')</div>
                </div>
                <div>
                    <div class="text-muted fs-7 mb-1">@lang('report::general.difference_due')</div>
                    <div class="fs-3 fw-bold tabular-nums {{ $diffDueClass }}">
                        {{ $signed((float) $data['difference_due']) }} @get_format_currency()
                    </div>
                    <div class="text-muted fs-8 mt-1">@lang('report::general.invoice_due') − @lang('report::general.purchase_due')</div>
                </div>
            </div>
        </div>
    </div>
</div>
