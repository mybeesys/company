@php
    $local = app()->currentLocale();
    $dir = $local === 'ar' ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ $local }}" dir="{{ $dir }}" class="dashboard-embed-root" style="direction: {{ $dir }}">
<head>
    <title>@hasSection('title')@yield('title')@endif</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.css-references')
    @yield('css')
    <style>
        html.dashboard-embed-root,
        body.dashboard-embed-body {
            background: #f5f8fa;
            margin: 0;
            padding: 0;
            height: auto !important;
            min-height: 0 !important;
            overflow: visible !important;
        }
    </style>
</head>
<body class="dashboard-embed-body">
    @yield('content')
    @include('layouts.js-references')
    @yield('script')
    <script>
        (function () {
            function reportEmbedHeight() {
                var height = Math.max(
                    document.body.scrollHeight,
                    document.documentElement.scrollHeight,
                    document.body.offsetHeight,
                    document.documentElement.offsetHeight
                );
                if (window.parent !== window) {
                    window.parent.postMessage(
                        { type: 'dashboard-embed-height', height: height },
                        window.location.origin
                    );
                }
            }

            window.addEventListener('load', reportEmbedHeight);
            window.addEventListener('resize', reportEmbedHeight);

            if (typeof ResizeObserver !== 'undefined' && document.body) {
                new ResizeObserver(reportEmbedHeight).observe(document.body);
            }

            [400, 1200, 2500].forEach(function (ms) {
                setTimeout(reportEmbedHeight, ms);
            });
        })();
    </script>
</body>
</html>
