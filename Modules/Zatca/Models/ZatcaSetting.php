<?php

namespace Modules\Zatca\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ZatcaSetting extends Model
{
    protected $table = 'zatca_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'generated_credentials' => 'array',
        'credentials_generated_at' => 'datetime',
    ];

    protected $hidden = [
        'zatca_app_key',
        'otp',
        'generated_credentials',
    ];

    public function setZatcaAppKeyAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['zatca_app_key'] = null;

            return;
        }

        $this->attributes['zatca_app_key'] = Crypt::encryptString($value);
    }

    public function getZatcaAppKeyAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            // Legacy plaintext fallback if encryption was not used yet.
            return $value;
        }
    }

    public function isConfigured(): bool
    {
        return $this->status === 'configured'
            && is_array($this->generated_credentials)
            && $this->generated_credentials !== [];
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'configured' => 'badge-light-success',
            'failed' => 'badge-light-danger',
            default => 'badge-light-warning',
        };
    }

    /**
     * Singleton row per tenant (create empty pending shell if missing).
     */
    public static function current(): self
    {
        $row = static::query()->orderBy('id')->first();
        if ($row) {
            return $row;
        }

        return static::query()->create([
            'zatca_environment' => 'local',
            'seller_name' => '',
            'vat_number' => '',
            'commercial_registration_number' => '',
            'organization_name' => '',
            'country_code' => 'SA',
            'invoice_type' => '1100',
            'status' => 'pending',
        ]);
    }
}
