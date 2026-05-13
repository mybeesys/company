<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $meta['title'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #111827;
            margin: 0;
            padding: 8px;
            line-height: 1.35;
        }
        .wsr-pdf-hero {
            background: linear-gradient(90deg, #1e3a8a 0%, #312e81 100%);
            color: #fff;
            padding: 12px 14px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .wsr-pdf-hero h1 {
            margin: 0 0 4px 0;
            font-size: 15px;
            font-weight: 700;
        }
        .wsr-pdf-meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            background: #f8fafc;
        }
        .wsr-pdf-meta td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .wsr-pdf-meta .k {
            font-weight: 700;
            width: 22%;
            background: #f1f5f9;
            color: #334155;
            white-space: nowrap;
        }
        .wsr-pdf-meta .v {
            color: #0f172a;
        }
        .wsr-pdf-filters .v {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        table.wsr-pdf-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 6px;
        }
        table.wsr-pdf-grid th,
        table.wsr-pdf-grid td {
            border: 1px solid #cbd5e1;
            padding: 2px 3px;
            vertical-align: middle;
        }
        table.wsr-pdf-grid thead th {
            background: #e5e7eb;
            font-weight: 700;
            text-align: center;
        }
        table.wsr-pdf-grid thead tr.wsr-h2 th {
            background: #f1f5f9;
            font-size: 5.5px;
        }
        table.wsr-pdf-grid tbody tr:nth-child(even) { background: #f9fafb; }
        table.wsr-pdf-grid tbody td { text-align: center; }
        table.wsr-pdf-grid tbody td.text-start { text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; }
        .wsr-pdf-foot {
            margin-top: 8px;
            font-size: 7px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="wsr-pdf-hero">
        <h1>{{ $meta['title'] }}</h1>
        <div>{{ __('report::general.export_generated_at') }}: {{ $meta['generated_at'] }}</div>
    </div>

    <table class="wsr-pdf-meta">
        <tr>
            <td class="k">{{ __('report::general.weekday_report_kpi_period_note') }}</td>
            <td class="v">{{ $meta['period_line'] }}</td>
        </tr>
        <tr>
            <td class="k">{{ __('report::general.weekday_export_selected_days') }}</td>
            <td class="v">{{ $meta['weekdays_line'] ?? '' }}</td>
        </tr>
        <tr class="wsr-pdf-filters">
            <td class="k">{{ __('report::general.export_filters_heading') }}</td>
            <td class="v">{{ $meta['filters'] }}</td>
        </tr>
    </table>

    <div class="table-responsive">
        <table class="wsr-pdf-grid">
            <thead>
                <tr>
                    <th rowspan="2">{{ __('report::fields.product_name') }}</th>
                    <th rowspan="2">{{ __('report::fields.establishment_name') }}</th>
                    <th rowspan="2">{{ __('report::general.filter_panel_unit') }}</th>
                    @foreach($dates as $dm)
                    <th colspan="2">{{ $dm['label'] ?? ($dm['date'] ?? '') }}</th>
                    @endforeach
                </tr>
                <tr class="wsr-h2">
                    @foreach($dates as $_)
                    <th>{{ __('report::general.weekday_simple_grid_col_qty') }}</th>
                    <th>{{ __('report::general.weekday_simple_grid_col_price') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td class="text-start">{{ $row['product_name'] ?? '—' }}</td>
                    <td class="text-start">{{ $row['establishment_name'] ?? '—' }}</td>
                    <td class="text-start">{{ $row['unit_label'] ?? '—' }}</td>
                    @php $cells = $row['cells'] ?? []; @endphp
                    @foreach($dates as $dm)
                        @php
                            $d = $dm['date'] ?? '';
                            $c = $cells[$d] ?? ['qty' => 0, 'unit_sale_price' => null];
                            $q = (float) ($c['qty'] ?? 0);
                            $p = $c['unit_sale_price'] ?? null;
                        @endphp
                    <td>{{ number_format($q, 3, '.', '') }}</td>
                    <td>{{ ($p !== null && is_numeric($p)) ? number_format((float) $p, 2, '.', '') : '—' }}</td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 3 + 2 * count($dates) }}" style="text-align:center;color:#64748b;">—</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="wsr-pdf-foot">
        {{ __('report::general.export_row_count', ['count' => (int) ($meta['row_count'] ?? 0)]) }}
    </div>
</body>
</html>
