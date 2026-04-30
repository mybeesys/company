<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CleanJsonNoiseMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->shouldSanitize($request, $response)) {
            return $response;
        }

        $content = (string) $response->getContent();
        if ($content === '') {
            return $response;
        }

        $firstObject = strpos($content, '{');
        $firstArray = strpos($content, '[');
        $firstJsonStart = $this->minPositive($firstObject, $firstArray);

        if ($firstJsonStart === null || $firstJsonStart === 0) {
            return $response;
        }

        $cleaned = ltrim(substr($content, $firstJsonStart));
        json_decode($cleaned, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $response;
        }

        $response->setContent($cleaned);
        return $response;
    }

    private function shouldSanitize(Request $request, Response $response): bool
    {
        $contentType = (string) $response->headers->get('Content-Type', '');
        $isJsonResponse = str_contains(strtolower($contentType), 'application/json');

        return $isJsonResponse || $request->expectsJson() || $request->ajax();
    }

    private function minPositive($a, $b): ?int
    {
        $values = array_filter([$a, $b], static fn($v) => is_int($v) && $v >= 0);
        if (empty($values)) {
            return null;
        }

        return min($values);
    }
}

