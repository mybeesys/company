<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CentralSubscribeHandoff
{
    /**
     * Create a one-time URL that logs the central user into /subscribe.
     */
    public function createUrl(int $userId, ?int $companyId = null, string $redirectTo = '/subscribe'): string
    {
        $base = rtrim((string) config('referrals.central_app_url', config('app.url')), '/');

        if (! Schema::connection('mysql')->hasTable('subscription_handoff_tokens')) {
            return $base.'/subscribe';
        }

        $plain = Str::random(64);
        $ttlMinutes = (int) config('referrals.handoff_ttl_minutes', 5);

        DB::connection('mysql')->table('subscription_handoff_tokens')->insert([
            'token_hash' => hash('sha256', $plain),
            'user_id' => $userId,
            'company_id' => $companyId,
            'redirect_to' => $redirectTo,
            'expires_at' => now()->addMinutes(max(1, $ttlMinutes)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Opportunistic cleanup of expired tokens
        DB::connection('mysql')->table('subscription_handoff_tokens')
            ->where('expires_at', '<', now()->subDay())
            ->delete();

        return $base.'/subscribe/handoff/'.$plain;
    }
}
