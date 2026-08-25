<?php

namespace Modules\Zatca\Services;

use Throwable;

/**
 * Turns raw ZATCA / package exception payloads into clean UI-facing messages.
 */
class ZatcaResponseFormatter
{
    /**
     * @return array{
     *   summary: string,
     *   errors: array<int, array{code: string, message: string}>,
     *   warnings: array<int, array{code: string, message: string}>,
     *   reporting_status: string|null,
     *   reporting_status_label: string|null
     * }
     */
    public function fromThrowable(Throwable $e): array
    {
        $raw = $e->getMessage();
        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            return $this->fromPayload($decoded);
        }

        // Nested JSON sometimes wrapped in text.
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $nested = json_decode($m[0], true);
            if (is_array($nested)) {
                return $this->fromPayload($nested);
            }
        }

        [$code, $message] = $this->classifyThrowableMessage($raw);

        return [
            'summary' => $message,
            'errors' => [['code' => $code, 'message' => $message]],
            'warnings' => [],
            'reporting_status' => null,
            'reporting_status_label' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *   summary: string,
     *   errors: array<int, array{code: string, message: string}>,
     *   warnings: array<int, array{code: string, message: string}>,
     *   reporting_status: string|null,
     *   reporting_status_label: string|null
     * }
     */
    public function fromPayload(array $payload): array
    {
        $validation = is_array($payload['validationResults'] ?? null)
            ? $payload['validationResults']
            : [];

        $errors = $this->mapMessages($validation['errorMessages'] ?? []);
        $warnings = $this->mapMessages($validation['warningMessages'] ?? []);

        // Older/onboarding style payloads.
        if ($errors === [] && ! empty($payload['errors']) && is_array($payload['errors'])) {
            foreach ($payload['errors'] as $err) {
                $errors[] = [
                    'code' => 'ZATCA',
                    'message' => $this->localizeMessage((string) $err, 'ZATCA'),
                ];
            }
        }

        if ($errors === [] && ! empty($payload['dispositionMessage'])) {
            $errors[] = [
                'code' => 'ZATCA',
                'message' => $this->localizeMessage((string) $payload['dispositionMessage'], (string) $payload['dispositionMessage']),
            ];
        }

        $reportingStatus = $payload['reportingStatus']
            ?? $payload['clearanceStatus']
            ?? null;

        $reportingStatusLabel = is_string($reportingStatus)
            ? $this->translateReportingStatus($reportingStatus)
            : null;

        $summary = $errors[0]['message']
            ?? ($reportingStatusLabel
                ? __('zatca::lang.sync_status_label', ['status' => $reportingStatusLabel])
                : __('zatca::lang.sync_failed_generic'));

        if (count($errors) > 1) {
            $summary .= ' '. __('zatca::lang.sync_more_errors', ['count' => count($errors) - 1]);
        }

        return [
            'summary' => $summary,
            'errors' => $errors,
            'warnings' => $warnings,
            'reporting_status' => is_string($reportingStatus) ? $reportingStatus : null,
            'reporting_status_label' => $reportingStatusLabel,
        ];
    }

    public function translateReportingStatus(?string $status): string
    {
        if ($status === null || $status === '') {
            return '';
        }

        $key = 'zatca::lang.reporting_status_'.strtolower($status);
        $translated = __($key);

        return $translated !== $key ? $translated : $status;
    }

    /**
     * One-line text for DB `last_error` column (user-facing, no raw EXCEPTION dumps).
     *
     * @param  array{summary: string, errors: array<int, array{code: string, message: string}>}  $formatted
     */
    public function toPlainText(array $formatted): string
    {
        $lines = [];
        foreach ($formatted['errors'] as $error) {
            $message = trim((string) ($error['message'] ?? ''));
            if ($message === '') {
                continue;
            }
            $lines[] = $message;
        }

        if ($lines === []) {
            return $formatted['summary'];
        }

        return implode("\n", array_values(array_unique($lines)));
    }

    /**
     * @param  mixed  $messages
     * @return array<int, array{code: string, message: string}>
     */
    private function mapMessages(mixed $messages): array
    {
        if (! is_array($messages)) {
            return [];
        }

        $out = [];
        foreach ($messages as $msg) {
            if (! is_array($msg)) {
                continue;
            }
            $code = (string) ($msg['code'] ?? '');
            $raw = (string) ($msg['message'] ?? $code);
            $out[] = [
                'code' => $this->displayCode($code),
                'message' => $this->localizeMessage($raw, $code),
            ];
        }

        return $out;
    }

    /**
     * @return array{0: string, 1: string} [code, message]
     */
    private function classifyThrowableMessage(string $raw): array
    {
        $lower = strtolower($raw);

        if (
            str_contains($lower, 'connection failed to zatca')
            || str_contains($lower, 'could not resolve host')
            || str_contains($lower, 'failed to connect')
            || str_contains($lower, 'connection timed out')
            || str_contains($lower, 'curl error')
            || str_contains($lower, 'ssl')
            || str_contains($lower, 'certificate verify failed')
            || str_contains($lower, 'network is unreachable')
        ) {
            return ['CONNECTION', __('zatca::lang.zatca_err_connection')];
        }

        if (str_contains($lower, 'certificate') && str_contains($lower, 'permission')) {
            return ['CERTIFICATE', __('zatca::lang.zatca_err_certificate_permissions')];
        }

        if (str_contains($lower, 'unauthorized') || str_contains($lower, '401') || str_contains($lower, '403')) {
            return ['AUTH', __('zatca::lang.zatca_err_auth')];
        }

        $localized = $this->localizeMessage($raw, '');
        if ($localized !== $this->truncate($raw, 240)) {
            return ['ZATCA', $localized];
        }

        return ['ERROR', __('zatca::lang.sync_failed_generic')];
    }

    private function displayCode(string $code): string
    {
        $code = trim($code);
        if ($code === '' || strtoupper($code) === 'EXCEPTION') {
            return '';
        }

        return $code;
    }

    private function localizeMessage(string $raw, string $code): string
    {
        $map = [
            'certificate-permissions' => __('zatca::lang.zatca_err_certificate_permissions'),
            'BR-KSA-37' => __('zatca::lang.zatca_err_building_number'),
            'BR-KSA-66' => __('zatca::lang.zatca_err_postal_code'),
            'BR-KSA-F-08' => __('zatca::lang.zatca_err_crn'),
            'BR-CO-13' => __('zatca::lang.zatca_err_totals'),
            'BR-CO-18' => __('zatca::lang.zatca_err_vat_breakdown'),
            'BR-KSA-84' => __('zatca::lang.zatca_err_vat_rate'),
            'BR-S-01' => __('zatca::lang.zatca_err_vat_standard_breakdown'),
            'BR-S-05' => __('zatca::lang.zatca_err_vat_standard_rate'),
            'BR-KSA-EN16931-08' => __('zatca::lang.zatca_err_tax_total_with_subtotals'),
            'BR-KSA-EN16931-09' => __('zatca::lang.zatca_err_tax_total_without_subtotals'),
            'XSD_ZATCA_VALID' => __('zatca::lang.zatca_info_xsd_ok'),
            'NOT_COMPLIANT' => __('zatca::lang.zatca_err_not_compliant'),
            'NOT_REPORTED' => __('zatca::lang.zatca_err_not_reported'),
            'REPORTED' => __('zatca::lang.reporting_status_reported'),
            'CLEARED' => __('zatca::lang.reporting_status_cleared'),
            'NOT_CLEARED' => __('zatca::lang.reporting_status_not_cleared'),
            'CONNECTION' => __('zatca::lang.zatca_err_connection'),
            'AUTH' => __('zatca::lang.zatca_err_auth'),
            'EXCEPTION' => __('zatca::lang.sync_failed_generic'),
        ];

        if (isset($map[$code])) {
            return $map[$code];
        }

        // Soft-match known English fragments.
        $lower = strtolower($raw);
        if (
            str_contains($lower, 'connection failed to zatca')
            || str_contains($lower, 'verify your certificate and network')
            || str_contains($lower, 'failed to connect')
            || str_contains($lower, 'connection timed out')
            || str_contains($lower, 'curl error')
        ) {
            return __('zatca::lang.zatca_err_connection');
        }
        if (str_contains($lower, 'vat number that exists in the authentication certificate')) {
            return __('zatca::lang.zatca_err_certificate_permissions');
        }
        if (str_contains($lower, 'building number must contain 4 digits')) {
            return __('zatca::lang.zatca_err_building_number');
        }
        if (str_contains($lower, 'postal code') && str_contains($lower, '5 digits')) {
            return __('zatca::lang.zatca_err_postal_code');
        }
        if (str_contains($lower, "scheme id 'crn'") || str_contains($lower, 'other seller id')) {
            return __('zatca::lang.zatca_err_crn');
        }
        if (str_contains($lower, 'vat breakdown group') || str_contains($lower, 'bg-23')) {
            return __('zatca::lang.zatca_err_vat_breakdown');
        }
        if (str_contains($lower, 'must be limited to one of the following values (5 or 15)')) {
            return __('zatca::lang.zatca_err_vat_rate');
        }

        // Never dump raw English package exceptions as the primary UX message.
        if (str_contains($lower, 'please try again') || str_starts_with($lower, 'exception')) {
            return __('zatca::lang.sync_failed_generic');
        }

        return $this->truncate($raw, 240);
    }

    private function truncate(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        return mb_strlen($text) > $max
            ? mb_substr($text, 0, $max - 1).'…'
            : $text;
    }
}
