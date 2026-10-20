<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' || session('locale') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@lang('general::general.saved_card_add')</title>
    <style>
        html, body { margin: 0; height: 100%; background: #fff; font-family: Cairo, system-ui, sans-serif; }
        .wrap { position: relative; width: 100%; height: 100%; min-height: 540px; }
        iframe { display: block; width: 100%; height: 100%; min-height: 540px; border: 0; background: #fff; }
        .overlay {
            position: absolute; inset: 0; display: grid; place-items: center;
            background: rgba(255,255,255,0.96); z-index: 2; transition: opacity .2s ease;
        }
        .overlay.is-hidden { opacity: 0; pointer-events: none; }
        .spin {
            width: 34px; height: 34px; border-radius: 50%;
            border: 3px solid rgba(235,184,30,.25); border-top-color: #ebb81e;
            animation: spin .7s linear infinite; margin: 0 auto;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        p { margin: .7rem 0 0; font-weight: 700; color: #6b7280; font-size: .88rem; text-align: center; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="overlay" id="overlay">
            <div>
                <div class="spin" aria-hidden="true"></div>
                <p>@lang('general::general.subscription_manage_loading')</p>
            </div>
        </div>
        <iframe id="frame" src="{{ $handoffUrl }}" title="@lang('general::general.saved_card_add')" allow="payment *"></iframe>
    </div>
    <script>
        (function () {
            var frame = document.getElementById('frame');
            var overlay = document.getElementById('overlay');
            var hide = function () { overlay.classList.add('is-hidden'); };
            frame.addEventListener('load', hide);
            setTimeout(hide, 9000);
        })();
    </script>
</body>
</html>
