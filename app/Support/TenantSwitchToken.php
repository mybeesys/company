<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class TenantSwitchToken
{
    public static function issue(string $email, string $tenantId, int $ttlSeconds = 90): string
    {
        $secret = static::secret();

        $payload = [
            'email' => strtolower(trim($email)),
            'tenant_id' => $tenantId,
            'exp' => now()->addSeconds($ttlSeconds)->getTimestamp(),
            'nonce' => (string) Str::uuid(),
        ];

        $encoded = static::base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $encoded, $secret);

        return $encoded.'.'.$signature;
    }

    /**
     * @return array{email: string, tenant_id: string, exp: int, nonce: string}
     */
    public static function verify(string $token, string $expectedTenantId): array
    {
        $secret = static::secret();

        [$encoded, $signature] = array_pad(explode('.', $token, 2), 2, null);

        if ($encoded === null || $signature === null) {
            throw new RuntimeException('Invalid switch token format.');
        }

        $expectedSignature = hash_hmac('sha256', $encoded, $secret);

        if (! hash_equals($expectedSignature, $signature)) {
            throw new RuntimeException('Invalid switch token signature.');
        }

        /** @var array{email?: string, tenant_id?: string, exp?: int, nonce?: string} $payload */
        $payload = json_decode(static::base64UrlDecode($encoded), true, 512, JSON_THROW_ON_ERROR);

        if (
            empty($payload['email'])
            || empty($payload['tenant_id'])
            || empty($payload['exp'])
            || empty($payload['nonce'])
        ) {
            throw new RuntimeException('Invalid switch token payload.');
        }

        if ($payload['tenant_id'] !== $expectedTenantId) {
            throw new RuntimeException('Switch token tenant mismatch.');
        }

        if ((int) $payload['exp'] < now()->getTimestamp()) {
            throw new RuntimeException('Switch token expired.');
        }

        $cacheKey = 'tenant_switch_nonce:'.$payload['nonce'];

        if (Cache::has($cacheKey)) {
            throw new RuntimeException('Switch token already used.');
        }

        Cache::put($cacheKey, true, max(60, (int) $payload['exp'] - now()->getTimestamp()));

        return [
            'email' => (string) $payload['email'],
            'tenant_id' => (string) $payload['tenant_id'],
            'exp' => (int) $payload['exp'],
            'nonce' => (string) $payload['nonce'],
        ];
    }

    protected static function secret(): string
    {
        $secret = (string) config('tenant_switch.secret', '');

        if ($secret === '') {
            throw new RuntimeException('TENANT_SWITCH_SECRET is not configured.');
        }

        return $secret;
    }

    protected static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected static function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
    }
}
