<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $docTitleAr }} — {{ $transaction->ref_no }}</title>
    <style>
        body {
            font-family: dejavusans, DejaVu Sans, sans-serif;
            font-size: 9.5pt;
            color: #222;
            line-height: 1.4;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 8px;
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
        .brand { font-size: 14pt; font-weight: bold; color: #1a1a1a; margin-bottom: 3px; }
        .logo { text-align: center; }
        .logo img { max-width: 100px; max-height: 60px; }

        .title-wrap {
            text-align: center;
            padding: 8px 0 6px 0;
            margin: 0 0 10px 0;
            border-bottom: 2px solid {{ $primaryColor }};
        }
        .title-ar { font-size: 13pt; font-weight: bold; color: #222; text-align: center; }
        .title-en { font-size: 8.5pt; color: #888; text-align: center; margin-top: 2px; }
        .badge { margin-top: 4px; font-size: 7.5pt; color: #1a1a1a; text-align: center; }

        .info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info td {
            border: 1px solid #e8e8e8;
            padding: 6px 8px;
            vertical-align: top;
            font-size: 8.5pt;
            background-color: #ffffff;
        }
        .info .lbl { width: 18%; border-right: 3px solid {{ $primaryColor }}; }
        .info .val { width: 32%; }

        .parties { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .parties .ph {
            width: 49%;
            text-align: right;
            padding: 6px 10px;
            border: 1px solid #e8e8e8;
            border-top: 3px solid {{ $primaryColor }};
            background-color: #ffffff;
            font-size: 9pt;
            vertical-align: middle;
        }
        .parties .pgap { width: 2%; border: 0; padding: 0; background-color: #ffffff; }
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

        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
        }
        table.lines th,
        table.lines td {
            padding: 7px 6px;
            font-size: 8pt;
            vertical-align: middle;
            background-color: #ffffff;
            overflow: hidden;
        }
        table.lines th {
            background-color: #fafafa;
            color: #333;
            font-size: 7.5pt;
            font-weight: bold;
            border-bottom: 2px solid {{ $primaryColor }};
            border-top: 1px solid #e8e8e8;
            line-height: 1.35;
        }
        table.lines th .en {
            display: block;
            font-size: 6.5pt;
            font-weight: normal;
            color: #888;
            margin-top: 1px;
        }
        table.lines td { border-bottom: 1px solid #eeeeee; }
        table.lines .col-seq { width: 4%; text-align: center; color: #888; }
        table.lines .col-desc {
            width: 29%;
            text-align: right;
            vertical-align: top;
            padding-right: 8px;
            word-wrap: break-word;
        }
        table.lines .col-qty { width: 8%; text-align: center; direction: ltr; white-space: nowrap; }
        table.lines .col-money {
            width: 12%;
            text-align: right;
            direction: ltr;
            white-space: nowrap;
            padding-left: 4px;
            padding-right: 6px;
        }
        table.lines .col-pct { width: 8%; text-align: center; direction: ltr; white-space: nowrap; }
        table.lines .col-total {
            width: 16%;
            text-align: right;
            direction: ltr;
            white-space: nowrap;
            font-weight: bold;
            padding-left: 4px;
            padding-right: 6px;
        }
        table.lines th.col-money,
        table.lines th.col-total { text-align: right; direction: rtl; }
        .item { font-weight: bold; line-height: 1.35; }
        .sku { font-size: 7pt; color: #888; line-height: 1.3; margin-top: 2px; }

        .foot-layout { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .foot-layout td { vertical-align: top; background-color: #ffffff; }
        .foot-totals-wrap { padding-left: 10px; }
        .foot { width: 100%; border-collapse: collapse; }
        .foot .fh {
            text-align: right;
            padding: 6px 12px;
            border: 1px solid #e8e8e8;
            border-top: 3px solid {{ $primaryColor }};
            background-color: #ffffff;
            font-size: 9pt;
            vertical-align: middle;
        }
        .foot .fb {
            padding: 0;
            border: 1px solid #e8e8e8;
            border-top: 0;
            background-color: #ffffff;
            vertical-align: top;
        }
        .label-bi { line-height: 1.45; white-space: normal; }
        .label-bi .ar { font-weight: bold; font-size: 8.5pt; color: #222; }
        .label-bi .sep { color: #c8c8c8; margin: 0 5px; font-weight: normal; }
        .label-bi .en { color: #888; font-size: 7.5pt; font-weight: normal; }
        .foot-qr-free {
            text-align: center;
            vertical-align: middle;
            padding: 0 8px 0 0;
            border: 0;
            background: transparent;
        }
        .qr-cap-bi { margin-bottom: 8px; line-height: 1.35; }
        .qr-cap-bi .ar { font-weight: bold; color: #666; font-size: 7.5pt; }
        .qr-cap-bi .en { color: #888; font-size: 7pt; }
        .qr-cap-bi .sep { color: #ccc; margin: 0 5px; }
        .qr-svg-wrap { display: inline-block; line-height: 0; }
        .qr-svg-wrap svg { width: 145px; height: 145px; max-width: 145px; max-height: 145px; }

        .totals { width: 100%; border-collapse: collapse; }
        .totals td {
            padding: 6px 12px;
            font-size: 8.5pt;
            border-bottom: 1px solid #eeeeee;
            background-color: #ffffff;
        }
        .totals tr:last-child td { border-bottom: 0; }
        .totals .k { width: 65%; line-height: 1.45; vertical-align: middle; }
        .totals .v {
            text-align: left;
            font-weight: bold;
            width: 35%;
            white-space: nowrap;
            vertical-align: middle;
        }
        .totals tr.grand td {
            background-color: {{ $primarySoft }};
            color: #222;
            font-size: 10pt;
            font-weight: bold;
            border-top: 2px solid {{ $primaryColor }};
            border-bottom: 0;
        }
        .totals tr.grand .v { color: #1a1a1a; }
        .grand-words-ar {
            margin-top: 6px;
            font-size: 7.5pt;
            font-weight: normal;
            color: #555;
            line-height: 1.45;
        }
        .grand-words-en {
            margin-top: 3px;
            font-size: 7pt;
            font-weight: normal;
            color: #888;
            direction: ltr;
            text-align: left;
            line-height: 1.4;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
    @if (!empty($autoPrint))
        <script>
            window.onload = function () { window.print(); };
            window.onafterprint = function () {
                @if (!empty($afterPrintUrl))
                    window.location.href = @json($afterPrintUrl);
                @endif
            };
        </script>
    @endif
</head>
<body>
@php
    $money = static fn ($v): string => number_format((float) $v, 2);
    $seller = $seller ?? ['name' => '—', 'address' => '', 'vat' => '', 'cr' => '', 'mobile' => ''];
    $buyer = $buyer ?? ['name' => '—', 'address' => '', 'vat' => '', 'cr' => '', 'mobile' => ''];
@endphp

<div class="top-rule"></div>

<table class="hdr">
    <tr>
        <td width="55%">
            <div class="brand">{{ $seller['name'] }}</div>
            @if (($seller['vat'] ?? '') !== '')
                <div class="bi-en ltr">VAT {{ $seller['vat'] }}</div>
            @endif
        </td>
        <td width="45%" class="logo">
            @if (!empty($logoSrc))
                <img src="{{ $logoSrc }}" alt="Logo" width="90">
            @endif
        </td>
    </tr>
</table>

<div class="title-wrap">
    <div class="title-ar">{{ $docTitleAr }}</div>
    <div class="title-en">{{ $docTitleEn }}</div>
    <div class="badge">{{ $docBadgeAr }} · {{ $docBadgeEn }}</div>
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
    @if ($isReturn && !empty($parentRef))
        <tr>
            <td class="lbl">
                <div class="bi-ar">المستند الأصلي</div>
                <div class="bi-en">Original Document</div>
            </td>
            <td class="val ltr bold" colspan="3">{{ $parentRef }}</td>
        </tr>
    @endif
</table>

<table class="parties">
    <tr>
        <td class="ph" width="49%">
            <div class="bi-ar">{{ $sellerRoleAr }}</div>
            <div class="bi-en">{{ $sellerRoleEn }}</div>
        </td>
        <td class="pgap" width="2%">&nbsp;</td>
        <td class="ph" width="49%">
            <div class="bi-ar">{{ $buyerRoleAr }}</div>
            <div class="bi-en">{{ $buyerRoleEn }}</div>
        </td>
    </tr>
    <tr>
        <td class="pb" width="49%">
            <div class="name">{{ $seller['name'] }}</div>
            @if (($seller['address'] ?? '') !== '')
                <div class="ltr muted fs-7">{{ $seller['address'] }}</div>
            @endif
            @if (($seller['vat'] ?? '') !== '')
                <div style="margin-top:4px;">
                    <span class="bi-ar">الرقم الضريبي</span>
                    <span class="bi-en"> VAT</span>
                    <span class="ltr"> {{ $seller['vat'] }}</span>
                </div>
            @endif
            @if (($seller['cr'] ?? '') !== '')
                <div>
                    <span class="bi-ar">السجل التجاري</span>
                    <span class="bi-en"> CR</span>
                    <span class="ltr"> {{ $seller['cr'] }}</span>
                </div>
            @endif
            @if (($seller['mobile'] ?? '') !== '')
                <div>
                    <span class="bi-ar">الجوال</span>
                    <span class="bi-en"> Mobile</span>
                    <span class="ltr"> {{ $seller['mobile'] }}</span>
                </div>
            @endif
        </td>
        <td class="pgap" width="2%">&nbsp;</td>
        <td class="pb" width="49%">
            <div class="name">{{ $buyer['name'] }}</div>
            @if (($buyer['address'] ?? '') !== '')
                <div class="ltr muted fs-7">{{ $buyer['address'] }}</div>
            @endif
            @if (($buyer['vat'] ?? '') !== '')
                <div style="margin-top:4px;">
                    <span class="bi-ar">الرقم الضريبي</span>
                    <span class="bi-en"> VAT</span>
                    <span class="ltr"> {{ $buyer['vat'] }}</span>
                </div>
            @else
                <div class="muted fs-7">لا يوجد رقم ضريبي</div>
            @endif
            @if (($buyer['cr'] ?? '') !== '')
                <div>
                    <span class="bi-ar">السجل التجاري</span>
                    <span class="bi-en"> CR</span>
                    <span class="ltr"> {{ $buyer['cr'] }}</span>
                </div>
            @endif
            @if (($buyer['mobile'] ?? '') !== '')
                <div>
                    <span class="bi-ar">الجوال</span>
                    <span class="bi-en"> Mobile</span>
                    <span class="ltr"> {{ $buyer['mobile'] }}</span>
                </div>
            @endif
        </td>
    </tr>
</table>

<table class="lines">
    <thead>
        <tr>
            <th class="col-seq">#</th>
            <th class="col-desc">البيان<span class="en">Description</span></th>
            <th class="col-qty">الكمية<span class="en">Qty</span></th>
            <th class="col-money">السعر<span class="en">Unit Price</span></th>
            <th class="col-money">الخصم<span class="en">Discount</span></th>
            <th class="col-pct">الضريبة %<span class="en">VAT %</span></th>
            <th class="col-money">قيمة الضريبة<span class="en">VAT Amt</span></th>
            <th class="col-total">الإجمالي<span class="en">Total</span></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($lineRows as $row)
            <tr>
                <td class="col-seq">{{ $row['seq'] }}</td>
                <td class="col-desc">
                    <div class="item">{{ $row['name'] }}</div>
                    @if ($row['sku'] !== '')
                        <div class="sku ltr">{{ $row['sku'] }}</div>
                    @endif
                    @if ($row['note'] !== '')
                        <div class="sku">{{ $row['note'] }}</div>
                    @endif
                </td>
                <td class="col-qty">
                    {{ $money($row['qty']) }}
                    @if ($row['unit'] !== '')
                        <span class="muted">({{ $row['unit'] }})</span>
                    @endif
                </td>
                <td class="col-money">{{ $money($row['unit_price']) }}</td>
                <td class="col-money">{{ $money($row['discount']) }}</td>
                <td class="col-pct">{{ $row['tax_percent'] }}%</td>
                <td class="col-money">{{ $money($row['tax']) }}</td>
                <td class="col-total">{{ $money($row['total']) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="center muted">-</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table class="foot-layout">
    <tr>
        <td width="30%" class="foot-qr-free">
            <div class="qr-cap-bi">
                @if (!empty($hasZatcaQr))
                    <span class="ar">رمز المرحلة الثانية</span>
                    <span class="sep">·</span>
                    <span class="en">ZATCA Phase 2 QR</span>
                @else
                    <span class="ar">رمز الفاتورة</span>
                    <span class="sep">·</span>
                    <span class="en">Invoice QR</span>
                @endif
            </div>
            <div class="qr-svg-wrap">{!! $qrCode !!}</div>
        </td>
        <td width="3%">&nbsp;</td>
        <td width="67%" class="foot-totals-wrap">
            <table class="foot">
                <tr>
                    <td class="fh">
                        <span class="label-bi">
                            <span class="ar">ملخص المبالغ</span>
                            <span class="sep">·</span>
                            <span class="en">Totals</span>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td class="fb">
                        <table class="totals">
                            <tr>
                                <td class="k">
                                    <span class="label-bi">
                                        <span class="ar">المجموع قبل الضريبة</span>
                                        <span class="sep">·</span>
                                        <span class="en">Subtotal (ex-VAT)</span>
                                    </span>
                                </td>
                                <td class="v">{{ $money($subtotalExVat) }}</td>
                            </tr>
                            <tr>
                                <td class="k">
                                    <span class="label-bi">
                                        <span class="ar">إجمالي الخصم</span>
                                        <span class="sep">·</span>
                                        <span class="en">Total Discount</span>
                                    </span>
                                </td>
                                <td class="v">{{ $money($totalLineDiscount + $invoiceDiscount) }}</td>
                            </tr>
                            <tr>
                                <td class="k">
                                    <span class="label-bi">
                                        <span class="ar">ضريبة القيمة المضافة</span>
                                        <span class="sep">·</span>
                                        <span class="en">VAT Total</span>
                                    </span>
                                </td>
                                <td class="v">{{ $money($vatTotal) }}</td>
                            </tr>
                            <tr class="grand">
                                <td class="k">
                                    <span class="label-bi">
                                        <span class="ar">الإجمالي شامل الضريبة</span>
                                        <span class="sep">·</span>
                                        <span class="en">Total with Tax</span>
                                    </span>
                                    @if ($amountWordsAr !== '')
                                        <div class="grand-words-ar">{{ $amountWordsAr }}</div>
                                    @endif
                                    @if ($amountWordsEn !== '')
                                        <div class="grand-words-en">{{ $amountWordsEn }}</div>
                                    @endif
                                </td>
                                <td class="v">{{ $money($grandTotal) }} SAR</td>
                            </tr>
                            <tr>
                                <td class="k">
                                    <span class="label-bi">
                                        <span class="ar">المدفوع</span>
                                        <span class="sep">·</span>
                                        <span class="en">Paid</span>
                                    </span>
                                </td>
                                <td class="v">{{ $money($paidAmount) }}</td>
                            </tr>
                            <tr>
                                <td class="k">
                                    <span class="label-bi">
                                        <span class="ar">المتبقي</span>
                                        <span class="sep">·</span>
                                        <span class="en">Amount Due</span>
                                    </span>
                                </td>
                                <td class="v">{{ $money($dueAmount) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
