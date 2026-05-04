<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.periodic_inventory_log') }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #222; }
        .header { margin-bottom: 12px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .sub { color: #555; font-size: 11px; }
        .meta { margin: 8px 0 14px; width: 100%; border-collapse: collapse; }
        .meta td { padding: 4px 6px; border: 1px solid #ddd; }
        .tbl { width: 100%; border-collapse: collapse; }
        .tbl th, .tbl td { border: 1px solid #ddd; padding: 6px; text-align: center; }
        .tbl th { background: #f5f7fb; }
        .note { margin-top: 12px; color: #666; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ __('accounting::lang.periodic_inventory_log') }} #{{ $inventory->id }}</div>
        <div class="sub">{{ __('accounting::lang.periodic_inventory_workflow_subtitle') }}</div>
    </div>

    <table class="meta">
        <tr>
            <td>{{ __('accounting::lang.period') }}</td>
            <td>{{ $inventory->start_date }} {{ __('accounting::lang.to') }} {{ $inventory->end_date }}</td>
            <td>{{ __('accounting::lang.establishment_name') }}</td>
            <td>{{ $inventory->establishment?->name ?? '--' }}</td>
        </tr>
        <tr>
            <td>{{ __('accounting::lang.created_by_user') }}</td>
            <td>{{ $inventory->creator?->name ?? '--' }}</td>
            <td>{{ __('accounting::lang.period_status') }}</td>
            <td>{{ __('accounting::lang.approved') }}</td>
        </tr>
        <tr>
            <td>{{ __('accounting::lang.opening_value') }}</td>
            <td>{{ number_format((float) $inventory->opening_stock_value, 2) }}</td>
            <td>{{ __('accounting::lang.purchases_value') }}</td>
            <td>{{ number_format((float) $inventory->purchases_value, 2) }}</td>
        </tr>
        <tr>
            <td>{{ __('accounting::lang.closing_value') }}</td>
            <td>{{ number_format((float) $inventory->closing_stock_value, 2) }}</td>
            <td>{{ __('accounting::lang.cogs_value') }}</td>
            <td>{{ number_format((float) $inventory->cogs, 2) }}</td>
        </tr>
    </table>

    <table class="tbl">
        <thead>
            <tr>
                <th>{{ __('accounting::lang.product') }}</th>
                <th>{{ __('accounting::lang.unit') }}</th>
                <th>{{ __('accounting::lang.system_quantity') }}</th>
                <th>{{ __('accounting::lang.physical_quantity') }}</th>
                <th>{{ __('accounting::lang.cost_price') }}</th>
                <th>{{ __('accounting::lang.difference') }}</th>
                <th>{{ __('accounting::lang.total_variance_value') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inventory->items as $item)
                @php
                    $v = (float) $item->variance;
                    $vv = $v * (float) $item->unit_cost;
                @endphp
                <tr>
                    <td>{{ $item->product?->name_ar ?? $item->product?->name_en ?? ('#'.$item->product_id) }}</td>
                    <td>{{ $item->unit_label ?? '—' }}</td>
                    <td>{{ number_format((float) $item->system_quantity, 2) }}</td>
                    <td>{{ number_format((float) $item->physical_quantity, 2) }}</td>
                    <td>{{ number_format((float) $item->unit_cost, 2) }}</td>
                    <td>{{ number_format($v, 2) }}</td>
                    <td>{{ number_format($vv, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">{{ __('accounting::lang.no_products') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="note">
        {{ $inventory->adjustment_entry_id ? __('accounting::lang.with_adjustment') : __('accounting::lang.no_adjustment_needed') }}
    </div>
</body>
</html>
