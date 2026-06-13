<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

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

    public function issue(User $user, ?string $clientType = null, ?string $deviceId = null): NewAccessToken
    {
        $tokenName = $this->tokenName($clientType, $deviceId);

        // إلغاء توken نفس التطبيق/الجهاز فقط — وليس كل توkenات المستخدم
        $user->tokens()->where('name', $tokenName)->delete();

        return $user->createToken($tokenName, ['company:api']);
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
}
