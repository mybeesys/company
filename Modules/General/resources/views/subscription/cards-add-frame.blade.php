<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' || session('locale') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@lang('general::general.saved_card_add')</title>
    <style>
        html, body { margin: 0; height: 100%; overflow: hidden; background: #fff; }
        .wrap { position: relative; width: 100%; height: 100%; min-height: 420px; }
        iframe { display: block; width: 100%; height: 100%; min-height: 420px; border: 0; background: #fff; }
        .overlay {
            position: absolute; inset: 0; display: grid; place-items: center;
            background: #fff; z-index: 2; transition: opacity .18s ease;
        }
        .overlay.is-hidden { opacity: 0; pointer-events: none; }
        .spin {
            width: 28px; height: 28px; border-radius: 50%;
            border: 2.5px solid rgba(235,184,30,.28); border-top-color: #ebb81e;
            animation: spin .65s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="overlay" id="overlay"><div class="spin" aria-hidden="true"></div></div>
        <iframe id="frame" src="{{ $handoffUrl }}" title="@lang('general::general.saved_card_add')" allow="payment *" scrolling="no"></iframe>
    </div>
    <script>
        (function () {
            var frame = document.getElementById('frame');
            var overlay = document.getElementById('overlay');
            var hide = function () { overlay.classList.add('is-hidden'); };
            frame.addEventListener('load', hide);
            setTimeout(hide, 6000);
        })();
    </script>
</body>
</html>
