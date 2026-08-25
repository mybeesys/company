<?php

namespace Modules\Zatca\Services;

use Bl\FatooraZatca\Invoices\B2B;
use Bl\FatooraZatca\Invoices\B2C;
use Illuminate\Support\Facades\DB;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use Modules\Zatca\Models\ZatcaSetting;
use RuntimeException;
use Throwable;

class ZatcaSellSyncService
{
    public function __construct(
        private readonly ZatcaCredentialService $credentials,
        private readonly ZatcaInvoiceMapper $mapper,
        private readonly ZatcaResponseFormatter $formatter
    ) {}

    /**
     * @return array{
     *   sync: ZatcaInvoiceSync,
     *   success: bool,
     *   message: string,
     *   feedback: array{ref: string, ok: bool, summary: string, errors: array, warnings: array, reporting_status: ?string}
     * }
     */
    public function syncOne(Transaction $transaction, string $reportType, ZatcaSetting $setting): array
    {
        $reportType = strtoupper($reportType);
        if (! in_array($reportType, ['B2C', 'B2B'], true)) {
            throw new RuntimeException(__('zatca::lang.report_type_invalid'));
        }

        if (! in_array((string) $transaction->type, ['sell', 'sell-return'], true)) {
            throw new RuntimeException(__('zatca::lang.sell_invoice_missing', ['id' => $transaction->id]));
        }

        if (! $setting->isConfigured()) {
            throw new RuntimeException(__('zatca::lang.send_requires_credentials'));
        }

        $transaction->loadMissing([
            'client.billingAddress',
            'sell_lines.product',
            'purchases_lines.product',
        ]);

        $isCreditNote = (string) $transaction->type === 'sell-return';

        if (! $isCreditNote && $transaction->sell_lines->isEmpty()) {
            $fallbackLines = TransactionSellLine::query()
                ->with('product')
                ->where('transaction_id', $transaction->id)
                ->where(function ($q) {
                    $q->whereNull('parent_id')->orWhere('parent_id', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('is_show')->orWhere('is_show', '1')->orWhere('is_show', 1);
                })
                ->get();
            $transaction->setRelation('sell_lines', $fallbackLines);
        }

        $sync = ZatcaInvoiceSync::forTransaction((int) $transaction->id);
        $sync->report_type = $reportType;
        $sync->synced_environment = (string) $setting->zatca_environment;
        $sync->last_attempt_at = now();
        $sync->last_error = null;
        $sync->save();

        try {
            return DB::transaction(function () use ($transaction, $reportType, $setting, $sync, $isCreditNote) {
                /** @var ZatcaSetting $lockedSetting */
                $lockedSetting = ZatcaSetting::query()
                    ->whereKey($setting->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->credentials->applyRuntimeConfig($lockedSetting);

                $seller = $this->mapper->toSeller($lockedSetting);
                $uuid = $this->mapper->ensureUuid($sync->invoice_uuid);
                $icv = ((int) $lockedSetting->invoice_counter) + 1;
                $previousHash = $lockedSetting->last_invoice_hash ?: null;

                if ($isCreditNote) {
                    $mapped = $this->mapper->toCreditNote($transaction, $icv, $uuid, $previousHash);
                    // Prefer same channel as the original invoice when available.
                    $parentType = strtoupper((string) ($mapped['parent_sync']->report_type ?? ''));
                    if (in_array($parentType, ['B2C', 'B2B'], true)) {
                        $reportType = $parentType;
                        $sync->report_type = $reportType;
                    }
                } else {
                    $mapped = $this->mapper->toInvoice($transaction, $icv, $uuid, $previousHash);
                }

                $invoice = $mapped['invoice'];

                if ($reportType === 'B2B') {
                    $clientTxn = $isCreditNote ? ($mapped['parent'] ?? $transaction) : $transaction;
                    $client = $this->mapper->toClient($clientTxn);
                    $reported = B2B::make($seller, $invoice, $client)->report();
                } else {
                    $reported = B2C::make($seller, $invoice)->report();
                }

                $payload = $reported->getResult();

                // Some gateways return HTTP 2xx with ERROR validation — treat as failure.
                $formatted = $this->formatter->fromPayload($payload);
                $hasHardError = $formatted['errors'] !== []
                    || in_array(strtoupper((string) ($formatted['reporting_status'] ?? '')), ['NOT_REPORTED', 'NOT_CLEARED', 'ERROR'], true)
                    || strtoupper((string) (($payload['validationResults']['status'] ?? ''))) === 'ERROR';

                if ($hasHardError) {
                    throw new RuntimeException(json_encode($payload, JSON_UNESCAPED_UNICODE));
                }

                $invoiceHash = $payload['invoiceHash'] ?? $reported->getInvoiceHash();
                $reportingStatus = $payload['reportingStatus']
                    ?? $payload['clearanceStatus']
                    ?? null;

                $qr = null;
                try {
                    $qr = $reported->getQr();
                } catch (Throwable) {
                    $qr = null;
                }

                $signedXml = null;
                try {
                    $signedXml = $reported->getClearedInvoice();
                } catch (Throwable) {
                    $signedXml = is_string($payload['clearedInvoice'] ?? null)
                        ? $payload['clearedInvoice']
                        : null;
                }

                $sync->invoice_uuid = $uuid;
                $sync->status = ZatcaInvoiceSync::STATUS_SYNCED;
                $sync->reporting_status = is_string($reportingStatus) ? $reportingStatus : null;
                $sync->invoice_hash = $invoiceHash;
                $sync->qr_tlv = $qr;
                $sync->cleared_invoice = $signedXml;
                $sync->synced_environment = (string) $lockedSetting->zatca_environment;
                $sync->response_payload = $this->sanitizePayload($payload);
                $sync->last_error = null;
                $sync->synced_at = now();
                $sync->save();

                $lockedSetting->last_invoice_hash = $invoiceHash;
                $lockedSetting->invoice_counter = $icv;
                $lockedSetting->save();

                $statusForMessage = is_string($reportingStatus)
                    ? $this->formatter->translateReportingStatus($reportingStatus)
                    : 'OK';

                $message = __('zatca::lang.sync_success', [
                    'ref' => $transaction->ref_no,
                    'type' => $isCreditNote
                        ? __('zatca::lang.doc_credit_note').' / '.$reportType
                        : $reportType,
                    'status' => $statusForMessage,
                ]);

                return [
                    'sync' => $sync->fresh(),
                    'success' => true,
                    'message' => $message,
                    'feedback' => [
                        'ref' => (string) $transaction->ref_no,
                        'ok' => true,
                        'summary' => $message,
                        'errors' => [],
                        'warnings' => $formatted['warnings'],
                        'reporting_status' => is_string($reportingStatus) ? $reportingStatus : null,
                        'reporting_status_label' => is_string($reportingStatus)
                            ? $this->formatter->translateReportingStatus($reportingStatus)
                            : null,
                    ],
                ];
            });
        } catch (Throwable $e) {
            $formatted = $this->formatter->fromThrowable($e);
            $plain = $this->formatter->toPlainText($formatted);

            $sync->status = ZatcaInvoiceSync::STATUS_FAILED;
            $sync->reporting_status = $formatted['reporting_status'];
            $sync->last_error = $plain;
            $sync->response_payload = [
                'formatted' => $formatted,
                'raw_message' => mb_substr($e->getMessage(), 0, 2000),
            ];
            $sync->synced_at = null;
            $sync->save();

            return [
                'sync' => $sync->fresh(),
                'success' => false,
                'message' => $formatted['summary'],
                'feedback' => [
                    'ref' => (string) $transaction->ref_no,
                    'ok' => false,
                    'summary' => $formatted['summary'],
                    'errors' => $formatted['errors'],
                    'warnings' => $formatted['warnings'],
                    'reporting_status' => $formatted['reporting_status'],
                    'reporting_status_label' => $formatted['reporting_status_label']
                        ?? $this->formatter->translateReportingStatus($formatted['reporting_status'] ?? null),
                ],
            ];
        }
    }

    /**
     * @param  array<int, array{id:int, report_type:string}>  $items
     * @return array{
     *   success: int,
     *   failed: int,
     *   messages: array<int, string>,
     *   feedback: array<int, array{ref: string, ok: bool, summary: string, errors: array, warnings: array, reporting_status: ?string}>
     * }
     */
    public function syncMany(array $items, ZatcaSetting $setting): array
    {
        $success = 0;
        $failed = 0;
        $messages = [];
        $feedback = [];

        foreach ($items as $item) {
            $transaction = Transaction::query()
                ->whereIn('type', ['sell', 'sell-return'])
                ->whereIn('status', ['approved', 'final'])
                ->with(['client.billingAddress', 'sell_lines.product', 'purchases_lines.product'])
                ->find($item['id'] ?? null);

            if (! $transaction) {
                $failed++;
                $msg = __('zatca::lang.sell_invoice_missing', ['id' => $item['id'] ?? '?']);
                $messages[] = $msg;
                $feedback[] = [
                    'ref' => '#'.($item['id'] ?? '?'),
                    'ok' => false,
                    'summary' => $msg,
                    'errors' => [['code' => 'MISSING', 'message' => $msg]],
                    'warnings' => [],
                    'reporting_status' => null,
                    'reporting_status_label' => null,
                ];

                continue;
            }

            $result = $this->syncOne(
                $transaction,
                (string) ($item['report_type'] ?? 'B2C'),
                $setting->fresh()
            );

            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }

            $messages[] = $transaction->ref_no.': '.$result['message'];
            $feedback[] = $result['feedback'];
        }

        return compact('success', 'failed', 'messages', 'feedback');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        $keep = $payload;
        if (isset($keep['clearedInvoice']) && is_string($keep['clearedInvoice']) && strlen($keep['clearedInvoice']) > 4000) {
            $keep['clearedInvoice'] = substr($keep['clearedInvoice'], 0, 4000).'…[truncated]';
        }

        return $keep;
    }
}
