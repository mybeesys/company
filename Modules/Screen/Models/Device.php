<?php

namespace Modules\Screen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
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

    /**
     * @return list<int> Device IDs that are missing or not linked to any of the given establishments.
     */
    public static function idsNotMatchingEstablishments(array $deviceIds, array $establishmentIds): array
    {
        $deviceIds = array_values(array_unique(array_filter(array_map('intval', $deviceIds))));
        $establishmentIds = array_values(array_unique(array_filter(array_map('intval', $establishmentIds))));

        if ($deviceIds === []) {
            return [];
        }

        if (! Schema::hasColumn('screen_devices', 'establishment_id')) {
            $foundIds = static::query()->whereIn('id', $deviceIds)->pluck('id')->map(fn ($id) => (int) $id)->all();

            return array_values(array_diff($deviceIds, $foundIds));
        }

        $validIds = static::query()
            ->whereIn('id', $deviceIds)
            ->whereIn('establishment_id', $establishmentIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_diff($deviceIds, $validIds));
    }

    public static function codesForIds(array $deviceIds): string
    {
        $deviceIds = array_values(array_unique(array_filter(array_map('intval', $deviceIds))));

        if ($deviceIds === []) {
            return '';
        }

        return static::query()
            ->whereIn('id', $deviceIds)
            ->orderBy('id')
            ->pluck('code')
            ->implode(', ');
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
