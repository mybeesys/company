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
            max-height: none !important;
            overflow: hidden !important;
        }
        #embed-content-root {
            display: block;
            width: 100%;
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
            overflow: visible;
        }
    </style>
</head>
<body class="dashboard-embed-body">
    <div id="embed-content-root">
        @yield('content')
    </div>
    @include('layouts.js-references')
    @yield('script')
    <script>
        (function () {
            var reportTimer = null;
            var lastReported = 0;

            function measureEmbedHeight() {
                var root = document.getElementById('embed-content-root');
                if (!root) {
                    return 0;
                }

                var rect = root.getBoundingClientRect();
                var height = Math.max(rect.height, root.scrollHeight);

                return Math.ceil(height) + 12;
            }

            function reportEmbedHeight() {
                if (window.parent === window) {
                    return;
                }

                clearTimeout(reportTimer);
                reportTimer = setTimeout(function () {
                    var height = measureEmbedHeight();
                    if (height < 80 || Math.abs(height - lastReported) < 6) {
                        return;
                    }
                    lastReported = height;
                    window.parent.postMessage(
                        { type: 'dashboard-embed-height', height: height },
                        window.location.origin
                    );
                }, 80);
            }

            window.reportEmbedHeight = reportEmbedHeight;

            window.addEventListener('load', reportEmbedHeight);

            var rootEl = document.getElementById('embed-content-root');
            if (typeof ResizeObserver !== 'undefined' && rootEl) {
                new ResizeObserver(reportEmbedHeight).observe(rootEl);
            }

            if (window.MutationObserver && rootEl) {
                new MutationObserver(function () {
                    reportEmbedHeight();
                }).observe(rootEl, { childList: true, subtree: true });
            }

            if (window.jQuery) {
                jQuery(document).on(
                    'init.dt draw.dt shown.bs.collapse hidden.bs.collapse shown.bs.modal hidden.bs.modal',
                    reportEmbedHeight
                );
            }

            [150, 600, 1500, 3000].forEach(function (ms) {
                setTimeout(reportEmbedHeight, ms);
            });
        })();
    </script>
</body>
</html>
