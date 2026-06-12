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

        if (self::findValidToken($bearerToken) !== null) {
            return true;
        }

        // company-login يُخزّن التوكن على DB المركزي؛ employee-login يُستدعى من دومين tenant
        if (function_exists('tenancy') && tenancy()->initialized) {
            return self::findValidToken($bearerToken, onCentral: true) !== null;
        }

        return false;
    }

    private static function findValidToken(string $bearerToken, bool $onCentral = false): ?PersonalAccessToken
    {
        $lookup = function () use ($bearerToken): ?PersonalAccessToken {
            $accessToken = PersonalAccessToken::findToken($bearerToken);
            if ($accessToken === null) {
                return null;
            }

            if ($accessToken->expires_at !== null && $accessToken->expires_at->isPast()) {
                return null;
            }

            return $accessToken;
        };

        if ($onCentral && function_exists('tenancy') && tenancy()->initialized) {
            return tenancy()->central($lookup);
        }

        return $lookup();
    }
}
