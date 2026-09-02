<?php

namespace Modules\Employee\Support;

/**
 * Copy for EMS permission hints on the dashboard-role matrix.
 */
final class DashboardPermissionHint
{
    /**
     * @return array{title: string, body: string}
     */
    public static function permission(string $fullName, string $entityLabel): array
    {
        $parts = explode('.', $fullName, 3);
        $module = $parts[0] ?? '';
        $entity = $parts[1] ?? '';
        $action = $parts[2] ?? '';

        $exact = self::lookup('exact', $fullName);
        if (is_string($exact) && $exact !== '') {
            return [
                'title' => self::permissionTitle($entityLabel, $action),
                'body' => $exact,
            ];
        }

        $entityCopy = self::entity($module, $entity, $entityLabel);
        $actionCopy = self::actionBody($action, $entityLabel);

        $body = $actionCopy;
        if (($entityCopy['body'] ?? '') !== '') {
            $body = $entityCopy['body'].' '.$actionCopy;
        }

        return [
            'title' => self::permissionTitle($entityCopy['title'] !== '' ? $entityCopy['title'] : $entityLabel, $action),
            'body' => trim($body),
        ];
    }

    /**
     * @return array{title: string, body: string}
     */
    public static function entity(string $module, string $entity, string $entityLabel): array
    {
        $row = self::lookup('entities', $module.'.'.$entity);
        if (is_array($row)) {
            return [
                'title' => (string) ($row['title'] ?? $entityLabel),
                'body' => (string) ($row['body'] ?? ''),
            ];
        }

        if (is_string($row) && $row !== '') {
            return ['title' => $entityLabel, 'body' => $row];
        }

        if ($entity === 'all') {
            return [
                'title' => $entityLabel !== '' ? $entityLabel : __('employee::permissions.module_all_title'),
                'body' => __('employee::permissions.module_all_body', [
                    'module' => self::moduleLabel($module),
                ]),
            ];
        }

        return [
            'title' => $entityLabel,
            'body' => __('employee::permissions.entity_fallback', ['entity' => $entityLabel]),
        ];
    }

    /**
     * @return array{title: string, body: string}
     */
    public static function module(string $module): array
    {
        $row = self::lookup('modules', $module);
        $label = self::moduleLabel($module);

        if (is_array($row)) {
            return [
                'title' => (string) ($row['title'] ?? $label),
                'body' => (string) ($row['body'] ?? ''),
            ];
        }

        return [
            'title' => $label,
            'body' => is_string($row) && $row !== '' ? $row : __('employee::permissions.module_fallback', ['module' => $label]),
        ];
    }

    /**
     * @return array{title: string, body: string}
     */
    public static function moduleAll(string $module, string $action): array
    {
        return [
            'title' => __('employee::permissions.all_row_title', [
                'action' => self::actionLabel($action),
                'module' => self::moduleLabel($module),
            ]),
            'body' => __('employee::permissions.all_row_body', [
                'action' => self::actionLabel($action),
                'module' => self::moduleLabel($module),
            ]),
        ];
    }

    /**
     * @return array{title: string, body: string}
     */
    public static function column(string $action): array
    {
        return [
            'title' => self::actionLabel($action),
            'body' => (string) __('employee::permissions.columns.'.$action),
        ];
    }

    public static function actionLabel(string $action): string
    {
        return (string) __('employee::permissions.action_labels.'.$action);
    }

    private static function permissionTitle(string $entityLabel, string $action): string
    {
        return trim($entityLabel.' — '.self::actionLabel($action));
    }

    private static function actionBody(string $action, string $entityLabel): string
    {
        $key = 'employee::permissions.action_bodies.'.$action;
        $translated = __($key, ['entity' => $entityLabel]);

        return $translated === $key
            ? __('employee::permissions.action_bodies.show', ['entity' => $entityLabel])
            : (string) $translated;
    }

    private static function moduleLabel(string $module): string
    {
        $key = "employee::main.{$module}_management_module";
        $label = __($key);

        return $label === $key ? $module : (string) $label;
    }

    private static function lookup(string $group, string $key): mixed
    {
        $map = __('employee::permissions.'.$group);

        return is_array($map) ? ($map[$key] ?? null) : null;
    }
}
