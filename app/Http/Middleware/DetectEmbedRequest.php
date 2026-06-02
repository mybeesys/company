<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectEmbedRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->boolean('embed') && $this->isIframeNavigation($request)) {
            $request->merge(['embed' => 1]);
        }

        return $next($request);
    }

    protected function isIframeNavigation(Request $request): bool
    {
        $dest = strtolower((string) $request->headers->get('Sec-Fetch-Dest', ''));

        return in_array($dest, ['iframe', 'nested'], true);
    }
}
