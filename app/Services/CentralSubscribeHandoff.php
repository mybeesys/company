<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class CentralSubscribeHandoff
{
    /**
     * Create a one-time URL that logs the matching central user into /subscribe.
     *
     * Important: tenant auth user IDs are NOT the same as central `users.id`.
     * Always resolve the central user by email before inserting the handoff token.
     */
    public function createUrlForEmail(string $email, ?int $companyId = null, string $redirectTo = '/subscribe'): string
    {
        $centralUserId = $this->resolveCentralUserIdByEmail($email);

        if (! $centralUserId) {
            throw new RuntimeException('Central user not found for subscription handoff.');
        }

        return $this->createUrl($centralUserId, $companyId, $redirectTo);
    }

    /**
     * Create a one-time URL that logs the central user into /subscribe.
     *
     * @param  int  $userId  Central users.id (not tenant users.id)
     */
    public function createUrl(int $userId, ?int $companyId = null, string $redirectTo = '/subscribe'): string
    {
        $base = $this->centralBaseUrl();
        $central = $this->centralConnection();

        if (! Schema::connection($central)->hasTable('subscription_handoff_tokens')) {
            throw new RuntimeException('Subscription handoff tokens table is missing on the central database.');
        }

        $plain = Str::random(64);
        $ttlMinutes = (int) config('referrals.handoff_ttl_minutes', 5);

        DB::connection($central)->table('subscription_handoff_tokens')->insert([
            'token_hash' => hash('sha256', $plain),
            'user_id' => $userId,
            'company_id' => $companyId,
            'redirect_to' => $redirectTo,
            'expires_at' => now()->addMinutes(max(1, $ttlMinutes)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection($central)->table('subscription_handoff_tokens')
            ->where('expires_at', '<', now()->subDay())
            ->delete();

        return $base.'/subscribe/handoff/'.$plain;
    }

    /**
     * Prefer HTTPS so an HTTPS tenant can embed the handoff URL without mixed-content blocking.
     */
    protected function centralBaseUrl(): string
    {
        $base = rtrim((string) config('referrals.central_app_url', config('app.url')), '/');

        $requestIsHttps = request()->isSecure()
            || str_starts_with(strtolower((string) request()->header('X-Forwarded-Proto', '')), 'https');

        $host = parse_url($base, PHP_URL_HOST) ?: '';
        $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.test');

        if (($requestIsHttps || ! $isLocal) && str_starts_with(strtolower($base), 'http://')) {
            $base = 'https://'.substr($base, 7);
        }

        return rtrim($base, '/');
    }

    public function resolveCentralUserIdByEmail(?string $email): ?int
    {
        $email = strtolower(trim((string) $email));
        if ($email === '') {
            return null;
        }

        $central = $this->centralConnection();

        $id = DB::connection($central)
            ->table('users')
            ->where('email', $email)
            ->when(
                Schema::connection($central)->hasColumn('users', 'deleted_at'),
                fn ($q) => $q->whereNull('deleted_at')
            )
            ->value('id');

        return $id ? (int) $id : null;
    }

    protected function centralConnection(): string
    {
        $connection = (string) config('tenancy.database.central_connection', 'mysql');

        return $connection !== '' ? $connection : 'mysql';
    }
}
