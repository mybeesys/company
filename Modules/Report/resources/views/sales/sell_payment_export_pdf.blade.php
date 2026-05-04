<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $meta['title'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #111827;
            margin: 0;
            padding: 8px;
            line-height: 1.35;
        }
        .spay-pdf-hero {
            background: #1e3a8a;
            color: #fff;
            padding: 10px 12px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .spay-pdf-hero h1 {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: 700;
        }
        .spay-pdf-hero .sub {
            opacity: 0.92;
            font-size: 8px;
        }
        .spay-pdf-meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8px;
        }
        .spay-pdf-meta td {
            padding: 4px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .spay-pdf-meta .k {
            font-weight: 700;
            width: 18%;
            background: #f1f5f9;
        }
        .spay-pdf-meta .v.filters {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        table.spay-pdf-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 6px;
        }
        table.spay-pdf-grid th,
        table.spay-pdf-grid td {
            border: 1px solid #cbd5e1;
            padding: 2px 3px;
            vertical-align: middle;
        }
        table.spay-pdf-grid thead th {
            background: #e5e7eb;
            font-weight: 700;
            text-align: center;
        }
        table.spay-pdf-grid tbody tr:nth-child(even) { background: #f9fafb; }
        table.spay-pdf-grid tbody td { text-align: center; }
        table.spay-pdf-grid tbody td.text-start {
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }
    </style>
</head>
<body>
    <div class="spay-pdf-hero">
        <h1>{{ $meta['title'] }}</h1>
        <div class="sub">{{ __('report::general.export_generated_at') }}: {{ $meta['generated_at'] }} — {{ __('report::general.export_row_count', ['count' => $meta['row_count']]) }}</div>
    </div>

    <table class="spay-pdf-meta">
        <tr>
            <td class="k">{{ __('report::general.export_filters_heading') }}</td>
            <td class="v filters">{{ $meta['filters'] }}</td>
        </tr>
    </table>

    <table class="spay-pdf-grid">
        <thead>
            <tr>
                @foreach ($headers as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $r)
                <tr>
                    @foreach ($r as $idx => $cell)
                        <td class="{{ in_array($idx, $textStartColIndexes ?? [], true) ? 'text-start' : '' }}">{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
