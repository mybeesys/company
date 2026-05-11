<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    <style>
        /* mPDF CSS support is limited: avoid CSS variables/selectors like :root */
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .page { padding: 10px 8px; }

        /* Clean official style: no colors */
        .header {
            padding: 14px 14px;
            margin-bottom: 14px;
        }
        .title { font-size: 18px; font-weight: 700; margin: 0 0 6px 0; }
        .meta { color: #374151; font-size: 12px; }
        .note { margin-top: 8px; color: #111827; }

        /* Cards grid */
        table.kv { width: 100%; border-collapse: collapse; }
        table.kv td { padding: 10px; vertical-align: top; }
        .box {
            width: 100%;
            padding: 12px 12px;
        }
        .box-title { font-size: 13px; font-weight: 700; margin-bottom: 6px; }
        .box-hint { color: #6B7280; font-size: 11px; margin-bottom: 8px; }
        .box-value { font-size: 12px; font-weight: 600; }
    </style>
</head>

<body>
    <div class="page">
        <div class="header">
            <div class="title">{{ $pageTitle }}</div>
            <div class="meta">
                {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                &nbsp;—&nbsp;
                {{ __('accounting::lang.amount') }}:
                <strong>{{ number_format((float) $amount, 2) }}</strong>
            </div>
            @if (!empty($note))
                <div class="note">{{ $note }}</div>
            @endif
        </div>

        <table class="kv">
            <tr>
                <td width="50%">
                    <div class="box">
                        <div class="box-title">{{ __('accounting::lang.account-debit') }}</div>
                        @if (!empty($debitHint))
                            <div class="box-hint">{{ $debitHint }}</div>
                        @endif
                        <div class="box-value">{{ $debitAccountLabel }}</div>
                    </div>
                </td>
                <td width="50%">
                    <div class="box">
                        <div class="box-title">{{ __('accounting::lang.account-credit') }}</div>
                        @if (!empty($creditHint))
                            <div class="box-hint">{{ $creditHint }}</div>
                        @endif
                        <div class="box-value">{{ $creditAccountLabel }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="50%">
                    <div class="box">
                        <div class="box-title">{{ __('accounting::lang.cost_center') }}</div>
                        <div class="box-value">{{ $costCenterLabel }}</div>
                    </div>
                </td>
                <td width="50%">
                    <div class="box">
                        <div class="box-title">{{ __('accounting::lang.added_by') }}</div>
                        <div class="box-value">{{ $createdByLabel }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>

