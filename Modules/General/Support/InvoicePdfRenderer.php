<?php

namespace Modules\General\Support;

use Illuminate\Support\Facades\DB;
use Modules\General\Models\Transaction;
use Modules\General\Utils\TransactionUtils;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use Mpdf\Mpdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;

final class InvoicePdfRenderer
{
    public static function company(): ?object
    {
        if (! function_exists('get_company_id')) {
            return null;
        }

        $id = (int) (get_company_id() ?: 0);
        if ($id <= 0) {
            return null;
        }

        return DB::connection('mysql')->table('companies')->find($id) ?: null;
    }

    public static function qrSvg(Transaction $transaction, object $company): string
    {
        $payload = self::qrPayload($transaction, $company);
        $svg = (string) QrCode::format('svg')->size(150)->margin(1)->generate($payload);

        return self::normalizeQrSvg($svg);
    }

    /**
     * @return array{binary: string, filename: string}
     */
    public static function binary(Transaction $transaction, ?object $company = null): array
    {
        $company ??= self::company();
        if (! $company) {
            abort(404);
        }

        $qrCode = self::qrSvg($transaction, $company);
        $filename = preg_replace('/[^\w\-]+/u', '-', (string) $transaction->ref_no).'.pdf';

        if (UnifiedInvoicePrintPresenter::supports($transaction->type)) {
            $data = UnifiedInvoicePrintPresenter::build($transaction, $company, $qrCode, true);
            $html = view('general::transactions.unified-invoice-print', $data)->render();

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'default_font' => 'DejaVuSans',
                'default_font_size' => 10,
                'autoLangToFont' => true,
                'autoScriptToLang' => true,
                'margin_top' => 10,
                'margin_bottom' => 14,
                'margin_left' => 10,
                'margin_right' => 10,
            ]);
            $mpdf->SetDirectionality('rtl');
            $mpdf->SetTitle(($data['docTitleAr'] ?? 'Invoice').' - '.(string) $transaction->ref_no);
            $mpdf->WriteHTML($html);

            return [
                'binary' => $mpdf->Output($filename, 'S'),
                'filename' => $filename,
            ];
        }

        $html = view('general::transactions.print', compact('transaction', 'qrCode', 'company'))->render();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 12,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);
        $mpdf->WriteHTML($html);

        return [
            'binary' => $mpdf->Output($filename, 'S'),
            'filename' => $filename,
        ];
    }

    public static function download(Transaction $transaction, ?object $company = null): Response
    {
        $pdf = self::binary($transaction, $company);

        return response($pdf['binary'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf['filename'].'"',
        ]);
    }

    private static function qrPayload(Transaction $transaction, object $company): string
    {
        $sync = ZatcaInvoiceSync::query()
            ->where('transaction_id', $transaction->id)
            ->where('status', ZatcaInvoiceSync::STATUS_SYNCED)
            ->whereNotNull('qr_tlv')
            ->first();

        if ($sync?->qr_tlv) {
            return (string) $sync->qr_tlv;
        }

        return (new TransactionUtils)->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction->transaction_date,
            $transaction->final_total,
            $transaction->tax_amount
        );
    }

    private static function normalizeQrSvg(string $svg): string
    {
        $svg = preg_replace('/<\?xml[^>]*\?>/i', '', $svg) ?? $svg;
        $svg = preg_replace('/<!DOCTYPE[^>]*>/i', '', $svg) ?? $svg;

        return $svg;
    }
}
