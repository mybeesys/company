<?php

namespace Modules\General\Support;

use Modules\General\Services\Notifications\NotificationDispatcher;
use Throwable;

/**
 * Fire-and-forget wrapper so domain code never breaks on notification failures.
 */
final class SystemNotify
{
    /**
     * @param  array<string, scalar|null>  $vars
     * @param  array<string, mixed>  $meta
     */
    public static function send(
        string $key,
        array $vars = [],
        ?string $actionUrl = null,
        ?int $actorUserId = null,
        array $meta = []
    ): void {
        try {
            app(NotificationDispatcher::class)->dispatch($key, $vars, $actionUrl, $actorUserId, $meta);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
