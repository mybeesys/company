<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Friendly, promotional response when a host has no tenant workspace.
 */
final class MissingTenantGateway
{
    public static function toResponse(Request $request): Response
    {
        self::applyLocale($request);

        if ($request->expectsJson() || $request->is('api/*') || $request->is('api')) {
            return response()->json([
                'message' => __('errors.tenant_missing_json'),
                'host' => $request->getHost(),
            ], 404);
        }

        $central = rtrim((string) config('referrals.central_app_url', config('app.url', 'https://mybeesystem.net')), '/');

        return response()->view('errors.tenant-missing', [
            'host' => $request->getHost(),
            'brand' => brand_short_name(),
            'centralUrl' => $central,
            'loginUrl' => $central.'/login',
            'registerUrl' => $central.'/admin/register?step=plan',
            'subscribeUrl' => $central.'/subscribe',
        ], 404);
    }

    protected static function applyLocale(Request $request): void
    {
        $fromQuery = strtolower((string) $request->query('lang', ''));
        if (in_array($fromQuery, ['ar', 'en'], true)) {
            app()->setLocale($fromQuery);

            return;
        }

        $preferred = $request->getPreferredLanguage(['ar', 'en']);
        app()->setLocale($preferred === 'en' ? 'en' : 'ar');
    }
}
