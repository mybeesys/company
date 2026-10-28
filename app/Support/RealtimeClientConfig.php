<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Socket.IO client settings for Flutter (waiter, kitchen, POS).
 * Production: same origin as REST — no :0 and no :3001 on the public URL.
 */
final class RealtimeClientConfig
{
    /**
     * @return array{
     *     socket_url: string,
     *     socket_path: string,
     *     socket_port: null|int,
     *     transports: list<string>,
     *     schema_version: int
     * }
     */
    public static function forRequest(?Request $request = null): array
    {
        $request ??= request();

        return self::build(
            scheme: $request->getScheme(),
            host: $request->getHost(),
            port: self::effectivePort($request),
        );
    }

    /**
     * @return array{
     *     socket_url: string,
     *     socket_path: string,
     *     socket_port: null|int,
     *     transports: list<string>,
     *     schema_version: int
     * }
     */
    public static function forTenantDomain(string $domain, ?string $scheme = null): array
    {
        $scheme = $scheme ?? (request()->isSecure() ? 'https' : 'http');

        return self::build($scheme, self::normalizeHost($domain), null);
    }

    /**
     * @return array{
     *     socket_url: string,
     *     socket_path: string,
     *     socket_port: null|int,
     *     transports: list<string>,
     *     schema_version: int
     * }
     */
    private static function build(string $scheme, string $host, ?int $port): array
    {
        return [
            'socket_url' => self::origin($scheme, $host, $port),
            'socket_path' => (string) config('realtime.client_path', '/socket.io/'),
            'socket_port' => $port,
            'transports' => array_values(config('realtime.client_transports', ['websocket', 'polling'])),
            'schema_version' => (int) config('realtime.schema_version', 1),
        ];
    }

    public static function origin(string $scheme, string $host, ?int $port = null): string
    {
        $host = self::normalizeHost($host);
        if ($port !== null && $port > 0) {
            return "{$scheme}://{$host}:{$port}";
        }

        return "{$scheme}://{$host}";
    }

    private static function effectivePort(Request $request): ?int
    {
        $port = (int) $request->getPort();
        if ($port <= 0) {
            return null;
        }

        $default = $request->isSecure() ? 443 : 80;
        if ($port === $default) {
            return null;
        }

        if (! config('realtime.expose_dev_port', false)) {
            return null;
        }

        return $port;
    }

    private static function normalizeHost(string $host): string
    {
        return trim($host);
    }
}
