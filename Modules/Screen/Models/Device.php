<?php

namespace Modules\Screen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Modules\Establishment\Models\Establishment;

class Device extends Model
{
    use HasApiTokens;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'updated_at', 'created_at'];

    protected $table = 'screen_devices';

    /**
     * @var list<string>
     */
    protected $hidden = [
        'pin_hash',
        'pairing_token_hash',
    ];

    public function playlists()
    {
        return $this->morphToMany(Playlist::class, 'related', 'screen_playlists_relates');
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }

    public static function hashPairingToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public static function isValidExternalPairingId(string $pairingId): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/', strtolower($pairingId));
    }

    /**
     * @return array{plain: string, hash: string}
     */
    public static function generatePairingCredentials(): array
    {
        $plain = bin2hex(random_bytes(24));

        return ['plain' => $plain, 'hash' => self::hashPairingToken($plain)];
    }

    /**
     * تعيين رمز اقتران جديد (يُخزَّن المشتق فقط). يُرجع النص الصريح مرة واحدة لعرض QR.
     */
    public function assignNewPairingToken(): string
    {
        $creds = self::generatePairingCredentials();
        $this->forceFill(['pairing_token_hash' => $creds['hash']])->save();

        return $creds['plain'];
    }
}
