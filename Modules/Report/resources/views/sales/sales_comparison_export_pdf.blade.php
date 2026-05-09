<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $meta['title'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #111827;
            margin: 0;
            padding: 8px;
            line-height: 1.35;
        }
        .sc-pdf-hero {
            background: linear-gradient(90deg, #1e3a8a 0%, #312e81 100%);
            color: #fff;
            padding: 14px 16px;
            border-radius: 6px;
            margin-bottom: 12px;
        }
        .sc-pdf-hero h1 {
            margin: 0 0 6px 0;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .sc-pdf-hero .sub {
            opacity: 0.92;
            font-size: 9px;
        }
        .sc-pdf-meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            background: #f8fafc;
            border-radius: 4px;
            overflow: hidden;
        }
        .sc-pdf-meta td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .sc-pdf-meta .k {
            font-weight: 700;
            width: 22%;
            background: #f1f5f9;
            color: #334155;
            white-space: nowrap;
        }
        .sc-pdf-meta .v {
            color: #0f172a;
        }
        .sc-pdf-filters .v {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .sc-pdf-table-wrap {
            margin-top: 8px;
        }
        table.sc-pdf-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5px;
        }
        table.sc-pdf-grid th,
        table.sc-pdf-grid td {
            border: 1px solid #cbd5e1;
            padding: 3px 4px;
            vertical-align: middle;
        }
        table.sc-pdf-grid thead th.sc-gh-context {
            background: #e5e7eb;
            color: #1f2937;
            font-weight: 700;
            text-align: center;
        }
        table.sc-pdf-grid thead th.sc-gh-p1 {
            background: #bfdbfe;
            color: #1e3a8a;
            font-weight: 700;
            text-align: center;
        }
        table.sc-pdf-grid thead th.sc-gh-p2 {
            background: #fed7aa;
            color: #7c2d12;
            font-weight: 700;
            text-align: center;
        }
        table.sc-pdf-grid thead th.sc-gh-var {
            background: #ddd6fe;
            color: #4c1d95;
            font-weight: 700;
            text-align: center;
        }
        table.sc-pdf-grid thead tr.sc-h2 th {
            background: #f1f5f9;
            font-size: 6px;
            font-weight: 700;
            text-align: center;
        }
        table.sc-pdf-grid tbody tr:nth-child(even) { background: #f9fafb; }
        table.sc-pdf-grid tbody td { text-align: center; }
        table.sc-pdf-grid tbody td.text-start { text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; }
        .sc-pdf-foot {
            margin-top: 10px;
            font-size: 8px;
            color: #64748b;
        }
        .sc-pdf-kpi {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 9px;
        }
        .sc-pdf-kpi th, .sc-pdf-kpi td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: center;
        }
        .sc-pdf-kpi th {
            background: #e0e7ff;
            color: #312e81;
            font-weight: 700;
        }
        .sc-pdf-kpi td {
            background: #fff;
            font-weight: 700;
            color: #0f172a;
        }
        .sc-pdf-note {
            font-size: 8px;
            color: #475569;
            margin-bottom: 10px;
            padding: 6px 8px;
            background: #f1f5f9;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="sc-pdf-hero">
        <h1>{{ $meta['title'] }}</h1>
        <div class="sub">{{ __('report::general.export_generated_at') }}: {{ $meta['generated_at'] }} — {{ __('report::general.export_row_count', ['count' => $meta['row_count']]) }}</div>
    </div>

    <table class="sc-pdf-meta">
        @if (! empty($meta['wsr_export_single_window']))
        <tr>
            <td class="k">{{ __('report::general.sales_comparison_period_a') }}</td>
            <td class="v">{{ $meta['period_a_line'] }}</td>
        </tr>
        @else
        <tr>
            <td class="k">{{ __('report::general.sales_comparison_period_a') }}</td>
            <td class="v">{{ $meta['period_a_line'] }}</td>
        </tr>
        <tr>
            <td class="k">{{ __('report::general.sales_comparison_period_b') }}</td>
            <td class="v">{{ $meta['period_b_line'] }}</td>
        </tr>
        @endif
        @if (! empty($meta['weekdays_line'] ?? null))
        <tr>
            <td class="k">{{ __('report::general.weekday_export_selected_days') }}</td>
            <td class="v">{{ $meta['weekdays_line'] }}</td>
        </tr>
        @endif
        <tr class="sc-pdf-filters">
            <td class="k">{{ __('report::general.export_filters_heading') }}</td>
            <td class="v">{{ $meta['filters'] }}</td>
        </tr>
    </table>

    @if (! empty($meta['wsr_export_single_window']))
    <div class="sc-pdf-note">{{ __('report::general.weekday_report_pdf_single_scope_note') }}</div>
    @endif

    @if (! empty($meta['wsr_kpi_pdf']) && is_array($meta['wsr_kpi_pdf']))
    <table class="sc-pdf-kpi">
        <thead>
            <tr>
                <th colspan="3">{{ __('report::general.weekday_report_pdf_kpi_title') }}</th>
            </tr>
            <tr>
                <th>{{ __('report::general.weekday_report_kpi_qty') }}</th>
                <th>{{ __('report::general.weekday_report_kpi_revenue') }}</th>
                <th>{{ __('report::general.weekday_report_kpi_lines') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $meta['wsr_kpi_pdf']['qty'] ?? '—' }}</td>
                <td>{{ $meta['wsr_kpi_pdf']['revenue'] ?? '—' }}</td>
                <td>{{ $meta['wsr_kpi_pdf']['lines'] ?? '—' }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <div class="sc-pdf-table-wrap">
        @if (! empty($meta['wsr_export_single_window']))
        <table class="sc-pdf-grid">
            <thead>
                <tr>
                    <th colspan="6" class="sc-gh-context">{{ __('report::general.sales_comparison_group_context') }}</th>
                    <th colspan="6" class="sc-gh-p1">{{ __('report::general.weekday_report_export_metrics_period') }}</th>
                </tr>
                <tr class="sc-h2">
                    <th>{{ __('report::fields.product_name') }}</th>
                    <th>{{ __('report::fields.category') }}</th>
                    <th>{{ __('report::fields.subcategory') }}</th>
                    <th>{{ __('report::fields.establishment_name') }}</th>
                    <th>{{ __('report::fields.SKU') }}</th>
                    <th>{{ __('report::fields.customer') }}</th>
                    <th>{{ __('report::fields.qty_period_a') }}</th>
                    <th>{{ __('report::fields.avg_unit_price_period_a') }}</th>
                    <th>{{ __('report::fields.discount_period_a') }}</th>
                    <th>{{ __('report::fields.tax_period_a') }}</th>
                    <th>{{ __('report::fields.subtotal_period_a') }}</th>
                    <th>{{ __('report::fields.lines_period_a') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="text-start">{{ $row['product_name'] }}</td>
                    <td class="text-start">{{ $row['category'] }}</td>
                    <td class="text-start">{{ $row['subcategory'] }}</td>
                    <td class="text-start">{{ $row['establishment_name'] }}</td>
                    <td class="text-start">{{ $row['SKU'] }}</td>
                    <td class="text-start">{{ $row['customer'] }}</td>
                    <td>{{ $row['qty_period_a'] }}</td>
                    <td>{{ $row['avg_unit_price_period_a'] }}</td>
                    <td>{{ $row['discount_period_a'] }}</td>
                    <td>{{ $row['tax_period_a'] }}</td>
                    <td>{{ $row['subtotal_period_a'] }}</td>
                    <td>{{ $row['lines_period_a'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <table class="sc-pdf-grid">
            <thead>
                <tr>
                    <th colspan="6" class="sc-gh-context">{{ __('report::general.sales_comparison_group_context') }}</th>
                    <th colspan="6" class="sc-gh-p1">{{ __('report::general.sales_comparison_group_period_a') }}</th>
                    <th colspan="6" class="sc-gh-p2">{{ __('report::general.sales_comparison_group_period_b') }}</th>
                    <th colspan="7" class="sc-gh-var">{{ __('report::general.sales_comparison_group_variance') }}</th>
                </tr>
                <tr class="sc-h2">
                    <th>{{ __('report::fields.product_name') }}</th>
                    <th>{{ __('report::fields.category') }}</th>
                    <th>{{ __('report::fields.subcategory') }}</th>
                    <th>{{ __('report::fields.establishment_name') }}</th>
                    <th>{{ __('report::fields.SKU') }}</th>
                    <th>{{ __('report::fields.customer') }}</th>
                    <th>{{ __('report::fields.qty_period_a') }}</th>
                    <th>{{ __('report::fields.avg_unit_price_period_a') }}</th>
                    <th>{{ __('report::fields.discount_period_a') }}</th>
                    <th>{{ __('report::fields.tax_period_a') }}</th>
                    <th>{{ __('report::fields.subtotal_period_a') }}</th>
                    <th>{{ __('report::fields.lines_period_a') }}</th>
                    <th>{{ __('report::fields.qty_period_b') }}</th>
                    <th>{{ __('report::fields.avg_unit_price_period_b') }}</th>
                    <th>{{ __('report::fields.discount_period_b') }}</th>
                    <th>{{ __('report::fields.tax_period_b') }}</th>
                    <th>{{ __('report::fields.subtotal_period_b') }}</th>
                    <th>{{ __('report::fields.lines_period_b') }}</th>
                    <th>{{ __('report::fields.qty_difference') }}</th>
                    <th>{{ __('report::fields.qty_change_percent') }}</th>
                    <th>{{ __('report::fields.subtotal_difference') }}</th>
                    <th>{{ __('report::fields.subtotal_change_percent') }}</th>
                    <th>{{ __('report::fields.discount_difference') }}</th>
                    <th>{{ __('report::fields.tax_difference') }}</th>
                    <th>{{ __('report::fields.lines_difference') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="text-start">{{ $row['product_name'] }}</td>
                    <td class="text-start">{{ $row['category'] }}</td>
                    <td class="text-start">{{ $row['subcategory'] }}</td>
                    <td class="text-start">{{ $row['establishment_name'] }}</td>
                    <td class="text-start">{{ $row['SKU'] }}</td>
                    <td class="text-start">{{ $row['customer'] }}</td>
                    <td>{{ $row['qty_period_a'] }}</td>
                    <td>{{ $row['avg_unit_price_period_a'] }}</td>
                    <td>{{ $row['discount_period_a'] }}</td>
                    <td>{{ $row['tax_period_a'] }}</td>
                    <td>{{ $row['subtotal_period_a'] }}</td>
                    <td>{{ $row['lines_period_a'] }}</td>
                    <td>{{ $row['qty_period_b'] }}</td>
                    <td>{{ $row['avg_unit_price_period_b'] }}</td>
                    <td>{{ $row['discount_period_b'] }}</td>
                    <td>{{ $row['tax_period_b'] }}</td>
                    <td>{{ $row['subtotal_period_b'] }}</td>
                    <td>{{ $row['lines_period_b'] }}</td>
                    <td>{{ $row['qty_difference'] }}</td>
                    <td>{{ $row['qty_change_percent'] }}</td>
                    <td>{{ $row['subtotal_difference'] }}</td>
                    <td>{{ $row['subtotal_change_percent'] }}</td>
                    <td>{{ $row['discount_difference'] }}</td>
                    <td>{{ $row['tax_difference'] }}</td>
                    <td>{{ $row['lines_difference'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <div class="sc-pdf-foot">{{ config('app.name') }}</div>
</body>
</html>
