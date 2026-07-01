@php
    use Modules\Report\Support\RegisterShiftReport;

    $paymentRows = [];
    foreach ($payment_types as $method) {
        $saleField = RegisterShiftReport::PAYMENT_FIELD_MAP[$method->name_en] ?? null;
        $refundField = $saleField ? $saleField.'_refund' : null;
        $paymentRows[] = [
            'label' => app()->getLocale() === 'ar' ? $method->name_ar : $method->name_en,
            'sale' => $saleField ? (float) ($register_details->$saleField ?? 0) : 0.0,
            'refund' => $refundField ? (float) ($register_details->$refundField ?? 0) : 0.0,
        ];
    }
@endphp

<div class="table-responsive">
    <table class="table table-row-bordered table-hover align-middle rr-detail-table">
        <thead>
            <tr>
                <th>@lang('report::fields.payment_method')</th>
                <th class="text-end">@lang('report::fields.sale')</th>
                <th class="text-end">@lang('report::fields.refund')</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($paymentRows as $row)
                <tr>
                    <td class="fw-semibold text-gray-800">{{ $row['label'] }}</td>
                    <td class="text-end">@format_currency($row['sale'])</td>
                    <td class="text-end">@format_currency($row['refund'])</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>@lang('report::fields.total')</th>
                <th class="text-end">@format_currency($register_details->total_sale ?? 0)</th>
                <th class="text-end">@format_currency($register_details->total_refund ?? 0)</th>
            </tr>
        </tfoot>
    </table>
</div>
