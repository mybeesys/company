@php
    $isR = !empty($voucherIsReceipt);
    $forPrint = $forPrint ?? false;
    $localeAr = !empty($voucherLocaleAr);
    $titleAr = $isR ? __('accounting::lang.voucher_tpl_receipt_title_ar') : __('accounting::lang.voucher_tpl_payment_title_ar');
    $titleEn = $isR ? __('accounting::lang.voucher_tpl_receipt_title_en') : __('accounting::lang.voucher_tpl_payment_title_en');
    $row1En = $isR ? __('accounting::lang.voucher_tpl_row1_receipt_en') : __('accounting::lang.voucher_tpl_row1_payment_en');
    $row1Ar = $isR ? __('accounting::lang.voucher_tpl_row1_receipt_ar') : __('accounting::lang.voucher_tpl_row1_payment_ar');
    $being = trim((string) ($note ?? ''));
    $hijriLatin = $hijriDateLatin ?? '';
    $hijriArabic = $hijriDateArabic ?? '';
    $taxNo = trim((string) ($companyTaxNumber ?? ''));
@endphp
<style>
    .cv-wrap { font-family: DejaVu Sans, Arial, Tajawal, sans-serif; color: #1a1a1a; background: #fff; }
    .cv-wrap.cv-screen { max-width: 980px; margin: 0 auto; padding: 16px 12px 20px; }
    .cv-wrap.cv-print { padding: 0; }
    .cv-grid3 { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .cv-grid3 td { vertical-align: middle; padding: 8px 10px; }
    .cv-grid3 td.cv-col-co { vertical-align: top; }
    .cv-col-co { width: 30%; text-align: right; vertical-align: top; }
    .cv-col-v { width: 40%; text-align: center; vertical-align: middle; }
    .cv-col-logo { width: 30%; text-align: left; vertical-align: middle; }
    .cv-col-co .cv-co-ar { font-size: 18px; font-weight: 700; margin: 0 0 4px; line-height: 1.25; }
    .cv-col-co .cv-co-sub { font-size: 10px; color: #444; margin: 0 0 4px; line-height: 1.35; }
    .cv-col-co .cv-co-en { font-size: 11px; color: #555; margin: 0; font-weight: 600; }
    .cv-col-logo img { max-height: 56px; max-width: 170px; display: inline-block; }
    .cv-title-ar { font-size: 24px; font-weight: 800; margin: 0 0 6px; letter-spacing: 0.03em; color: #111; }
    .cv-title-en-row { margin-top: 4px; }
    .cv-title-en { display: inline-block; font-size: 10px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: #4a4a4a; border: 1px solid #c8c8c8; border-radius: 999px; padding: 4px 14px; background: #fafafa; }
    .cv-vno { margin-top: 10px; font-size: 13px; color: #333; letter-spacing: 0.05em; }
    .cv-vno-lbl { font-size: 9px; color: #666; font-weight: 600; }
    .cv-date-strip { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; }
    .cv-date-strip td { vertical-align: top; padding: 10px 10px; }
    .cv-date-strip td.cv-dt-cell { border-right: 1px solid #ddd; }
    .cv-date-strip td.cv-dt-last { border-right: 0; }
    .cv-dt-lbl { font-size: 8px; color: #555; font-weight: 700; letter-spacing: 0.04em; margin-bottom: 5px; line-height: 1.35; }
    .cv-dt-val { font-size: 13px; font-weight: 700; }
    .cv-dt-sub { font-size: 10px; color: #555; margin-top: 4px; }
    .cv-trn-lbl { font-size: 8px; color: #555; font-weight: 700; margin-bottom: 5px; line-height: 1.35; }
    .cv-trn-val { font-size: 14px; font-weight: 700; letter-spacing: 0.03em; }
    .cv-trn-name { font-size: 9px; color: #666; margin-top: 4px; line-height: 1.35; }
    .cv-row-table { width: 100%; border-collapse: collapse; margin-top: 14px; }
    .cv-row-table td { vertical-align: bottom; padding: 8px 4px 4px; }
    .cv-lbl-en { font-size: 11px; text-align: left; width: 22%; white-space: nowrap; }
    .cv-lbl-ar { font-size: 12px; text-align: right; width: 26%; direction: rtl; }
    .cv-dots { border-bottom: 1px dotted #333; font-size: 12px; padding: 0 6px 2px; text-align: center; }
    .cv-amt-nums { font-size: 17px; font-weight: 800; line-height: 1.3; }
    .cv-amt-words { margin-top: 8px; font-size: 12px; line-height: 1.6; text-align: center; }
    .cv-amt-w-ar { font-weight: 700; unicode-bidi: isolate; }
    .cv-amt-w-en { font-weight: 600; unicode-bidi: isolate; }
    .cv-amt-sep { color: #888; padding: 0 5px; }
    .cv-amt-suf { font-size: 10px; color: #333; font-weight: 600; }
    .cv-sig { width: 100%; border-collapse: collapse; margin-top: 28px; }
    .cv-sig td { width: 50%; padding: 8px 16px; vertical-align: bottom; }
    .cv-sig-lbl { font-size: 11px; margin-bottom: 28px; }
    .cv-sig-line { border-bottom: 1px solid #111; height: 1px; }
    .cv-meta { font-size: 10px; color: #444; margin-top: 16px; line-height: 1.65; padding-top: 10px; border-top: 1px solid #e5e5e5; }
    .cv-meta strong { font-weight: 700; color: #222; }

    /* PDF / mPDF: breathing room around English title pill; amount cell uses inner table (see blade) */
    .cv-wrap.cv-print .cv-col-v { padding-top: 10px; padding-bottom: 10px; }
    .cv-wrap.cv-print .cv-title-ar { margin-bottom: 0; padding-bottom: 4px; }
    .cv-wrap.cv-print .cv-title-en-row { margin-top: 10px; margin-bottom: 10px; padding-top: 4px; padding-bottom: 4px; }
    .cv-wrap.cv-print .cv-title-en { padding: 7px 18px; line-height: 1.45; }
    .cv-wrap.cv-print .cv-vno { margin-top: 16px; }
    .cv-wrap.cv-print td.cv-amt-cell--pdf { padding-top: 12px; padding-bottom: 12px; }
</style>

<div class="cv-wrap {{ $forPrint ? 'cv-print' : 'cv-screen' }}">
    {{-- رأس عربي: شعار يسار | السند وسط | الشركة يمين --}}
    <table class="cv-grid3" dir="ltr" style="direction: ltr;">
        <tr>
            <td class="cv-col-logo">
                @if (!empty($companyLogoUrl))
                    <img src="{{ $companyLogoUrl }}" alt="">
                @endif
            </td>
            <td class="cv-col-v">
                <div class="cv-title-ar">{{ $titleAr }}</div>
                <div class="cv-title-en-row">
                    <span class="cv-title-en">{{ $titleEn }}</span>
                </div>
                <div class="cv-vno">
                    <span class="cv-vno-lbl">{{ __('accounting::lang.voucher_tpl_voucher_no_label') }}</span>
                    {{ $voucherNo ?? '' }}
                </div>
            </td>
            <td class="cv-col-co">
                <div class="cv-co-ar">{{ $companyNameAr ?? '' }}</div>
                @if (!empty($companyTaglineAr))
                    <div class="cv-co-sub">{{ $companyTaglineAr }}</div>
                @endif
                @if (!empty($companyName) && ($companyName ?? '') !== ($companyNameAr ?? ''))
                    <div class="cv-co-en">{{ $companyName }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ميلادي تحت الشعار | ضريبة تحت السند | هجري تحت الشركة --}}
    <table class="cv-date-strip" dir="ltr" style="direction: ltr;">
        <tr>
            <td class="cv-col-logo cv-dt-cell" dir="ltr" style="text-align: left;">
                <div class="cv-dt-lbl">{{ __('accounting::lang.voucher_tpl_greg_heading') }}</div>
                <div class="cv-dt-val">{{ $gregorianDateFormatted ?? '' }}</div>
            </td>
            <td class="cv-col-v cv-dt-cell" style="text-align: center; vertical-align: top;">
                <div class="cv-trn-lbl">{{ __('accounting::lang.voucher_tpl_trn_label') }}</div>
                <div class="cv-trn-val">{{ $taxNo !== '' ? $taxNo : '—' }}</div>
                @if (!empty($companyTaxName))
                    <div class="cv-trn-name">{{ $companyTaxName }}</div>
                @endif
            </td>
            <td class="cv-col-co cv-dt-last" dir="rtl" style="text-align: right;">
                <div class="cv-dt-lbl" style="text-align: right;">{{ __('accounting::lang.voucher_tpl_hijri_heading') }}</div>
                <div class="cv-dt-val" dir="ltr" style="text-align: right; unicode-bidi: plaintext;">{{ $hijriLatin !== '' ? $hijriLatin : '—' }}</div>
                @if ($hijriArabic !== '')
                    <div class="cv-dt-sub" dir="rtl">{{ $hijriArabic }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="cv-row-table" dir="ltr" style="direction: ltr;">
        <tr>
            <td class="cv-lbl-en">{{ $row1En }}</td>
            <td class="cv-dots">{{ $counterpartValue ?? '' }}</td>
            <td class="cv-lbl-ar">{{ $row1Ar }}</td>
        </tr>
        <tr>
            <td class="cv-lbl-en">{{ __('accounting::lang.voucher_tpl_row2_en') }}</td>
            <td class="cv-amt-cell{{ $forPrint ? ' cv-amt-cell--pdf' : '' }}" width="52%" style="width: 52%; vertical-align: middle;" @if ($forPrint) align="center" @endif>
                @if ($forPrint)
                    <table width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse: collapse; margin: 0;">
                        <tr>
                            <td align="center" style="text-align: center; vertical-align: middle; padding: 10px 8px;">
                @else
                    <div style="text-align: center;">
                @endif
                    <div dir="ltr" class="cv-amt-nums" style="display: inline-block; unicode-bidi: isolate; direction: ltr;">
                        <span>{{ $amountFormatted ?? '' }}</span>
                        <span style="font-size: 11px; font-weight: 700; margin-left: 6px;">{{ __('accounting::lang.voucher_tpl_sr') }}</span>
                    </div>
                    <div class="cv-amt-words" dir="{{ $localeAr ? 'rtl' : 'ltr' }}" style="text-align: center;">
                        @if ($localeAr)
                            <span class="cv-amt-w-ar" dir="rtl" lang="ar">{{ $amountWordsAr ?? '' }}</span>
                            <span class="cv-amt-suf" dir="rtl"> {{ __('accounting::lang.voucher_tpl_amount_words_suffix_ar') }}</span>
                        @else
                            <span class="cv-amt-w-en" dir="ltr" lang="en">{{ $amountWordsEn ?? '' }}</span>
                            <span class="cv-amt-suf" dir="ltr"> {{ __('accounting::lang.voucher_tpl_amount_words_suffix') }}</span>
                        @endif
                    </div>
                @if ($forPrint)
                            </td>
                        </tr>
                    </table>
                @else
                    </div>
                @endif
            </td>
            <td class="cv-lbl-ar">{{ __('accounting::lang.voucher_tpl_row2_ar') }}</td>
        </tr>
    </table>

    <table class="cv-row-table" dir="ltr" style="direction: ltr; margin-top: 4px;">
        <tr>
            <td class="cv-lbl-en">{{ __('accounting::lang.voucher_tpl_row3_bank_en') }}</td>
            <td class="cv-dots">{{ $bankCashValue ?? '' }}</td>
            <td class="cv-lbl-ar">{{ __('accounting::lang.voucher_tpl_row3_bank_ar') }}</td>
        </tr>
        <tr>
            <td class="cv-lbl-en">{{ __('accounting::lang.voucher_tpl_row3_cash_en') }}</td>
            <td class="cv-dots">—</td>
            <td class="cv-lbl-ar">{{ __('accounting::lang.voucher_tpl_row3_cash_ar') }}</td>
        </tr>
    </table>

    <table class="cv-row-table" dir="ltr" style="direction: ltr;">
        <tr>
            <td class="cv-lbl-en">{{ __('accounting::lang.voucher_tpl_row4_en') }}</td>
            <td class="cv-dots">{{ $being !== '' ? $being : '—' }}</td>
            <td class="cv-lbl-ar">{{ __('accounting::lang.voucher_tpl_row4_ar') }}</td>
        </tr>
    </table>

    <table class="cv-sig" dir="ltr" style="direction: ltr;">
        <tr>
            <td>
                <div class="cv-sig-lbl">
                    <span>{{ __('accounting::lang.voucher_tpl_receiver_en') }}</span>
                    &nbsp;/&nbsp;
                    <span dir="rtl">{{ __('accounting::lang.voucher_tpl_receiver_ar') }}</span>
                </div>
                <div class="cv-sig-line"></div>
            </td>
            <td style="text-align: right;">
                <div class="cv-sig-lbl" style="text-align: right;">
                    <span>{{ __('accounting::lang.voucher_tpl_accountant_en') }}</span>
                    &nbsp;/&nbsp;
                    <span dir="rtl">{{ __('accounting::lang.voucher_tpl_accountant_ar') }}</span>
                </div>
                <div class="cv-sig-line"></div>
            </td>
        </tr>
    </table>

    <div class="cv-meta" dir="{{ $localeAr ? 'rtl' : 'ltr' }}" style="text-align: {{ $localeAr ? 'right' : 'left' }};">
        <strong>{{ __('accounting::lang.cost_center') }}</strong>
        {{ $costCenterLabel ?? '—' }}
        <span style="color:#aaa;"> · </span>
        <strong>{{ __('accounting::lang.added_by') }}</strong>
        {{ $createdByLabel ?? '—' }}
    </div>
</div>
