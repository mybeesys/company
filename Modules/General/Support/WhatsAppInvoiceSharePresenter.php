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

    public const TTL_HOURS_KEY = 'WHATSAPP_INVOICE_LINK_TTL_HOURS';

    /** Default: 24 hours (not a fixed week). */
    public const DEFAULT_TTL_HOURS = 24;

    public const MIN_TTL_HOURS = 1;

    public const MAX_TTL_HOURS = 168;

    /** @deprecated Use linkTtlHours() — kept for callers that still reference days. */
    public const LINK_TTL_DAYS = 1;

    /** @var list<int> */
    public const TTL_HOUR_OPTIONS = [1, 6, 12, 24, 48, 72, 168];

    /** @var list<string> */
    public const SHAREABLE_TYPES = ['sell', 'sell-return'];

    public static function supports(Transaction $transaction): bool
    {
        return in_array((string) $transaction->type, self::SHAREABLE_TYPES, true)
            && ! $transaction->isDraft();
    }

    public static function defaultTemplate(): string
    {
        // Fallback for wa.me only (shows a link). Cloud API uses defaultApiTemplate().
        return implode("\n", [
            'عزيزي العميل،',
            'Dear Customer,',
            '*{customer_name}*',
            '',
            'نشكر لكم زيارتكم لـ *{company_name}*.',
            'Thank you for visiting *{company_name}*.',
            '',
            'نرفق لكم فاتورة مشترياتكم.',
            'Please find your purchase invoice below.',
            '',
            'رقم الفاتورة / Invoice: *{invoice_no}*',
            'التاريخ / Date: {date}',
            'المبلغ / Amount: *{amount} SAR*',
            '',
            '*لتحميل الفاتورة يرجى الضغط على تحميل*',
            '*To download the invoice, please click on Download*',
            '',
            '⬇️ *تحميل / Download*',
            '{download_link}',
            '',
            '*{company_name}*',
        ]);
    }

    /** Body for Cloud API: no raw URL — Download is a native WhatsApp button / PDF attachment. */
    public static function defaultApiTemplate(): string
    {
        return implode("\n", [
            'عزيزي العميل،',
            'Dear Customer,',
            '*{customer_name}*',
            '',
            'نشكر لكم زيارتكم لـ *{company_name}*.',
            'Thank you for visiting *{company_name}*.',
            '',
            'نرفق لكم فاتورة مشترياتكم.',
            'Please find your purchase invoice below.',
            '',
            'رقم الفاتورة / Invoice: *{invoice_no}*',
            'التاريخ / Date: {date}',
            'المبلغ / Amount: *{amount} SAR*',
            '',
            '*لتحميل الفاتورة يرجى الضغط على تحميل*',
            '*To download the invoice, please click on Download*',
            '',
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

    public static function linkTtlHours(): int
    {
        try {
            $stored = NotificationSettingParameter::query()
                ->where('key', self::TTL_HOURS_KEY)
                ->value('value');
        } catch (\Throwable) {
            $stored = null;
        }

        $hours = is_numeric($stored) ? (int) $stored : self::DEFAULT_TTL_HOURS;

        return max(self::MIN_TTL_HOURS, min(self::MAX_TTL_HOURS, $hours));
    }

    public static function linkExpiresAt(): Carbon
    {
        return now()->addHours(self::linkTtlHours());
    }

    public static function linkValidityLabel(int $hours): string
    {
        if ($hours % 24 === 0) {
            $days = (int) ($hours / 24);
            if ($days === 1) {
                return '24 ساعة / 24 hours';
            }

            return $days.' أيام / '.$days.' days';
        }

        if ($hours === 1) {
            return 'ساعة واحدة / 1 hour';
        }

        return $hours.' ساعات / '.$hours.' hours';
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
     *   pdf_url:string,
     *   wa_url:string,
     *   cloud_configured:bool,
     *   send_mode:string,
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
        $ttlHours = self::linkTtlHours();
        $expiresAt = self::linkExpiresAt();
        $landingUrl = self::signedPublicUrl($transaction, autoDownload: true);
        $pdfUrl = self::signedPdfUrl($transaction);
        $amount = number_format((float) ($transaction->final_total ?: 0), 2);
        $date = Carbon::parse($transaction->transaction_date)->format('Y-m-d');
        $cloudConfigured = WhatsAppCloudApiClient::isConfigured();

        $vars = [
            'customer_name' => $customerName,
            'invoice_no' => (string) $transaction->ref_no,
            'amount' => $amount,
            'date' => $date,
            'company_name' => $companyName,
            'download_link' => $cloudConfigured ? '' : $landingUrl,
            'link_validity' => self::linkValidityLabel($ttlHours),
        ];

        if ($cloudConfigured) {
            // Prefer API body with no URL; if a custom template still has {download_link}, strip links.
            $message = self::stripLinksFromMessage(self::renderTemplate(self::apiBodyTemplate(), $vars));
        } else {
            $message = self::renderTemplate(self::template(), $vars);
        }

        return [
            'transaction_id' => (int) $transaction->id,
            'ref_no' => (string) $transaction->ref_no,
            'customer_name' => $customerName,
            'phone' => $phone,
            'phone_display' => $phoneRaw !== '' ? $phoneRaw : $phone,
            'message' => $message,
            'download_url' => $landingUrl,
            'pdf_url' => $pdfUrl,
            'wa_url' => self::waUrl($phone, $message),
            'cloud_configured' => $cloudConfigured,
            'send_mode' => $cloudConfigured ? 'cloud_api' : 'wa_me',
            'link_ttl_hours' => $ttlHours,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    public static function apiBodyTemplate(): string
    {
        // Custom templates often include a link line; for Cloud API we always start from the no-URL default
        // unless the stored template has no {download_link} placeholder.
        $stored = self::template();
        if (! str_contains($stored, '{download_link}') && ! str_contains($stored, 'http')) {
            return $stored;
        }

        return self::defaultApiTemplate();
    }

    public static function stripLinksFromMessage(string $message): string
    {
        $message = preg_replace('#https?://\S+#iu', '', $message) ?? $message;
        $message = preg_replace('/^\s*⬇️?\s*\*?تحميل\s*\/\s*Download\*?\s*$/miu', '', $message) ?? $message;
        $message = preg_replace("/[ \t]+\n/", "\n", $message) ?? $message;
        $message = preg_replace("/\n{3,}/", "\n\n", $message) ?? $message;

        return trim($message);
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

    public static function signedPublicUrl(Transaction $transaction, bool $autoDownload = false): string
    {
        $params = ['transaction' => $transaction->id];
        if ($autoDownload) {
            $params['auto'] = 1;
        }

        return URL::temporarySignedRoute(
            'public.invoice.show',
            self::linkExpiresAt(),
            $params
        );
    }

    public static function signedPdfUrl(Transaction $transaction): string
    {
        return URL::temporarySignedRoute(
            'public.invoice.pdf',
            self::linkExpiresAt(),
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
