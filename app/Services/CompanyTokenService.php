<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * إصدار توكنات company-login متعددة — تطبيق لكل client_type (waiter / cashier / kitchen…).
 * لا تستخدم tokens()->delete() بدون اسم — ذلك يلغي جلسة التطبيقات الأخرى.
 */
class CompanyTokenService
{
    /** @var list<string> */
    private const ALLOWED_CLIENT_TYPES = [
        'waiter',
        'cashier',
        'kitchen',
        'pos',
        'admin',
        'screen',
        'mobile',
        'default',
    ];

    public function issue(
        User $user,
        ?string $clientType = null,
        ?string $deviceId = null,
        ?bool $revokePrevious = null,
    ): NewAccessToken {
        $tokenName = $this->tokenName($clientType, $deviceId);
        $shouldRevoke = $revokePrevious ?? (bool) config('company_api.revoke_previous_on_login', false);

        if ($shouldRevoke) {
            $user->tokens()->where('name', $tokenName)->delete();
        }

        return $user->createToken(
            $tokenName,
            ['company:api'],
            $this->resolveExpiration(),
        );
    }

    public function revokePlainTextToken(string $plainTextToken): void
    {
        $connection = (string) config('tenancy.database.central_connection', 'mysql');

        if (! str_contains($plainTextToken, '|')) {
            PersonalAccessToken::on($connection)
                ->where('token', hash('sha256', $plainTextToken))
                ->delete();

            return;
        }

        [$id] = explode('|', $plainTextToken, 2);
        PersonalAccessToken::on($connection)->whereKey($id)->delete();
    }

    public function tokenName(?string $clientType, ?string $deviceId = null): string
    {
        $type = $this->normalizeClientType($clientType);
        $deviceId = trim((string) $deviceId);

        if ($deviceId !== '') {
            return "company-api:{$type}:{$deviceId}";
        }

        return "company-api:{$type}";
    }

    public function normalizeClientType(?string $clientType): string
    {
        $type = strtolower(trim((string) $clientType));

        return in_array($type, self::ALLOWED_CLIENT_TYPES, true) ? $type : 'default';
    }

    private function resolveExpiration(): ?\DateTimeInterface
    {
        $minutes = config('company_api.token_expiration_minutes');
        if ($minutes === null || $minutes === '' || (int) $minutes <= 0) {
            return null;
        }

        return now()->addMinutes((int) $minutes);
    }
}
