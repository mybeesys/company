<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' || session('locale') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@lang('general::general.saved_card_add')</title>
    <style>
        html, body { margin: 0; height: 100%; overflow: hidden; background: #fff; }
        .wrap { position: relative; width: 100%; height: 100%; min-height: 480px; }
        iframe {
            display: block;
            width: 100%;
            height: 100%;
            min-height: 480px;
            border: 0;
            background: #fff;
        }
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
        <iframe id="frame" src="{{ $handoffUrl }}" title="@lang('general::general.saved_card_add')" allow="payment *"></iframe>
    </div>
    <script>
        (function () {
            var frame = document.getElementById('frame');
            var overlay = document.getElementById('overlay');
            var wrap = document.querySelector('.wrap');

            function hide() { overlay.classList.add('is-hidden'); }

            function applyHeight(h) {
                h = Math.max(460, Math.min(720, Number(h) || 0));
                frame.style.height = h + 'px';
                wrap.style.minHeight = h + 'px';
                try {
                    window.parent.postMessage({ type: 'vault-card-height', height: h + 8 }, '*');
                } catch (e) {}
            }

            frame.addEventListener('load', hide);
            setTimeout(hide, 7000);

            window.addEventListener('message', function (event) {
                var data = event.data || {};
                if (data && data.type === 'vault-card-height' && data.height) {
                    hide();
                    applyHeight(data.height);
                }
            });
        })();
    </script>
</body>
</html>
