<?php

namespace Modules\General\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\General\Models\NotificationSettingParameter;
use RuntimeException;

final class WhatsAppCloudApiClient
{
    public const TOKEN_KEY = 'WHATSAPP_CLOUD_TOKEN';

    public const PHONE_NUMBER_ID_KEY = 'WHATSAPP_CLOUD_PHONE_NUMBER_ID';

    public const API_VERSION_KEY = 'WHATSAPP_CLOUD_API_VERSION';

    public const DEFAULT_API_VERSION = 'v21.0';

    public const CTA_BUTTON_TEXT = 'تحميل / Download';

    public static function isConfigured(): bool
    {
        return self::token() !== '' && self::phoneNumberId() !== '';
    }

    public static function token(): string
    {
        return trim((string) self::setting(self::TOKEN_KEY));
    }

    public static function phoneNumberId(): string
    {
        return trim((string) self::setting(self::PHONE_NUMBER_ID_KEY));
    }

    public static function apiVersion(): string
    {
        $version = trim((string) self::setting(self::API_VERSION_KEY));

        return $version !== '' ? $version : self::DEFAULT_API_VERSION;
    }

    /**
     * Upload a PDF and return Meta media id.
     */
    public static function uploadDocument(string $binary, string $filename): string
    {
        self::assertConfigured();

        $response = self::http()
            ->attach('file', $binary, $filename, ['Content-Type' => 'application/pdf'])
            ->post(self::baseUrl().'/'.self::phoneNumberId().'/media', [
                'messaging_product' => 'whatsapp',
                'type' => 'application/pdf',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(self::errorMessage($response->json(), 'Failed to upload invoice PDF to WhatsApp.'));
        }

        $mediaId = (string) ($response->json('id') ?? '');
        if ($mediaId === '') {
            throw new RuntimeException('WhatsApp media upload returned no id.');
        }

        return $mediaId;
    }

    /**
     * Send interactive CTA URL button (eXtra-style Download button — no raw URL in body).
     *
     * @return array<string, mixed>
     */
    public static function sendCtaUrl(string $to, string $body, string $url, string $buttonText = self::CTA_BUTTON_TEXT): array
    {
        self::assertConfigured();

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => preg_replace('/\D+/', '', $to) ?: $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'cta_url',
                'body' => [
                    'text' => mb_substr($body, 0, 1024),
                ],
                'action' => [
                    'name' => 'cta_url',
                    'parameters' => [
                        'display_text' => mb_substr($buttonText, 0, 20),
                        'url' => $url,
                    ],
                ],
            ],
        ];

        return self::sendMessage($payload);
    }

    /**
     * Send PDF document so the customer can download immediately inside WhatsApp.
     *
     * @return array<string, mixed>
     */
    public static function sendDocument(string $to, string $mediaId, string $filename, string $caption = ''): array
    {
        self::assertConfigured();

        $document = [
            'id' => $mediaId,
            'filename' => $filename,
        ];
        if (trim($caption) !== '') {
            $document['caption'] = mb_substr($caption, 0, 1024);
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => preg_replace('/\D+/', '', $to) ?: $to,
            'type' => 'document',
            'document' => $document,
        ];

        return self::sendMessage($payload);
    }

    /**
     * Preferred invoice share:
     * - Public HTTPS → native CTA “تحميل / Download” button (no raw URL in the bubble).
     * - Otherwise → upload PDF and send as a document (tap to download inside WhatsApp).
     *
     * @return array{document:?array<string,mixed>, cta:?array<string,mixed>, mode:string}
     */
    public static function sendInvoiceShare(
        string $to,
        string $bodyWithoutUrl,
        string $pdfBinary,
        string $filename,
        ?string $publicDownloadUrl = null,
    ): array {
        if (self::isPublicHttpsUrl($publicDownloadUrl)) {
            try {
                $ctaResult = self::sendCtaUrl($to, $bodyWithoutUrl, (string) $publicDownloadUrl);

                return [
                    'document' => null,
                    'cta' => $ctaResult,
                    'mode' => 'cta',
                ];
            } catch (\Throwable $e) {
                Log::warning('WhatsApp CTA button send failed; falling back to document.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $mediaId = self::uploadDocument($pdfBinary, $filename);
        $documentResult = self::sendDocument($to, $mediaId, $filename, $bodyWithoutUrl);

        return [
            'document' => $documentResult,
            'cta' => null,
            'mode' => 'document',
        ];
    }

    public static function isPublicHttpsUrl(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        if (! str_starts_with($url, 'https://')) {
            return false;
        }

        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost') || $host === '127.0.0.1') {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function sendMessage(array $payload): array
    {
        $response = self::http()->post(self::baseUrl().'/'.self::phoneNumberId().'/messages', $payload);

        if (! $response->successful()) {
            throw new RuntimeException(self::errorMessage($response->json(), 'Failed to send WhatsApp message.'));
        }

        return $response->json() ?? [];
    }

    private static function http(): PendingRequest
    {
        return Http::withToken(self::token())
            ->acceptJson()
            ->timeout(60);
    }

    private static function baseUrl(): string
    {
        return 'https://graph.facebook.com/'.self::apiVersion();
    }

    private static function assertConfigured(): void
    {
        if (! self::isConfigured()) {
            throw new RuntimeException(__('general::lang.whatsapp_cloud_not_configured'));
        }
    }

    /**
     * @param  mixed  $json
     */
    private static function errorMessage($json, string $fallback): string
    {
        if (is_array($json)) {
            $message = $json['error']['message'] ?? null;
            if (is_string($message) && $message !== '') {
                return $message;
            }
        }

        return $fallback;
    }

    private static function setting(string $key): ?string
    {
        try {
            $value = NotificationSettingParameter::query()->where('key', $key)->value('value');
        } catch (\Throwable) {
            return null;
        }

        return is_string($value) ? $value : null;
    }
}
