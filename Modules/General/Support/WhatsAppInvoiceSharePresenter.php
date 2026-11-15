<?php

namespace Modules\General\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Modules\Accounting\Support\LedgerStatementPresenter;
use Modules\General\Models\NotificationSettingParameter;
use Modules\General\Models\Transaction;

final class WhatsAppInvoiceSharePresenter
{
    public const TEMPLATE_KEY = 'WHATSAPP_INVOICE_TEMPLATE';

    public const LINK_TTL_DAYS = 7;

    /** @var list<string> */
    public const SHAREABLE_TYPES = ['sell', 'sell-return'];

    public static function supports(Transaction $transaction): bool
    {
        return in_array((string) $transaction->type, self::SHAREABLE_TYPES, true)
            && ! $transaction->isDraft();
    }

    public static function defaultTemplate(): string
    {
        // WhatsApp formatting: *bold* — keep lines short and bilingual for clarity.
        return implode("\n", [
            '*عزيزنا العميل / Dear Customer*',
            '{customer_name}',
            '',
            'نشكر لكم زيارتكم وثقتكم بـ *{company_name}*.',
            'Thank you for visiting *{company_name}*.',
            '',
            'يمكنكم الاطلاع على فاتورة مشترياتكم من الرابط أدناه.',
            'Please find your purchase invoice via the secure link below.',
            '',
            '────────────────────',
            '*تفاصيل الفاتورة / Invoice details*',
            'رقم الفاتورة / Invoice No.: *{invoice_no}*',
            'التاريخ / Date: {date}',
            'المبلغ / Amount: *{amount} SAR*',
            '────────────────────',
            '',
            '*لتحميل الفاتورة يرجى الضغط على الرابط التالي*',
            '*To download the invoice, please tap the link below*',
            '',
            '{download_link}',
            '',
            '_الرابط آمن وصالح لمدة 7 أيام_',
            '_This secure link is valid for 7 days_',
            '',
            'مع أطيب التحيات / Best regards',
            '*{company_name}*',
        ]);
    }

    public static function template(): string
    {
        try {
            $stored = NotificationSettingParameter::query()
                ->where('key', self::TEMPLATE_KEY)
                ->value('value');
        } catch (\Throwable) {
            $stored = null;
        }

        $stored = is_string($stored) ? trim($stored) : '';

        return $stored !== '' ? $stored : self::defaultTemplate();
    }

    /**
     * @return array{
     *   transaction_id:int,
     *   ref_no:string,
     *   customer_name:string,
     *   phone:string,
     *   phone_display:string,
     *   message:string,
     *   download_url:string,
     *   wa_url:string,
     *   expires_at:string
     * }
     */
    public static function build(Transaction $transaction, ?object $company = null): array
    {
        if (! self::supports($transaction)) {
            abort(422, __('general::lang.whatsapp_share_unsupported'));
        }

        $transaction->loadMissing('client');
        $company ??= InvoicePdfRenderer::company();
        $localeAr = str_starts_with(strtolower((string) app()->getLocale()), 'ar');
        $companyName = $company
            ? LedgerStatementPresenter::companyDisplayName($company, $localeAr)
            : (string) config('app.name', 'My Bee');
        if ($companyName === '') {
            $companyName = trim((string) ($company->name ?? $company->name_ar ?? '')) ?: 'My Bee';
        }

        $customerName = trim((string) ($transaction->client?->name ?? '')) ?: __('general::lang.whatsapp_share_customer_fallback');
        $phoneRaw = trim((string) ($transaction->client?->mobile_number ?? $transaction->client?->phone_number ?? ''));
        $phone = self::normalizePhone($phoneRaw);
        $downloadUrl = self::signedPublicUrl($transaction);
        $amount = number_format((float) ($transaction->final_total ?: 0), 2);
        $date = Carbon::parse($transaction->transaction_date)->format('Y-m-d');

        $message = self::renderTemplate(self::template(), [
            'customer_name' => $customerName,
            'invoice_no' => (string) $transaction->ref_no,
            'amount' => $amount,
            'date' => $date,
            'company_name' => $companyName,
            'download_link' => $downloadUrl,
        ]);

        return [
            'transaction_id' => (int) $transaction->id,
            'ref_no' => (string) $transaction->ref_no,
            'customer_name' => $customerName,
            'phone' => $phone,
            'phone_display' => $phoneRaw !== '' ? $phoneRaw : $phone,
            'message' => $message,
            'download_url' => $downloadUrl,
            'wa_url' => self::waUrl($phone, $message),
            'expires_at' => now()->addDays(self::LINK_TTL_DAYS)->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, string>  $vars
     */
    public static function renderTemplate(string $template, array $vars): string
    {
        $replacements = [];
        foreach ($vars as $key => $value) {
            $replacements['{'.$key.'}'] = $value;
        }

        return strtr($template, $replacements);
    }

    public static function signedPublicUrl(Transaction $transaction): string
    {
        return URL::temporarySignedRoute(
            'public.invoice.show',
            now()->addDays(self::LINK_TTL_DAYS),
            ['transaction' => $transaction->id]
        );
    }

    public static function signedPdfUrl(Transaction $transaction): string
    {
        return URL::temporarySignedRoute(
            'public.invoice.pdf',
            now()->addDays(self::LINK_TTL_DAYS),
            ['transaction' => $transaction->id]
        );
    }

    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';
        if ($digits === '') {
            return '';
        }

        // Saudi local mobile 05xxxxxxxx → 9665xxxxxxxx
        if (preg_match('/^05\d{8}$/', $digits)) {
            return '966'.substr($digits, 1);
        }

        // 5xxxxxxxx (9 digits) → 9665xxxxxxxx
        if (preg_match('/^5\d{8}$/', $digits)) {
            return '966'.$digits;
        }

        // 00966... → 966...
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    public static function waUrl(string $phone, string $message): string
    {
        $query = http_build_query(['text' => $message], '', '&', PHP_QUERY_RFC3986);
        if ($phone !== '') {
            return 'https://wa.me/'.$phone.'?'.$query;
        }

        return 'https://wa.me/?'.$query;
    }
}
