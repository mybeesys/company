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

        // company-login يُخزّن التوكن على DB المركزي؛ طلبات admin/employee من دومين tenant
        if (function_exists('tenancy') && tenancy()->initialized) {
            $centralConnection = (string) config('tenancy.database.central_connection');

            if ($centralConnection !== '' && self::findValidToken($bearerToken, $centralConnection) !== null) {
                return true;
            }
        }

        return false;
    }

    private static function findValidToken(string $bearerToken, ?string $connection = null): ?PersonalAccessToken
    {
        $accessToken = self::findTokenOnConnection($bearerToken, $connection);
        if ($accessToken === null) {
            return null;
        }

        if ($accessToken->expires_at !== null && $accessToken->expires_at->isPast()) {
            return null;
        }

        return $accessToken;
    }

    private static function findTokenOnConnection(string $bearerToken, ?string $connection): ?PersonalAccessToken
    {
        $query = $connection !== null
            ? PersonalAccessToken::on($connection)
            : PersonalAccessToken::query();

        if (! str_contains($bearerToken, '|')) {
            return $query->where('token', hash('sha256', $bearerToken))->first();
        }

        [$id, $plainTextToken] = explode('|', $bearerToken, 2);

        /** @var PersonalAccessToken|null $accessToken */
        $accessToken = $query->whereKey($id)->first();
        if ($accessToken === null) {
            return null;
        }

        if (! hash_equals($accessToken->token, hash('sha256', $plainTextToken))) {
            return null;
        }

        return $accessToken;
    }
}
