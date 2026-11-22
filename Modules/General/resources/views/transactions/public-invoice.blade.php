<!DOCTYPE html>
<html lang="{{ $localeAr ? 'ar' : 'en' }}" dir="{{ $localeAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $localeAr ? 'تحميل الفاتورة' : 'Download invoice' }} — {{ $transaction->ref_no }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #111b21;
            --muted: #667781;
            --line: #e9edef;
            --wa: #25d366;
            --wa-deep: #1da851;
            --bubble: #ffffff;
            --page: #efeae2;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: {{ $localeAr ? "'IBM Plex Sans Arabic', sans-serif" : "'Manrope', sans-serif" }};
            color: var(--ink);
            background: var(--page);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 14px;
        }
        .card {
            width: min(420px, 100%);
            background: var(--bubble);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(17, 27, 33, 0.08);
        }
        .head {
            padding: 22px 22px 14px;
            text-align: center;
            border-bottom: 1px solid var(--line);
        }
        .logo img {
            max-height: 52px;
            max-width: 160px;
            object-fit: contain;
        }
        .brand {
            margin: 10px 0 0;
            font-size: 1.15rem;
            font-weight: 700;
        }
        .body {
            padding: 18px 22px 8px;
        }
        .msg {
            margin: 0 0 14px;
            font-size: 0.95rem;
            line-height: 1.55;
            color: var(--ink);
        }
        .meta {
            margin: 0 0 8px;
            font-size: 0.88rem;
            color: var(--muted);
            line-height: 1.6;
        }
        .meta strong { color: var(--ink); font-weight: 700; }
        .actions {
            border-top: 1px solid var(--line);
            background: #f7f8fa;
        }
        .dl-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px 18px;
            text-decoration: none;
            color: var(--wa-deep);
            font-weight: 700;
            font-size: 1.05rem;
            background: transparent;
            border: 0;
            cursor: pointer;
        }
        .dl-btn:hover { background: #eef8f1; color: #14833d; }
        .dl-btn .arrow {
            font-size: 1.15rem;
            line-height: 1;
            transform: {{ $localeAr ? 'scaleX(-1)' : 'none' }};
        }
        .divider { height: 1px; background: var(--line); }
        .note {
            padding: 12px 22px 18px;
            text-align: center;
            color: var(--muted);
            font-size: 0.78rem;
            line-height: 1.5;
        }
        .status {
            display: none;
            text-align: center;
            color: var(--wa-deep);
            font-size: 0.85rem;
            margin: 0 0 10px;
        }
        .status.is-on { display: block; }
    </style>
</head>
<body>
    <main class="card">
        <div class="head">
            @if (!empty($logoSrc))
                <div class="logo"><img src="{{ $logoSrc }}" alt="{{ $companyName }}"></div>
            @endif
            <h1 class="brand">{{ $companyName }}</h1>
        </div>

        <div class="body">
            <p class="msg">
                {{ $localeAr
                    ? 'لتحميل الفاتورة يرجى الضغط على تحميل'
                    : 'To download the invoice, please click on Download' }}
            </p>
            <p class="meta">
                {{ $localeAr ? 'رقم الفاتورة' : 'Invoice' }}:
                <strong dir="ltr">{{ $transaction->ref_no }}</strong><br>
                {{ $localeAr ? 'المبلغ' : 'Amount' }}:
                <strong dir="ltr">{{ $amount }} SAR</strong>
            </p>
            <p id="dlStatus" class="status {{ !empty($autoDownload) ? 'is-on' : '' }}">
                {{ $localeAr ? 'جاري بدء التحميل…' : 'Starting download…' }}
            </p>
        </div>

        <div class="actions">
            <a class="dl-btn" id="downloadBtn" href="{{ $pdfUrl }}" rel="noopener" download>
                <span>{{ $localeAr ? 'تحميل / Download' : 'Download / تحميل' }}</span>
                <span class="arrow" aria-hidden="true">↪️</span>
            </a>
        </div>

        <div class="divider"></div>
        <p class="note">
            {{ $localeAr
                ? 'سيتم تحميل ملف PDF فوراً. الرابط آمن وينتهي في '.$expiresAt.'.'
                : 'The PDF downloads immediately. This secure link expires on '.$expiresAt.'.' }}
        </p>
    </main>

    <script>
        (function () {
            var pdfUrl = @json($pdfUrl);
            var auto = @json((bool) ($autoDownload ?? false));
            if (!auto || !pdfUrl) return;

            function startDownload() {
                // Prefer a real download click so the CTA page stays open for retry.
                var a = document.createElement('a');
                a.href = pdfUrl;
                a.setAttribute('download', '');
                a.rel = 'noopener';
                a.style.display = 'none';
                document.body.appendChild(a);
                a.click();
                a.remove();

                // Fallback for WebViews (WhatsApp in-app browser) that ignore download=.
                var frame = document.createElement('iframe');
                frame.style.display = 'none';
                frame.src = pdfUrl;
                document.body.appendChild(frame);
            }

            if (document.readyState === 'complete') {
                startDownload();
            } else {
                window.addEventListener('load', startDownload, { once: true });
            }
        })();
    </script>
</body>
</html>
