<?php

namespace Modules\Zatca\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Modules\Accounting\Support\LedgerStatementPresenter;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionSellLine;
use Modules\General\Support\TransactionLineTaxRate;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use Modules\Zatca\Models\ZatcaSetting;
use Mpdf\Mpdf;
use NumberFormatter;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ZatcaDocumentController extends Controller
{
    public function pdf(int $transactionId): Response|StreamedResponse
    {
        abort_unless(config('zatca.show_in_menu', true), 404);

        $data = $this->buildPdfViewData($transactionId);

        $html = view('zatca::documents.invoice-pdf', $data)->render();
        $footerHtml = view('zatca::documents.partials.pdf-footer', $data)->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 10,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'margin_top' => 10,
            'margin_bottom' => 18,
            'margin_left' => 10,
            'margin_right' => 10,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle(($data['docTitleAr'] ?? 'Invoice').' - '.(string) $data['transaction']->ref_no);
        $mpdf->SetHTMLFooter($footerHtml);
        $mpdf->WriteHTML($html);

        $filename = 'zatca-'.preg_replace('/[^\w\-]+/u', '-', (string) $data['transaction']->ref_no).'.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function preview(int $transactionId): View
    {
        abort_unless(config('zatca.show_in_menu', true), 404);

        return view('zatca::documents.invoice-pdf', $this->buildPdfViewData($transactionId));
    }

    public function xml(int $transactionId): Response
    {
        abort_unless(config('zatca.show_in_menu', true), 404);

        [, $sync] = $this->loadSyncedDocument($transactionId);

        $xmlBase64 = (string) ($sync->cleared_invoice ?: '');
        if ($xmlBase64 === '') {
            abort(404, __('zatca::lang.document_xml_missing'));
        }

        $xml = base64_decode($xmlBase64, true);
        if ($xml === false || trim($xml) === '') {
            abort(422, __('zatca::lang.document_xml_invalid'));
        }

        $filename = 'zatca-'.($sync->invoice_uuid ?: $sync->transaction_id).'.xml';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function qr(int $transactionId): Response
    {
        abort_unless(config('zatca.show_in_menu', true), 404);

        [, $sync] = $this->loadSyncedDocument($transactionId);

        if (! $sync->qr_tlv) {
            abort(404, __('zatca::lang.document_qr_missing'));
        }

        // SVG avoids Imagick (PNG backend is unavailable on many hosts).
        $svg = QrCode::format('svg')->size(320)->margin(1)->generate($sync->qr_tlv);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="zatca-qr-'.$sync->transaction_id.'.svg"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPdfViewData(int $transactionId): array
    {
        [$transaction, $sync, $setting, $qrCode] = $this->loadSyncedDocument($transactionId);

        $isCreditNote = (string) $transaction->type === 'sell-return';
        $sellerName = trim((string) ($setting->organization_name ?: $setting->seller_name)) ?: '—';

        $sellerAddressParts = array_filter([
            trim((string) $setting->street_name),
            trim((string) $setting->building_number),
            trim((string) $setting->district),
            trim((string) $setting->city),
            trim((string) $setting->postal_code),
            trim((string) ($setting->country_code ?: 'SA')),
        ], static fn ($part) => $part !== null && $part !== '');
        $sellerAddressLine = implode(' / ', $sellerAddressParts);

        $client = $transaction->client;
        $billing = $client?->billingAddress;
        $buyerName = trim((string) ($client?->name ?? '')) ?: __('zatca::lang.walk_in_customer');
        $buyerVat = preg_replace('/\D+/', '', (string) ($client?->tax_number ?? '')) ?: '';
        $buyerMobile = trim((string) ($client?->mobile_number ?? ''));
        $buyerAddressParts = array_filter([
            trim((string) ($billing?->street_name ?? '')),
            trim((string) ($billing?->building_number ?? '')),
            trim((string) ($billing?->state ?? '')),
            trim((string) ($billing?->city ?? '')),
            trim((string) ($billing?->postal_code ?? '')),
        ], static fn ($part) => $part !== null && $part !== '');
        $buyerAddressLine = implode(' / ', $buyerAddressParts);

        $lineRows = [];
        $totalLineDiscount = 0.0;
        $lines = $isCreditNote
            ? $transaction->purchases_lines
            : $transaction->sell_lines->filter(function ($line) {
                if ((string) ($line->parent_id ?? '') !== '' && (int) $line->parent_id > 0) {
                    return false;
                }
                if (isset($line->is_show) && (string) $line->is_show === '0') {
                    return false;
                }

                return true;
            });

        $seq = 1;
        foreach ($lines as $line) {
            $row = $this->mapPdfLine($line, $seq);
            $lineRows[] = $row;
            $totalLineDiscount += $row['discount'];
            $seq++;
        }

        $invoiceDiscount = $this->resolveInvoiceDiscount($transaction);
        $serviceFee = round((float) ($transaction->service_fee_amount ?: 0), 2);
        $serviceFeeTax = round((float) ($transaction->service_fee_tax ?: 0), 2);

        $subtotalExVat = round((float) ($transaction->total_before_tax ?: 0), 2);
        $vatTotal = round((float) ($transaction->tax_amount ?: 0), 2);
        $grandTotal = round((float) ($transaction->final_total ?: 0), 2);
        $paidAmount = round((float) $transaction->payment->sum('amount'), 2);
        $dueAmount = max(0, round($grandTotal - $paidAmount, 2));

        $pdfStatus = $sync->reporting_status ?: 'REPORTED';
        $pdfStatusKey = 'zatca::lang.reporting_status_'.strtolower((string) $pdfStatus);
        $statusLabel = __($pdfStatusKey);
        if ($statusLabel === $pdfStatusKey) {
            $statusLabel = (string) $pdfStatus;
        }

        $paymentStatus = strtolower((string) ($transaction->payment_status ?: 'due'));
        $paymentStatusLabel = match ($paymentStatus) {
            'paid' => __('zatca::lang.pdf_payment_paid'),
            'partial' => __('zatca::lang.pdf_payment_partial'),
            default => __('zatca::lang.pdf_payment_due'),
        };
        $paymentStatusLabelEn = match ($paymentStatus) {
            'paid' => 'Paid',
            'partial' => 'Partially paid',
            default => 'Due',
        };

        $issueDate = Carbon::parse($transaction->transaction_date)->format('Y-m-d');
        $dueDate = $transaction->due_date
            ? Carbon::parse($transaction->due_date)->format('Y-m-d')
            : '';

        $logoSrc = null;
        $companyId = function_exists('get_company_id') ? get_company_id() : null;
        if ($companyId) {
            $company = Company::query()->find($companyId);
            $logoSrc = LedgerStatementPresenter::companyLogoSrc($company, true);
        }

        // Brand gold — soft tint only for grand-total row (table cell, mPDF-safe).
        $primaryColor = (string) config('zatca.pdf_primary_color', '#e9b71f');
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $primaryColor)) {
            $primaryColor = '#e9b71f';
        }
        $pr = hexdec(substr($primaryColor, 1, 2));
        $pg = hexdec(substr($primaryColor, 3, 2));
        $pb = hexdec(substr($primaryColor, 5, 2));
        $primaryRgb = "{$pr}, {$pg}, {$pb}";
        $blend = static function (int $c, float $alpha): int {
            return (int) round(255 + ($c - 255) * $alpha);
        };
        // Very light cream tint of brand gold (≈8% on white).
        $primarySoft = sprintf(
            '#%02X%02X%02X',
            $blend($pr, 0.10),
            $blend($pg, 0.10),
            $blend($pb, 0.10)
        );
        $primarySoft2 = sprintf(
            '#%02X%02X%02X',
            $blend($pr, 0.18),
            $blend($pg, 0.18),
            $blend($pb, 0.18)
        );

        return [
            'transaction' => $transaction,
            'sync' => $sync,
            'setting' => $setting,
            'qrCode' => $qrCode,
            'isCreditNote' => $isCreditNote,
            'sellerName' => $sellerName,
            'sellerAddressLine' => $sellerAddressLine,
            'buyerName' => $buyerName,
            'buyerVat' => $buyerVat,
            'buyerMobile' => $buyerMobile,
            'buyerAddressLine' => $buyerAddressLine,
            'lineRows' => $lineRows,
            'totalLineDiscount' => round($totalLineDiscount, 2),
            'invoiceDiscount' => $invoiceDiscount,
            'serviceFee' => $serviceFee,
            'serviceFeeTax' => $serviceFeeTax,
            'subtotalExVat' => $subtotalExVat,
            'vatTotal' => $vatTotal,
            'grandTotal' => $grandTotal,
            'paidAmount' => $paidAmount,
            'dueAmount' => $dueAmount,
            'amountWordsAr' => $this->amountInWords($grandTotal, 'ar'),
            'amountWordsEn' => $this->amountInWords($grandTotal, 'en'),
            'docTitleAr' => $isCreditNote
                ? __('zatca::lang.doc_credit_note')
                : __('zatca::lang.doc_tax_invoice'),
            'docTitleEn' => $isCreditNote ? 'Credit Note' : 'Tax Invoice',
            'statusLabel' => $statusLabel,
            'paymentStatusLabel' => $paymentStatusLabel,
            'paymentStatusLabelEn' => $paymentStatusLabelEn,
            'issueDate' => $issueDate,
            'dueDate' => $dueDate,
            'parentRef' => optional($transaction->parentSell)->ref_no,
            'syncedAt' => $sync->synced_at?->format('Y-m-d H:i') ?: '—',
            'logoSrc' => $logoSrc,
            'primaryColor' => $primaryColor,
            'primaryRgb' => $primaryRgb,
            'primarySoft' => $primarySoft,
            'primarySoft2' => $primarySoft2,
        ];
    }

    /**
     * @return array{seq:int,name:string,sku:string,note:string,qty:float,unit:string,unit_price:float,discount:float,tax_percent:string,tax:float,total:float}
     */
    private function mapPdfLine(TransactionSellLine|TransactionePurchasesLine $line, int $seq): array
    {
        $qty = (float) ($line->qyt ?: 1);
        if ($qty <= 0) {
            $qty = 1.0;
        }

        $discount = $this->resolveLineDiscount($line, $qty);
        $lineNet = round((float) ($line->total_before_vat ?: 0), 2);
        if ($lineNet <= 0) {
            $unit = (float) ($line->unit_price ?: 0);
            $lineNet = round(($unit * $qty) - $discount, 2);
        }
        if ($lineNet < 0) {
            $lineNet = 0.0;
        }

        $grossBeforeDiscount = round($lineNet + $discount, 2);
        $unitPrice = $qty > 0 ? round($grossBeforeDiscount / $qty, 2) : 0.0;
        $tax = round((float) ($line->tax_value ?: 0), 2);
        $total = round((float) ($line->unit_price_inc_tax ?: ($lineNet + $tax)), 2);

        $taxPercentRaw = TransactionLineTaxRate::displayPercent(
            $line->tax_id !== null ? (string) $line->tax_id : null
        );
        if ($taxPercentRaw === '--' || $taxPercentRaw === '') {
            $taxPercent = $lineNet > 0 && $tax > 0
                ? (string) round(($tax / $lineNet) * 100, 2)
                : '0';
        } else {
            $taxPercent = preg_replace('/[^\d.]+/', '', (string) $taxPercentRaw) ?: '0';
        }

        $product = $line->product;
        $name = (string) ($product?->name_ar ?: $product?->name_en ?: ('#'.$line->product_id));
        $sku = trim((string) ($product?->barcode ?? ''));
        $note = trim((string) ($line->note ?? $line->sell_line_note ?? ''));
        // Avoid UnitTransfer->units1 (product_unit) — table may be absent on some tenants.
        $unit = '';

        return [
            'seq' => $seq,
            'name' => $name,
            'sku' => $sku,
            'note' => $note,
            'qty' => $qty,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'tax_percent' => $taxPercent,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    private function resolveLineDiscount(TransactionSellLine|TransactionePurchasesLine $line, float $qty): float
    {
        $amount = (float) ($line->discount_amount ?: 0);
        if ($amount <= 0) {
            return 0.0;
        }

        $type = strtolower((string) ($line->discount_type ?: 'fixed'));
        if (in_array($type, ['percentage', 'percent', '%'], true)) {
            $base = (float) ($line->unit_price_before_discount ?: $line->unit_price ?: 0) * $qty;

            return round($base * ($amount / 100), 2);
        }

        return round($amount, 2);
    }

    private function resolveInvoiceDiscount(Transaction $transaction): float
    {
        $amount = (float) ($transaction->discount_amount ?: 0);
        if ($amount <= 0) {
            return 0.0;
        }

        $type = strtolower((string) ($transaction->discount_type ?: 'fixed'));
        if (in_array($type, ['percentage', 'percent', '%'], true)) {
            $base = (float) ($transaction->total_before_tax ?: 0);

            return round($base * ($amount / 100), 2);
        }

        return round($amount, 2);
    }

    private function amountInWords(float $amount, string $locale): string
    {
        $amount = round(abs($amount), 2);
        $major = (int) floor($amount);
        $minor = (int) round(($amount - $major) * 100);

        try {
            $formatter = new NumberFormatter($locale === 'ar' ? 'ar' : 'en', NumberFormatter::SPELLOUT);
            $majorWords = $formatter->format($major);
            $minorWords = $minor > 0 ? $formatter->format($minor) : null;
        } catch (\Throwable) {
            return number_format($amount, 2).' SAR';
        }

        if ($locale === 'ar') {
            $text = $majorWords.' ريالاً';
            if ($minorWords) {
                $text .= ' و '.$minorWords.' هللة';
            }

            return $text.' فقط لا غير';
        }

        $text = ucfirst((string) $majorWords).' Saudi Riyal';
        if ($minorWords) {
            $text .= ' and '.$minorWords.' Halala';
        }

        return $text.' only';
    }

    /**
     * @return array{0: Transaction, 1: ZatcaInvoiceSync, 2: ZatcaSetting, 3: string}
     */
    private function loadSyncedDocument(int $transactionId): array
    {
        $transaction = Transaction::query()
            ->with([
                'client.billingAddress',
                'sell_lines.product',
                'purchases_lines.product',
                'parentSell',
                'payment',
            ])
            ->whereIn('type', ['sell', 'sell-return'])
            ->findOrFail($transactionId);

        $sync = ZatcaInvoiceSync::query()
            ->where('transaction_id', $transaction->id)
            ->where('status', ZatcaInvoiceSync::STATUS_SYNCED)
            ->firstOrFail();

        $setting = ZatcaSetting::current();

        $qrPayload = $sync->qr_tlv;
        if (! $qrPayload) {
            abort(422, __('zatca::lang.document_qr_missing'));
        }

        $qrCode = QrCode::format('svg')->size(155)->margin(1)->generate($qrPayload);
        $qrCode = preg_replace('/<\?xml[^>]*\?>/i', '', (string) $qrCode) ?? (string) $qrCode;
        $qrCode = preg_replace('/<!DOCTYPE[^>]*>/i', '', $qrCode) ?? $qrCode;
        // Keep SVG sizing predictable for mPDF.
        if (! str_contains($qrCode, 'width=')) {
            $qrCode = preg_replace('/<svg\b/i', '<svg width="145" height="145"', $qrCode, 1) ?? $qrCode;
        }

        return [$transaction, $sync, $setting, trim($qrCode)];
    }
}
