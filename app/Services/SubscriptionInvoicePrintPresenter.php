<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Support\LedgerStatementPresenter;
use NumberFormatter;
use RuntimeException;
use Throwable;

/**
 * Builds print data for SaaS subscription invoices using the same
 * unified invoice print template as sales/purchases documents.
 */
final class SubscriptionInvoicePrintPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function build(int $subscriptionId, ?int $companyId = null, bool $forPdf = false): array
    {
        $companyId = $companyId ?: (function_exists('get_company_id') ? (int) get_company_id() : 0);
        if ($companyId <= 0) {
            throw new RuntimeException('Company context is required.');
        }

        $overview = app(SubscriptionOverviewService::class)->forCompany($companyId);
        $invoice = collect($overview['invoices'])->first(
            fn (array $row) => (int) ($row['subscription_id'] ?? 0) === $subscriptionId
        );

        if (! $invoice) {
            throw new RuntimeException('Subscription invoice not found for this company.');
        }

        $company = DB::connection('mysql')->table('companies')->find($companyId);
        if (! $company) {
            throw new RuntimeException('Company not found.');
        }

        $buyer = self::companyParty($company);
        $seller = self::platformParty();

        $lineRows = self::mapLineItems($invoice);
        $discountTotal = round(array_sum(array_map(
            static fn (array $row) => (float) ($row['discount'] ?? 0),
            $lineRows
        )), 2);

        $positiveLines = array_sum(array_map(
            static fn (array $row) => max(0, (float) ($row['total'] ?? 0)),
            $lineRows
        ));
        $negativeLines = abs(array_sum(array_map(
            static fn (array $row) => min(0, (float) ($row['total'] ?? 0)),
            $lineRows
        )));

        $grandTotal = round((float) ($invoice['amount'] ?? 0), 2);
        $subtotalExVat = $lineRows !== []
            ? round(max(0, $positiveLines - $negativeLines), 2)
            : $grandTotal;
        if ($subtotalExVat <= 0) {
            $subtotalExVat = $grandTotal;
        }

        $primaryColor = (string) config('zatca.pdf_primary_color', '#e9b71f');
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $primaryColor)) {
            $primaryColor = '#e9b71f';
        }
        $pr = hexdec(substr($primaryColor, 1, 2));
        $pg = hexdec(substr($primaryColor, 3, 2));
        $pb = hexdec(substr($primaryColor, 5, 2));
        $blend = static fn (int $c, float $alpha): int => (int) round(255 + ($c - 255) * $alpha);
        $primarySoft = sprintf('#%02X%02X%02X', $blend($pr, 0.10), $blend($pg, 0.10), $blend($pb, 0.10));

        $refNo = (string) ($invoice['id'] ?? ('SUB-'.$subscriptionId));
        $issueDate = ! empty($invoice['date'])
            ? Carbon::parse($invoice['date'])->format('Y-m-d')
            : now()->format('Y-m-d');

        $periodLabel = match ($invoice['period'] ?? null) {
            'Year', 'year' => 'سنوي / Yearly',
            'Month', 'month' => 'شهري / Monthly',
            default => (string) ($invoice['period'] ?? ''),
        };

        $qrPayload = json_encode([
            'type' => 'subscription_invoice',
            'ref' => $refNo,
            'company_id' => $companyId,
            'amount' => $grandTotal,
            'currency' => $invoice['currency'] ?? 'SAR',
            'date' => $issueDate,
        ], JSON_UNESCAPED_UNICODE);

        $qrCode = '';
        try {
            if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
                $qrCode = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(150)
                    ->margin(1)
                    ->generate((string) $qrPayload);
                $qrCode = preg_replace('/<\?xml.*?\?>/', '', $qrCode) ?? $qrCode;
            }
        } catch (Throwable) {
            $qrCode = '';
        }

        $transaction = (object) [
            'ref_no' => $refNo,
            'transaction_date' => $issueDate,
            'due_date' => $issueDate,
            'type' => 'subscription',
            'payment_status' => 'paid',
        ];

        return [
            'transaction' => $transaction,
            'company' => $company,
            'qrCode' => $qrCode,
            'forPdf' => $forPdf,
            'autoPrint' => ! $forPdf,
            'isPurchaseSide' => false,
            'isReturn' => false,
            'docTitleAr' => 'فاتورة اشتراك',
            'docTitleEn' => 'Subscription Invoice',
            'docBadgeAr' => 'اشتراك'.($periodLabel !== '' ? ' · '.$periodLabel : ''),
            'docBadgeEn' => 'Subscription',
            'seller' => $seller,
            'buyer' => $buyer,
            'sellerRoleAr' => 'مقدّم الخدمة',
            'sellerRoleEn' => 'Service Provider',
            'buyerRoleAr' => 'المشترك',
            'buyerRoleEn' => 'Subscriber',
            'lineRows' => $lineRows,
            'totalLineDiscount' => $discountTotal + $negativeLines,
            'invoiceDiscount' => 0.0,
            'subtotalExVat' => $subtotalExVat,
            'vatTotal' => 0.0,
            'grandTotal' => $grandTotal,
            'paidAmount' => $grandTotal,
            'dueAmount' => 0.0,
            'amountWordsAr' => self::amountInWords($grandTotal, 'ar'),
            'amountWordsEn' => self::amountInWords($grandTotal, 'en'),
            'paymentStatusLabel' => __('general::lang.paid'),
            'paymentStatusLabelEn' => 'Paid',
            'issueDate' => $issueDate,
            'dueDate' => $issueDate,
            'parentRef' => null,
            'logoSrc' => LedgerStatementPresenter::companyLogoSrc($company, $forPdf)
                ?: asset('assets/media/logos/1-11.png'),
            'primaryColor' => $primaryColor,
            'primarySoft' => $primarySoft,
            'hasZatcaQr' => false,
            'afterPrintUrl' => url('/subscription'),
        ];
    }

    /**
     * @param  array<string, mixed>  $invoice
     * @return list<array{seq:int,name:string,sku:string,note:string,qty:float,unit:string,unit_price:float,discount:float,tax_percent:string,tax:float,total:float}>
     */
    private static function mapLineItems(array $invoice): array
    {
        $items = $invoice['line_items'] ?? [];
        if (! is_array($items) || $items === []) {
            $amount = round((float) ($invoice['amount'] ?? 0), 2);

            return [[
                'seq' => 1,
                'name' => (string) ($invoice['label'] ?? 'Subscription'),
                'sku' => 'SUB',
                'note' => 'فاتورة اشتراك / Subscription invoice',
                'qty' => 1.0,
                'unit' => '',
                'unit_price' => $amount,
                'discount' => 0.0,
                'tax_percent' => '0',
                'tax' => 0.0,
                'total' => $amount,
            ]];
        }

        $rows = [];
        $seq = 1;
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $total = round((float) ($item['total'] ?? 0), 2);
            $qty = max(1.0, (float) ($item['quantity'] ?? 1));
            $unitPrice = isset($item['unit_price'])
                ? round((float) $item['unit_price'], 2)
                : round($total / $qty, 2);
            $isDiscount = $total < 0 || (($item['type'] ?? '') === 'coupon');

            $rows[] = [
                'seq' => $seq++,
                'name' => (string) ($item['label'] ?? $item['key'] ?? 'Item'),
                'sku' => (string) ($item['key'] ?? ''),
                'note' => $isDiscount ? 'خصم / Discount' : 'بند اشتراك / Subscription item',
                'qty' => $qty,
                'unit' => '',
                'unit_price' => abs($unitPrice),
                'discount' => $isDiscount ? abs($total) : 0.0,
                'tax_percent' => '0',
                'tax' => 0.0,
                'total' => $isDiscount ? 0.0 : max(0, $total),
            ];
        }

        return $rows !== [] ? $rows : self::mapLineItems(array_merge($invoice, ['line_items' => []]));
    }

    /** @return array{name: string, address: string, vat: string, cr: string, mobile: string} */
    private static function platformParty(): array
    {
        return [
            'name' => (string) (config('app.platform_name') ?: 'My Bee'),
            'address' => (string) (config('app.platform_address') ?: ''),
            'vat' => preg_replace('/\D+/', '', (string) (config('app.platform_vat') ?: '')) ?: '',
            'cr' => (string) (config('app.platform_cr') ?: ''),
            'mobile' => (string) (config('app.platform_phone') ?: ''),
        ];
    }

    /** @return array{name: string, address: string, vat: string, cr: string, mobile: string} */
    private static function companyParty(object $company): array
    {
        $name = LedgerStatementPresenter::companyDisplayName($company, app()->getLocale() === 'ar');
        if ($name === '') {
            $name = trim((string) ($company->name ?? $company->name_ar ?? '')) ?: '—';
        }

        $address = implode(' / ', array_filter([
            trim((string) ($company->street_name ?? $company->address ?? $company->national_address ?? '')),
            trim((string) ($company->building_number ?? '')),
            trim((string) ($company->district ?? $company->state ?? '')),
            trim((string) ($company->city ?? '')),
            trim((string) ($company->postal_code ?? $company->zip_code ?? $company->zipcode ?? '')),
        ], static fn ($p) => $p !== ''));

        return [
            'name' => $name,
            'address' => $address,
            'vat' => preg_replace('/\D+/', '', (string) ($company->tax_number ?? '')) ?: '',
            'cr' => trim((string) ($company->commercial_register ?? '')),
            'mobile' => trim((string) ($company->phone ?? $company->owner_phone_number ?? '')),
        ];
    }

    private static function amountInWords(float $amount, string $locale): string
    {
        $amount = round(abs($amount), 2);
        $major = (int) floor($amount);
        $minor = (int) round(($amount - $major) * 100);

        try {
            $formatter = new NumberFormatter($locale === 'ar' ? 'ar' : 'en', NumberFormatter::SPELLOUT);
            $majorWords = $formatter->format($major);
            $minorWords = $minor > 0 ? $formatter->format($minor) : null;
        } catch (Throwable) {
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
}
