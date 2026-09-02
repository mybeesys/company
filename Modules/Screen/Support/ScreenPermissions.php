<?php

namespace Modules\Screen\Support;

/**
 * Central Screens EMS permission names (must match dashboard-permissions.php).
 *
 * Catalog prefixes: screens.* (hub "main" + tab entities) and screen_module.all.*
 * (sidebar module). Wildcards only apply within the same prefix, so actions OR both.
 *
 * Tab entities (no print — preview is show):
 * screens.Ad materials | screens.Playlists | screens.Devices
 */
final class ScreenPermissions
{
    public const ALL_SHOW = 'screens.all.show';

    public const ALL_PRINT = 'screens.all.print';

    public const ALL_CREATE = 'screens.all.create';

    public const ALL_UPDATE = 'screens.all.update';

    public const ALL_DELETE = 'screens.all.delete';

    public const MAIN_SHOW = 'screens.main.show';

    public const MAIN_CREATE = 'screens.main.create';

    public const MAIN_UPDATE = 'screens.main.update';

    public const MAIN_DELETE = 'screens.main.delete';

    public const PROMO_SHOW = 'screens.Ad materials.show';

    public const PROMO_CREATE = 'screens.Ad materials.create';

    public const PROMO_UPDATE = 'screens.Ad materials.update';

    public const PROMO_DELETE = 'screens.Ad materials.delete';

    public const PLAYLIST_SHOW = 'screens.Playlists.show';

    public const PLAYLIST_CREATE = 'screens.Playlists.create';

    public const PLAYLIST_UPDATE = 'screens.Playlists.update';

    public const PLAYLIST_DELETE = 'screens.Playlists.delete';

    public const DEVICE_SHOW = 'screens.Devices.show';

    public const DEVICE_CREATE = 'screens.Devices.create';

    public const DEVICE_UPDATE = 'screens.Devices.update';

    public const DEVICE_DELETE = 'screens.Devices.delete';

    public const MODULE_ALL_SHOW = 'screen_module.all.show';

    public const MODULE_ALL_PRINT = 'screen_module.all.print';

    public const MODULE_ALL_CREATE = 'screen_module.all.create';

    public const MODULE_ALL_UPDATE = 'screen_module.all.update';

    public const MODULE_ALL_DELETE = 'screen_module.all.delete';

    /**
     * Any Screens show permission (hub, menu, navbar).
     *
     * @return list<string>
     */
    public static function action(string $action): array
    {
        return match ($action) {
            'show', 'create', 'update', 'delete' => array_values(array_unique([
                ...self::for('main', $action),
                self::crud('promos')[$action],
                self::crud('playlists')[$action],
                self::crud('devices')[$action],
            ])),
            default => throw new \InvalidArgumentException("Unknown screens EMS action [{$action}]"),
        };
    }

    /**
     * Entity gate: tab permission OR screens.main.* OR screen_module.all.*
     *
     * @return list<string>
     */
    public static function for(string $entity, string $action): array
    {
        $crud = self::crud($entity);
        if (! isset($crud[$action])) {
            throw new \InvalidArgumentException("Unknown screens EMS action [{$action}] for [{$entity}]");
        }

        $names = [$crud[$action], self::moduleAll($action)];
        if ($entity !== 'main') {
            $names[] = self::crud('main')[$action];
        }

        return array_values(array_unique($names));
    }

    /**
     * @return list<string>
     */
    public static function menuShowAny(): array
    {
        return self::action('show');
    }

    /**
     * Playlist editor helpers (promo picker).
     *
     * @return list<string>
     */
    public static function playlistComposeAny(): array
    {
        return array_values(array_unique([
            ...self::for('promos', 'show'),
            ...self::for('playlists', 'create'),
            ...self::for('playlists', 'update'),
        ]));
    }

    /**
     * Device picker used while composing playlists.
     *
     * @return list<string>
     */
    public static function devicePickerAny(): array
    {
        return array_values(array_unique([
            ...self::for('devices', 'show'),
            ...self::for('playlists', 'create'),
            ...self::for('playlists', 'update'),
        ]));
    }

    /**
     * @return list<string>
     */
    public static function hubReadOrWriteAny(): array
    {
        return array_values(array_unique([
            ...self::action('show'),
            ...self::action('create'),
            ...self::action('update'),
        ]));
    }

    /**
     * @return array{show: string, create: string, update: string, delete: string}
     */
    public static function crud(string $entity): array
    {
        return match ($entity) {
            'main' => [
                'show' => self::MAIN_SHOW,
                'create' => self::MAIN_CREATE,
                'update' => self::MAIN_UPDATE,
                'delete' => self::MAIN_DELETE,
            ],
            'promos', 'Ad materials' => [
                'show' => self::PROMO_SHOW,
                'create' => self::PROMO_CREATE,
                'update' => self::PROMO_UPDATE,
                'delete' => self::PROMO_DELETE,
            ],
            'playlists', 'Playlists' => [
                'show' => self::PLAYLIST_SHOW,
                'create' => self::PLAYLIST_CREATE,
                'update' => self::PLAYLIST_UPDATE,
                'delete' => self::PLAYLIST_DELETE,
            ],
            'devices', 'Devices' => [
                'show' => self::DEVICE_SHOW,
                'create' => self::DEVICE_CREATE,
                'update' => self::DEVICE_UPDATE,
                'delete' => self::DEVICE_DELETE,
            ],
            default => throw new \InvalidArgumentException("Unknown screens EMS entity [{$entity}]"),
        };
    }

    /**
     * @return list<array{name: string, name_ar: string, description: string, description_ar: string, type: string}>
     */
    public static function definitions(): array
    {
        $rows = array_filter(
            include base_path('Modules/Employee/data/dashboard-permissions.php'),
            static fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'screens.')
                || str_starts_with((string) ($row['name'] ?? ''), 'screen_module.')
        );

        $unique = [];
        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? '');
            if ($name === '' || isset($unique[$name])) {
                continue;
            }
            $unique[$name] = $row;
        }

        return array_values($unique);
    }

    private static function moduleAll(string $action): string
    {
        return match ($action) {
            'show' => self::MODULE_ALL_SHOW,
            'create' => self::MODULE_ALL_CREATE,
            'update' => self::MODULE_ALL_UPDATE,
            'delete' => self::MODULE_ALL_DELETE,
            default => throw new \InvalidArgumentException("Unknown screens EMS action [{$action}]"),
        };
    }
}
