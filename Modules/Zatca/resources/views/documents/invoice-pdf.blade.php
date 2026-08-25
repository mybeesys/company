<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $docTitleAr }} — {{ $transaction->ref_no }}</title>
    <style>
        /* mPDF-safe · gold brand #e9b71f · no soft fills on nested divs */
        body {
            font-family: dejavusans;
            font-size: 9.5pt;
            color: #222;
            line-height: 1.4;
            direction: rtl;
            text-align: right;
        }

        .bi-ar { font-weight: bold; color: #222; font-size: 8.5pt; }
        .bi-en { font-size: 7pt; color: #888; }
        .muted { color: #888; }
        .fs-7 { font-size: 7.5pt; }
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .ltr { direction: ltr; text-align: left; }

        .top-rule {
            border-top: 3px solid {{ $primaryColor }};
            margin: 0 0 10px 0;
            padding: 0;
            height: 0;
        }

        .hdr { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .hdr td { vertical-align: top; padding: 2px 4px; }

        .brand {
            font-size: 14pt;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 3px;
        }
        .meta { font-size: 8pt; color: #555; line-height: 1.5; }
        .meta .row { margin-top: 2px; }

        .logo { text-align: center; }
        .logo img { max-width: 100px; max-height: 60px; }

        .qr { text-align: center; width: 115px; }
        .qr-frame {
            border: 1px solid #e5e5e5;
            padding: 5px;
            background-color: #ffffff;
        }
        .qr-cap { margin-top: 3px; font-size: 7pt; color: #888; }

        .title-wrap {
            text-align: center;
            padding: 8px 0 6px 0;
            margin: 0 0 10px 0;
            border-bottom: 2px solid {{ $primaryColor }};
        }
        .title-ar {
            font-size: 13pt;
            font-weight: bold;
            color: #222;
            text-align: center;
        }
        /* Never use direction:ltr on a full-width block — mPDF parks it at the page corner */
        .title-en {
            font-size: 8.5pt;
            color: #888;
            text-align: center;
            margin-top: 2px;
        }
        .badge {
            margin-top: 4px;
            font-size: 7.5pt;
            color: #1a1a1a;
            text-align: center;
        }

        /* plain white meta — background only on simple table cells */
        .info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info td {
            border: 1px solid #e8e8e8;
            padding: 6px 8px;
            vertical-align: top;
            font-size: 8.5pt;
            background-color: #ffffff;
        }
        .info .lbl {
            width: 18%;
            border-right: 3px solid {{ $primaryColor }};
        }
        .info .val { width: 32%; }

        /* seller / buyer — one table so headers & heights stay parallel */
        .parties {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .parties .ph {
            width: 49%;
            text-align: right;
            padding: 6px 10px;
            border: 1px solid #e8e8e8;
            border-bottom: 1px solid #e8e8e8;
            border-top: 3px solid {{ $primaryColor }};
            background-color: #ffffff;
            font-size: 9pt;
            vertical-align: middle;
        }
        .parties .pgap {
            width: 2%;
            border: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .parties .pb {
            width: 49%;
            padding: 8px 10px;
            border: 1px solid #e8e8e8;
            border-top: 0;
            font-size: 8.5pt;
            line-height: 1.5;
            background-color: #ffffff;
            vertical-align: top;
        }
        .parties .name { font-size: 10pt; font-weight: bold; margin-bottom: 4px; }

        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.lines th {
            background-color: #fafafa;
            color: #333;
            font-size: 7.5pt;
            font-weight: bold;
            text-align: center;
            padding: 6px 3px;
            border-bottom: 2px solid {{ $primaryColor }};
            border-top: 1px solid #e8e8e8;
        }
        table.lines th .en { font-size: 6.5pt; font-weight: normal; color: #888; }
        table.lines td {
            padding: 6px 3px;
            font-size: 8pt;
            border-bottom: 1px solid #eeeeee;
            vertical-align: top;
            background-color: #ffffff;
        }
        table.lines .seq { text-align: center; color: #888; width: 20px; }
        table.lines .num { direction: ltr; text-align: left; }
        table.lines .qty { text-align: center; direction: ltr; }
        .item { font-weight: bold; }
        .sku { font-size: 7pt; color: #888; }

        .foot {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .foot .fh {
            width: 49%;
            text-align: right;
            padding: 6px 10px;
            border: 1px solid #e8e8e8;
            border-top: 3px solid {{ $primaryColor }};
            background-color: #ffffff;
            font-size: 9pt;
            vertical-align: middle;
        }
        .foot .fgap {
            width: 2%;
            border: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .foot .fb {
            width: 49%;
            padding: 0;
            border: 1px solid #e8e8e8;
            border-top: 0;
            background-color: #ffffff;
            vertical-align: top;
        }
        .words-inner {
            width: 100%;
            border-collapse: collapse;
        }
        .words-inner td {
            padding: 6px 10px;
            font-size: 8pt;
            line-height: 1.45;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
            background-color: #ffffff;
        }
        .words-inner tr:last-child td { border-bottom: 0; }
        .words-inner .wl {
            width: 28%;
            color: #666;
            font-size: 7.5pt;
        }
        .words-inner .wv { width: 72%; }
        .words-inner .wv-en { text-align: left; }

        .totals {
            width: 100%;
            border-collapse: collapse;
        }
        .totals td {
            padding: 5px 10px;
            font-size: 8.5pt;
            border-bottom: 1px solid #eeeeee;
            background-color: #ffffff;
        }
        .totals tr:last-child td { border-bottom: 0; }
        .totals .k .en { font-size: 7pt; color: #888; font-weight: normal; }
        .totals .v { text-align: left; font-weight: bold; width: 36%; }
        .totals tr.grand td {
            background-color: {{ $primarySoft }};
            color: #222;
            font-size: 10pt;
            font-weight: bold;
            border-top: 2px solid {{ $primaryColor }};
            border-bottom: 0;
        }
        .totals tr.grand .v { color: #1a1a1a; }

        .comp {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid #e8e8e8;
            font-size: 7.5pt;
            color: #555;
            line-height: 1.45;
        }
        .hash { direction: ltr; text-align: left; font-size: 7pt; color: #888; }
        .note {
            margin-top: 5px;
            color: #1a1a1a;
            font-weight: bold;
            font-size: 7.5pt;
        }
    </style>
</head>
<body>
@php
    $money = static fn ($v): string => number_format((float) $v, 2);
@endphp

<div class="top-rule"></div>

<table class="hdr">
    <tr>
        <td width="48%">
            <div class="brand">{{ $sellerName }}</div>
            <div class="meta">
                @if ($setting->seller_name && $setting->seller_name !== $sellerName)
                    <div>{{ $setting->seller_name }}</div>
                @endif
                <div class="row">
                    <span class="bi-ar">الرقم الضريبي</span>
                    <span class="bi-en"> · VAT</span>
                    <span class="ltr"> {{ $setting->vat_number }}</span>
                </div>
                <div class="row">
                    <span class="bi-ar">السجل التجاري</span>
                    <span class="bi-en"> · CR</span>
                    <span class="ltr"> {{ $setting->commercial_registration_number }}</span>
                </div>
                @if ($sellerAddressLine !== '')
                    <div class="row ltr muted">{{ $sellerAddressLine }}</div>
                @endif
            </div>
        </td>
        <td width="20%" class="logo">
            @if (!empty($logoSrc))
                <img src="{{ $logoSrc }}" alt="Logo" width="90">
            @endif
        </td>
        <td width="32%" class="qr">
            <div class="qr-frame">{!! $qrCode !!}</div>
            <div class="qr-cap">
                <div class="bi-ar">رمز المرحلة الثانية</div>
                <div class="bi-en">ZATCA Phase 2 QR</div>
            </div>
        </td>
    </tr>
</table>

<div class="title-wrap">
    <div class="title-ar">{{ $docTitleAr }}</div>
    <div class="title-en">{{ $docTitleEn }}</div>
    <div class="badge">
        ZATCA Phase 2 · {{ $statusLabel }} · {{ strtoupper((string) ($sync->report_type ?: 'reporting')) }}
    </div>
</div>

<table class="info">
    <tr>
        <td class="lbl">
            <div class="bi-ar">رقم الفاتورة</div>
            <div class="bi-en">Invoice No.</div>
        </td>
        <td class="val ltr bold">{{ $transaction->ref_no }}</td>
        <td class="lbl">
            <div class="bi-ar">تاريخ الإصدار</div>
            <div class="bi-en">Issue Date</div>
        </td>
        <td class="val ltr">{{ $issueDate }}</td>
    </tr>
    <tr>
        <td class="lbl">
            <div class="bi-ar">تاريخ الاستحقاق</div>
            <div class="bi-en">Due Date</div>
        </td>
        <td class="val ltr">{{ $dueDate !== '' ? $dueDate : '-' }}</td>
        <td class="lbl">
            <div class="bi-ar">حالة الدفع</div>
            <div class="bi-en">Payment Status</div>
        </td>
        <td class="val">
            <div class="bi-ar">{{ $paymentStatusLabel }}</div>
            <div class="bi-en">{{ $paymentStatusLabelEn }}</div>
        </td>
    </tr>
    @if ($isCreditNote && !empty($parentRef))
        <tr>
            <td class="lbl">
                <div class="bi-ar">الفاتورة الأصلية</div>
                <div class="bi-en">Original Invoice</div>
            </td>
            <td class="val ltr bold" colspan="3">{{ $parentRef }}</td>
        </tr>
    @endif
    <tr>
        <td class="lbl">
            <div class="bi-ar">المعرف الفريد</div>
            <div class="bi-en">UUID</div>
        </td>
        <td class="val ltr" colspan="3" style="font-size:7.5pt;">{{ $sync->invoice_uuid }}</td>
    </tr>
</table>

<table class="parties">
    <tr>
        <td class="ph" width="49%">
            <div class="bi-ar">البائع</div>
            <div class="bi-en">Seller</div>
        </td>
        <td class="pgap" width="2%">&nbsp;</td>
        <td class="ph" width="49%">
            <div class="bi-ar">المشتري</div>
            <div class="bi-en">Buyer</div>
        </td>
    </tr>
    <tr>
        <td class="pb" width="49%">
            <div class="name">{{ $sellerName }}</div>
            @if ($sellerAddressLine !== '')
                <div class="ltr muted fs-7">{{ $sellerAddressLine }}</div>
            @endif
            <div style="margin-top:4px;">
                <span class="bi-ar">الرقم الضريبي</span>
                <span class="bi-en"> VAT</span>
                <span class="ltr"> {{ $setting->vat_number }}</span>
            </div>
            <div>
                <span class="bi-ar">السجل التجاري</span>
                <span class="bi-en"> CR</span>
                <span class="ltr"> {{ $setting->commercial_registration_number }}</span>
            </div>
        </td>
        <td class="pgap" width="2%">&nbsp;</td>
        <td class="pb" width="49%">
            <div class="name">{{ $buyerName }}</div>
            @if ($buyerAddressLine !== '')
                <div class="ltr muted fs-7">{{ $buyerAddressLine }}</div>
            @endif
            @if ($buyerVat !== '')
                <div style="margin-top:4px;">
                    <span class="bi-ar">الرقم الضريبي</span>
                    <span class="bi-en"> VAT</span>
                    <span class="ltr"> {{ $buyerVat }}</span>
                </div>
            @else
                <div class="muted fs-7">{{ __('zatca::lang.pdf_buyer_vat_na') }}</div>
            @endif
            @if ($buyerMobile !== '')
                <div>
                    <span class="bi-ar">{{ __('zatca::lang.pdf_mobile') }}</span>
                    <span class="bi-en"> Mobile</span>
                    <span class="ltr"> {{ $buyerMobile }}</span>
                </div>
            @endif
        </td>
    </tr>
</table>

<table class="lines">
    <thead>
        <tr>
            <th width="4%">#</th>
            <th width="30%">البيان<br><span class="en">Description</span></th>
            <th width="9%">الكمية<br><span class="en">Qty</span></th>
            <th width="12%">السعر<br><span class="en">Unit Price</span></th>
            <th width="10%">الخصم<br><span class="en">Discount</span></th>
            <th width="9%">الضريبة %<br><span class="en">VAT %</span></th>
            <th width="12%">قيمة الضريبة<br><span class="en">VAT Amt</span></th>
            <th width="14%">الإجمالي<br><span class="en">Total</span></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($lineRows as $row)
            <tr>
                <td class="seq">{{ $row['seq'] }}</td>
                <td>
                    <div class="item">{{ $row['name'] }}</div>
                    @if ($row['sku'] !== '')
                        <div class="sku ltr">{{ $row['sku'] }}</div>
                    @endif
                    @if ($row['note'] !== '')
                        <div class="sku">{{ $row['note'] }}</div>
                    @endif
                </td>
                <td class="qty">
                    {{ $money($row['qty']) }}
                    @if ($row['unit'] !== '')
                        <span class="muted">({{ $row['unit'] }})</span>
                    @endif
                </td>
                <td class="num">{{ $money($row['unit_price']) }}</td>
                <td class="num">{{ $money($row['discount']) }}</td>
                <td class="qty">{{ $row['tax_percent'] }}%</td>
                <td class="num">{{ $money($row['tax']) }}</td>
                <td class="num bold">{{ $money($row['total']) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="center muted">-</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table class="foot">
    <tr>
        <td class="fh" width="49%">
            <div class="bi-ar">مبلغ الفاتورة كتابةً</div>
            <div class="bi-en">Amount in words</div>
        </td>
        <td class="fgap" width="2%">&nbsp;</td>
        <td class="fh" width="49%">
            <div class="bi-ar">ملخص المبالغ</div>
            <div class="bi-en">Totals</div>
        </td>
    </tr>
    <tr>
        <td class="fb" width="49%">
            <table class="words-inner">
                <tr>
                    <td class="wl">
                        <div class="bi-ar">عربي</div>
                        <div class="bi-en">Arabic</div>
                    </td>
                    <td class="wv">{{ $amountWordsAr }}</td>
                </tr>
                <tr>
                    <td class="wl">
                        <div class="bi-ar">إنجليزي</div>
                        <div class="bi-en">English</div>
                    </td>
                    <td class="wv wv-en">{{ $amountWordsEn }}</td>
                </tr>
                @if ($invoiceDiscount > 0)
                    <tr>
                        <td class="wl">
                            <div class="bi-ar">خصم الفاتورة</div>
                            <div class="bi-en">Invoice discount</div>
                        </td>
                        <td class="wv wv-en">{{ $money($invoiceDiscount) }} SAR</td>
                    </tr>
                @endif
                @if ($serviceFee > 0)
                    <tr>
                        <td class="wl">
                            <div class="bi-ar">{{ __('zatca::lang.service_fee_reason') }}</div>
                            <div class="bi-en">Service fee</div>
                        </td>
                        <td class="wv wv-en">
                            {{ $money($serviceFee) }} SAR
                            @if ($serviceFeeTax > 0)
                                (+ VAT {{ $money($serviceFeeTax) }})
                            @endif
                        </td>
                    </tr>
                @endif
            </table>
        </td>
        <td class="fgap" width="2%">&nbsp;</td>
        <td class="fb" width="49%">
            <table class="totals">
                <tr>
                    <td class="k">
                        المجموع قبل الضريبة<br><span class="en">Subtotal (ex-VAT)</span>
                    </td>
                    <td class="v">{{ $money($subtotalExVat) }}</td>
                </tr>
                <tr>
                    <td class="k">
                        إجمالي الخصم<br><span class="en">Total Discount</span>
                    </td>
                    <td class="v">{{ $money($totalLineDiscount + $invoiceDiscount) }}</td>
                </tr>
                <tr>
                    <td class="k">
                        ضريبة القيمة المضافة<br><span class="en">VAT Total</span>
                    </td>
                    <td class="v">{{ $money($vatTotal) }}</td>
                </tr>
                <tr class="grand">
                    <td class="k">
                        الإجمالي شامل الضريبة<br><span class="en">Grand Total</span>
                    </td>
                    <td class="v">{{ $money($grandTotal) }} SAR</td>
                </tr>
                <tr>
                    <td class="k">
                        المدفوع<br><span class="en">Paid</span>
                    </td>
                    <td class="v">{{ $money($paidAmount) }}</td>
                </tr>
                <tr>
                    <td class="k">
                        المتبقي<br><span class="en">Amount Due</span>
                    </td>
                    <td class="v">{{ $money($dueAmount) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="comp">
    <div>
        <strong>{{ __('zatca::lang.pdf_synced_at') }}:</strong>
        <span class="ltr">{{ $syncedAt }}</span>
        &nbsp;|&nbsp;
        <strong>{{ __('zatca::lang.pdf_hash') }}:</strong>
    </div>
    <div class="hash">{{ $sync->invoice_hash }}</div>
    <div class="note">{{ __('zatca::lang.pdf_disclaimer') }}</div>
</div>
</body>
</html>
