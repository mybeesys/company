<?php

namespace Modules\Zatca\Support;

use Illuminate\Support\Str;
use Modules\General\Models\Transaction;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use Modules\Zatca\Services\ZatcaResponseFormatter;

/**
 * Inline ZATCA sync indicator for sell / sell-return listing tables (no extra column).
 */
final class ZatcaTransactionListBadge
{
    public static function enabled(): bool
    {
        return (bool) config('zatca.show_in_menu', true);
    }

    public static function appliesTo(Transaction $transaction): bool
    {
        return self::enabled()
            && in_array((string) $transaction->type, ['sell', 'sell-return'], true);
    }

    public static function render(Transaction $transaction): string
    {
        if (! self::appliesTo($transaction)) {
            return '';
        }

        if ($transaction->isDraft()) {
            return self::badge(
                'draft',
                __('zatca::lang.list_sync_draft_title'),
                e(__('zatca::lang.list_sync_draft_hint'))
            );
        }

        $sync = $transaction->zatcaInvoiceSync;
        if (! $sync) {
            return self::badge(
                'pending',
                __('zatca::lang.list_sync_pending_title'),
                e(__('zatca::lang.list_sync_pending_hint'))
            );
        }

        return match ((string) $sync->status) {
            ZatcaInvoiceSync::STATUS_SYNCED => self::renderSynced($sync),
            ZatcaInvoiceSync::STATUS_FAILED => self::renderFailed($sync),
            default => self::badge(
                'pending',
                __('zatca::lang.list_sync_pending_title'),
                self::pendingDetail($sync)
            ),
        };
    }

    private static function renderSynced(ZatcaInvoiceSync $sync): string
    {
        $formatter = app(ZatcaResponseFormatter::class);
        $reporting = $sync->reporting_status
            ? $formatter->translateReportingStatus((string) $sync->reporting_status)
            : null;

        $lines = [e(__('zatca::lang.list_sync_synced_hint'))];
        if ($reporting) {
            $lines[] = e(__('zatca::lang.list_sync_reporting_status', ['status' => $reporting]));
        }
        if ($sync->synced_at) {
            $lines[] = e(__('zatca::lang.list_sync_synced_at', [
                'at' => $sync->synced_at->format('Y-m-d H:i'),
            ]));
        }

        return self::badge(
            'synced',
            __('zatca::lang.list_sync_synced_title'),
            implode('<br>', $lines)
        );
    }

    private static function renderFailed(ZatcaInvoiceSync $sync): string
    {
        $reason = self::sanitizeTooltipText((string) ($sync->last_error ?: ''));
        if ($reason === '') {
            $reason = __('zatca::lang.list_sync_failed_hint');
        }

        $detail = e($reason);
        if ($sync->last_attempt_at) {
            $detail .= '<br>'.e(__('zatca::lang.list_sync_last_attempt', [
                'at' => $sync->last_attempt_at->format('Y-m-d H:i'),
            ]));
        }

        return self::badge(
            'failed',
            __('zatca::lang.list_sync_failed_title'),
            $detail
        );
    }

    private static function pendingDetail(ZatcaInvoiceSync $sync): string
    {
        $hint = e(__('zatca::lang.list_sync_pending_hint'));
        if ($sync->last_attempt_at) {
            $hint .= '<br>'.e(__('zatca::lang.list_sync_last_attempt', [
                'at' => $sync->last_attempt_at->format('Y-m-d H:i'),
            ]));
        }

        return $hint;
    }

    private static function badge(string $state, string $title, string $bodyHtml): string
    {
        $icon = match ($state) {
            'synced' => 'ki-check-circle',
            'failed' => 'ki-cross-circle',
            'draft' => 'ki-file-sheet',
            default => 'ki-cloud-add',
        };

        $tooltip = '<div class="zatca-sync-tip">'
            .'<div class="zatca-sync-tip-title">'.e($title).'</div>'
            .'<div class="zatca-sync-tip-body">'.$bodyHtml.'</div>'
            .'</div>';

        return '<span class="zatca-list-badge zatca-list-badge--'.$state.'"'
            .' data-zatca-tip="1"'
            .' data-bs-toggle="tooltip"'
            .' data-bs-placement="top"'
            .' data-bs-html="true"'
            .' data-bs-custom-class="zatca-sync-tooltip"'
            .' data-bs-title="'.htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8').'"'
            .' role="img"'
            .' aria-label="'.e($title).'">'
            .'<i class="ki-outline '.$icon.'"></i>'
            .'</span>';
    }

    private static function sanitizeTooltipText(string $text): string
    {
        $text = trim(strip_tags($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return Str::limit($text, 220);
    }
}
