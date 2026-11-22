<?php

namespace Modules\General\Support;

/**
 * Repair legacy / mistyped notification deep-links without rewriting stored rows.
 */
final class NotificationUrlNormalizer
{
    public static function normalize(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $parts = parse_url($url);
        $path = $parts['path'] ?? '';
        if ($path === '') {
            return $url;
        }

        $replacements = [
            '/journal-entry' => '/journal-entry-index',
        ];

        foreach ($replacements as $from => $to) {
            if ($path === $from || rtrim($path, '/') === $from) {
                $path = $to;
                break;
            }
        }

        $rebuilt = ($parts['scheme'] ?? null) && ($parts['host'] ?? null)
            ? ($parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : ''))
            : '';

        $rebuilt .= $path;
        if (! empty($parts['query'])) {
            $rebuilt .= '?'.$parts['query'];
        }
        if (! empty($parts['fragment'])) {
            $rebuilt .= '#'.$parts['fragment'];
        }

        return $rebuilt !== '' ? $rebuilt : $url;
    }
}
