<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CentralCompanyMembershipService;
use App\Services\CompanyTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CompanyAuthController extends Controller
{
    public function __construct(
        private readonly CompanyTokenService $tokens,
        private readonly CentralCompanyMembershipService $memberships,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'tenant_id' => ['nullable', 'string'],
            'client_type' => ['nullable', 'string', 'max:32'],
            'device_id' => ['nullable', 'string', 'max:128'],
            'revoke_previous' => ['nullable', 'boolean'],
        ]);

        $user = User::query()
            ->where('email', strtolower(trim($validated['email'])))
            ->whereNull('deleted_at')
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $companies = $this->memberships->companiesForEmail($user->email);
        if ($companies->isEmpty()) {
            return response()->json(['message' => __('company_not_found')], 403);
        }

        $tenantId = trim((string) ($validated['tenant_id'] ?? ''));
        if ($tenantId !== '') {
            if (! $this->memberships->userCanAccessTenant($user->email, $tenantId)) {
                return response()->json(['message' => __('company_not_found')], 403);
            }
        } else {
            $tenantId = (string) ($companies->first()->tenant_id ?? '');
        }

        if ($tenantId === '') {
            return response()->json(['message' => __('company_not_found')], 403);
        }

        $revokePrevious = array_key_exists('revoke_previous', $validated)
            ? (bool) $validated['revoke_previous']
            : null;

        $accessToken = $this->tokens->issue(
            $user,
            $validated['client_type'] ?? null,
            $validated['device_id'] ?? null,
            $revokePrevious,
        );

        return response()->json([
            'token' => $accessToken->plainTextToken,
            'tenant_id' => $tenantId,
            'expires_at' => $accessToken->accessToken->expires_at?->toIso8601String(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        if ($token === null || $token === '') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $this->tokens->revokePlainTextToken($token);

        return response()->json(['message' => 'Logged out']);
    }
}
