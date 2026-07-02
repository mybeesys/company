<?php

namespace Modules\Screen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreenPairingPin extends Model
{
    protected $table = 'screen_pairing_pins';

    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    public static function hashPin(string $pin): string
    {
        return hash_hmac('sha256', trim($pin), (string) config('app.key'));
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return $this->used_at === null && ! $this->isExpired();
    }
}
