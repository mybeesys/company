<!DOCTYPE html>
<html lang="{{ $localeAr ? 'ar' : 'en' }}" dir="{{ $localeAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $localeAr ? 'فاتورتك' : 'Your invoice' }} — {{ $transaction->ref_no }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1a1a1a;
            --muted: #6b6b6b;
            --line: #ececec;
            --bee: #e9b71f;
            --bee-deep: #c99a12;
            --surface: #fffdf7;
            --paper: #ffffff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: {{ $localeAr ? "'IBM Plex Sans Arabic', sans-serif" : "'Manrope', sans-serif" }};
            color: var(--ink);
            background:
                radial-gradient(1200px 600px at 10% -10%, rgba(233, 183, 31, 0.18), transparent 55%),
                radial-gradient(900px 500px at 100% 0%, rgba(233, 183, 31, 0.10), transparent 50%),
                linear-gradient(180deg, #fffaf0 0%, #f7f4ea 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 16px;
        }
        .sheet {
            width: min(520px, 100%);
            background: var(--paper);
            border: 1px solid var(--line);
            border-top: 4px solid var(--bee);
            padding: 36px 32px 28px;
            box-shadow: 0 18px 50px rgba(26, 26, 26, 0.06);
        }
        .logo {
            text-align: center;
            margin-bottom: 18px;
            min-height: 56px;
        }
        .logo img {
            max-height: 64px;
            max-width: 180px;
            object-fit: contain;
        }
        .brand {
            text-align: center;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0 0 6px;
        }
        .eyebrow {
            text-align: center;
            color: var(--muted);
            font-size: 0.92rem;
            margin: 0 0 28px;
        }
        .meta {
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            padding: 18px 0;
            margin-bottom: 28px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 8px 0;
            font-size: 0.95rem;
        }
        .row .k { color: var(--muted); }
        .row .v { font-weight: 600; text-align: end; }
        .amount .v {
            font-size: 1.25rem;
            color: var(--ink);
        }
        .cta {
            display: block;
            width: 100%;
            text-align: center;
            text-decoration: none;
            background: var(--bee);
            color: #1a1a1a;
            font-weight: 700;
            font-size: 1.05rem;
            padding: 14px 18px;
            border: 0;
            transition: background 0.15s ease, transform 0.15s ease;
        }
        .cta:hover { background: var(--bee-deep); color: #111; }
        .cta:active { transform: translateY(1px); }
        .note {
            margin-top: 18px;
            text-align: center;
            color: var(--muted);
            font-size: 0.82rem;
            line-height: 1.55;
        }
        .foot {
            margin-top: 28px;
            text-align: center;
            font-size: 0.75rem;
            color: #9a9a9a;
        }
    </style>
</head>
<body>
    <main class="sheet">
        <div class="logo">
            @if (!empty($logoSrc))
                <img src="{{ $logoSrc }}" alt="{{ $companyName }}">
            @endif
        </div>
        <h1 class="brand">{{ $companyName }}</h1>
        <p class="eyebrow">
            {{ $localeAr ? 'فاتورتك جاهزة للتحميل' : 'Your invoice is ready to download' }}
        </p>

        <div class="meta">
            <div class="row">
                <span class="k">{{ $localeAr ? 'رقم الفاتورة' : 'Invoice No.' }}</span>
                <span class="v" dir="ltr">{{ $transaction->ref_no }}</span>
            </div>
            <div class="row">
                <span class="k">{{ $localeAr ? 'العميل' : 'Customer' }}</span>
                <span class="v">{{ $customerName }}</span>
            </div>
            <div class="row">
                <span class="k">{{ $localeAr ? 'التاريخ' : 'Date' }}</span>
                <span class="v" dir="ltr">{{ $issueDate }}</span>
            </div>
            <div class="row amount">
                <span class="k">{{ $localeAr ? 'المبلغ' : 'Amount' }}</span>
                <span class="v" dir="ltr">{{ $amount }} SAR</span>
            </div>
        </div>

        <a class="cta" href="{{ $pdfUrl }}">
            {{ $localeAr ? 'تحميل الفاتورة PDF' : 'Download invoice PDF' }}
        </a>

        <p class="note">
            {{ $localeAr
                ? 'يمكنك تحميل الفاتورة دون تسجيل دخول. الرابط آمن وينتهي في '.$expiresAt.'.'
                : 'You can download this invoice without signing in. This secure link expires on '.$expiresAt.'.' }}
        </p>
        <p class="foot">{{ $companyName }}</p>
    </main>
</body>
</html>
