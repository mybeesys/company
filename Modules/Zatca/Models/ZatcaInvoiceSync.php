<?php

namespace Modules\Zatca\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\General\Models\Transaction;

class ZatcaInvoiceSync extends Model
{
    protected $table = 'zatca_invoice_syncs';

    protected $fillable = [
        'transaction_id',
        'invoice_uuid',
        'report_type',
        'synced_environment',
        'status',
        'reporting_status',
        'last_error',
        'response_payload',
        'invoice_hash',
        'qr_tlv',
        'cleared_invoice',
        'last_attempt_at',
        'synced_at',
    ];

    protected $casts = [
        'response_payload' => 'array',
        'last_attempt_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_SYNCED = 'synced';

    public const STATUS_FAILED = 'failed';

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SYNCED => __('zatca::lang.sync_status_synced'),
            self::STATUS_FAILED => __('zatca::lang.sync_status_failed'),
            default => __('zatca::lang.sync_status_pending'),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_SYNCED => 'badge-light-success',
            self::STATUS_FAILED => 'badge-light-danger',
            default => 'badge-light-warning',
        };
    }

    public static function forTransaction(int $transactionId): self
    {
        return static::query()->firstOrCreate(
            ['transaction_id' => $transactionId],
            ['status' => self::STATUS_PENDING]
        );
    }
}
