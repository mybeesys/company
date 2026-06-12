<?php

namespace App\Support;

use Laravel\Sanctum\PersonalAccessToken;

class SanctumBearerValidator
{
    public static function isValid(?string $bearerToken): bool
    {
        if ($bearerToken === null || $bearerToken === '') {
            return false;
        }

        $accessToken = PersonalAccessToken::findToken($bearerToken);
        if ($accessToken === null) {
            return false;
        }

        if ($accessToken->expires_at !== null && $accessToken->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
