<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @include('accounting::settings.partials.fiscal_report._styles')
    <style>
        body { font-family: DejaVu Sans, 'Segoe UI', Tahoma, sans-serif; font-size: 11px; margin: 16px; }
    </style>
</head>
<body onload="window.print()">
    @include('accounting::settings.partials.fiscal_report._body', ['report' => $report, 'title' => $title])
</body>
</html>
