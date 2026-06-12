<?php

namespace Modules\Screen\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Screen\Models\Device;
use Symfony\Component\HttpFoundation\Response;

class EnsureScreenPlayerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Device) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = $user->currentAccessToken();
        if (! $token || ! $token->can('screen:player')) {
            return response()->json(['message' => __('screen::general.screen_player_token_required')], 403);
        }

        return $next($request);
    }
}
