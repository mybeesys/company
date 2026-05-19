<!DOCTYPE html>
@php
    $localeAr = $locale_ar ?? (app()->getLocale() === 'ar');
    $dir = $localeAr ? 'rtl' : 'ltr';
    $alignStart = $localeAr ? 'right' : 'left';
    $alignEnd = $localeAr ? 'left' : 'right';
    $isPdf = $is_pdf ?? false;
    $currency = $currency ?? 'SAR';
    $opening = (float) ($opening_balance ?? 0);
    $closing = (float) ($closing_balance ?? $opening);
    $fmt = fn (?float $v, bool $emptyZero = false) => \Modules\Accounting\Support\LedgerStatementPresenter::formatAmount($v, $emptyZero);
    $fmtDate = fn (?string $d) => \Modules\Accounting\Support\LedgerStatementPresenter::formatDate($d);
    $accountLabel = $localeAr ? ($account->name_ar ?? $account->name_en) : ($account->name_en ?? $account->name_ar);
@endphp
<html lang="{{ $localeAr ? 'ar' : 'en' }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('accounting::lang.account_statement') }} — {{ $account->gl_code }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
        body {
            font-size: 10px;
            color: #111;
            margin: 0;
            padding: {{ $isPdf ? '4mm 6mm' : '12px 14px' }};
            direction: {{ $dir }};
            text-align: {{ $alignStart }};
        }
        table { border-collapse: collapse; width: 100%; }
        .hdr { margin-bottom: 8px; }
        .hdr td { vertical-align: top; width: 50%; padding: 0 4px; }
        .hdr-company { text-align: {{ $alignStart }}; }
        .hdr-company-name { font-size: 14px; font-weight: 700; margin: 0 0 4px; }
        .hdr-company-line { font-size: 9px; color: #333; margin: 0 0 2px; line-height: 1.4; }
        .hdr-logo { margin-top: 4px; }
        .hdr-logo img { height: 36px; max-height: 36px; width: auto; max-width: 100px; display: block; }
        .hdr-account { text-align: {{ $alignEnd }}; }
        .account-box {
            display: inline-block;
            border: 1px solid #888;
            padding: 8px 10px;
            min-width: 220px;
            max-width: 100%;
            text-align: {{ $alignStart }};
        }
        .account-box table { width: 100%; font-size: 9px; }
        .account-box .lbl {
            color: #444;
            font-weight: 600;
            white-space: nowrap;
            text-align: {{ $alignStart }};
            padding-{{ $localeAr ? 'left' : 'right' }}: 10px;
            width: 42%;
        }
        .account-box .val {
            font-weight: 700;
            color: #000;
            text-align: {{ $alignEnd }};
            width: 58%;
        }
        .account-box tr td { padding: 2px 0; vertical-align: top; }
        .doc-title {
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            margin: 6px 0 10px;
            clear: both;
        }
        .summary-bar {
            margin-bottom: 8px;
            font-size: 9px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 6px;
        }
        .summary-bar td {
            width: 33.33%;
            vertical-align: top;
            padding: 0 6px;
        }
        .summary-bar .s-start { text-align: {{ $alignStart }}; }
        .summary-bar .s-center { text-align: center; }
        .summary-bar .s-end { text-align: {{ $alignEnd }}; }
        .summary-bar .k { color: #555; font-weight: 600; }
        .summary-bar .v { color: #000; font-weight: 700; }
        .lines { font-size: 9px; }
        .lines thead th {
            border: 1px solid #666;
            background: #ececec;
            padding: 6px 4px;
            text-align: center;
            font-weight: 700;
            font-size: 8.5px;
        }
        .lines tbody td,
        .lines tfoot td {
            border: 1px solid #888;
            padding: 5px 4px;
            vertical-align: top;
        }
        .lines .c-date { width: 9%; text-align: center; white-space: nowrap; }
        .lines .c-ref { width: 10%; text-align: {{ $alignStart }}; }
        .lines .c-desc { width: 26%; text-align: {{ $alignStart }}; }
        .lines .c-due { width: 9%; text-align: center; white-space: nowrap; }
        .lines .c-cur { width: 7%; text-align: center; }
        .lines .c-amt { text-align: {{ $alignEnd }}; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .lines .row-open td,
        .lines .row-close td {
            background: #f4f4f4;
            font-weight: 700;
        }
        .footer-meta {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #ccc;
            font-size: 8px;
            color: #555;
        }
        .footer-meta td { vertical-align: top; padding: 2px 4px; }
        @media print {
            body { padding: 6mm; }
        }
    </style>
    @if (! $isPdf)
        <script>
            window.onload = function() { window.print(); };
            window.onafterprint = function() {
                window.location.href = @json(url('ledger').'?account_id='.$account->id);
            };
        </script>
    @endif
</head>
<body>

    {{-- RTL: العمود الأول يظهر يميناً (الشركة) | الثاني يساراً (الحساب) --}}
    <table class="hdr">
        <tr>
            <td class="hdr-company">
                <p class="hdr-company-name">{{ $company_name }}</p>
                @foreach ($company_address_lines as $line)
                    <p class="hdr-company-line">{{ $line }}</p>
                @endforeach
                @if (! empty($company_logo_src))
                    <div class="hdr-logo">
                        <img src="{{ $company_logo_src }}" alt="">
                    </div>
                @endif
            </td>
            <td class="hdr-account">
                <div class="account-box">
                    <table>
                        <tr>
                            <td class="lbl">@lang('accounting::lang.ledger_stmt_account')</td>
                            <td class="val">{{ $account->gl_code }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">@lang('accounting::lang.account_name')</td>
                            <td class="val">{{ $accountLabel }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">@lang('accounting::lang.ledger_stmt_currency')</td>
                            <td class="val">{{ $currency }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">@lang('accounting::lang.from_date')</td>
                            <td class="val">{{ $fmtDate($start_date) }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">@lang('accounting::lang.to_date')</td>
                            <td class="val">{{ $fmtDate($end_date) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="doc-title">@lang('accounting::lang.account_statement')</div>

    <table class="summary-bar">
        <tr>
            <td class="s-start">
                <span class="k">@lang('accounting::lang.opening_balance'):</span>
                <span class="v">{{ $fmt($opening) }} {{ $currency }}</span>
            </td>
            <td class="s-center">
                <span class="k">@lang('accounting::lang.closing_balance'):</span>
                <span class="v">{{ $fmt($closing) }} {{ $currency }}</span>
            </td>
            <td class="s-end">
                <span class="k">@lang('accounting::lang.balance'):</span>
                <span class="v">{{ $fmt((float) $current_bal) }} {{ $currency }}</span>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th class="c-date">@lang('accounting::lang.ledger_stmt_col_date')</th>
                <th class="c-ref">@lang('accounting::lang.ledger_stmt_col_transaction')</th>
                <th class="c-desc">@lang('accounting::lang.ledger_stmt_col_description')</th>
                <th class="c-due">@lang('accounting::lang.ledger_stmt_col_due')</th>
                <th class="c-cur">@lang('accounting::lang.ledger_stmt_currency')</th>
                <th class="c-amt">@lang('accounting::lang.debit')</th>
                <th class="c-amt">@lang('accounting::lang.credit')</th>
                <th class="c-amt">@lang('accounting::lang.balance')</th>
            </tr>
        </thead>
        <tbody>
            <tr class="row-open">
                <td class="c-date"></td>
                <td class="c-ref"></td>
                <td class="c-desc">@lang('accounting::lang.opening_balance')</td>
                <td class="c-due"></td>
                <td class="c-cur">{{ $currency }}</td>
                <td class="c-amt"></td>
                <td class="c-amt"></td>
                <td class="c-amt">{{ $fmt($opening) }}</td>
            </tr>
            @forelse ($statement_lines as $line)
                <tr>
                    <td class="c-date">{{ $line['date'] }}</td>
                    <td class="c-ref">{{ $line['ref'] }}</td>
                    <td class="c-desc">{{ $line['description'] }}</td>
                    <td class="c-due">{{ $line['due'] }}</td>
                    <td class="c-cur">{{ $line['currency'] }}</td>
                    <td class="c-amt">{{ $line['debit'] }}</td>
                    <td class="c-amt">{{ $line['credit'] }}</td>
                    <td class="c-amt">{{ $line['balance'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;color:#666;padding:14px;">@lang('accounting::lang.no_data')</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="row-close">
                <td class="c-date"></td>
                <td class="c-ref"></td>
                <td class="c-desc">@lang('accounting::lang.closing_balance')</td>
                <td class="c-due"></td>
                <td class="c-cur">{{ $currency }}</td>
                <td class="c-amt"></td>
                <td class="c-amt"></td>
                <td class="c-amt">{{ $fmt($closing) }}</td>
            </tr>
        </tfoot>
    </table>

    @if (! $isPdf)
        <table class="footer-meta">
            <tr>
                <td style="width:55%; text-align: {{ $alignStart }};">
                    @if (! empty($company?->phone))
                        <div>@lang('accounting::lang.ledger_stmt_telephone'): {{ $company->phone }}</div>
                    @endif
                    @if (! empty($company?->tax_number))
                        <div>@lang('accounting::lang.ledger_stmt_tax_reg'): {{ $company->tax_number }}</div>
                    @endif
                </td>
                <td style="width:45%; text-align: {{ $alignEnd }};">
                    <div>@lang('accounting::lang.ledger_stmt_printed_at'): {{ $printed_at }}</div>
                    <div style="font-weight:700;">@lang('accounting::lang.ledger_stmt_original')</div>
                </td>
            </tr>
        </table>
    @endif

</body>
</html>
