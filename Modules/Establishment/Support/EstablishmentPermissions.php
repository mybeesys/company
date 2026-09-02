<?php

namespace Modules\Establishment\Support;

/**
 * Central Establishments EMS permission names (must match dashboard-permissions.php).
 *
 * List vs item: establishments.establishments.show (name_ar الأفرع) vs establishments.establishment.* (فرع).
 * POS devices have no catalog row — they follow establishment.update / establishments.show.
 */
final class EstablishmentPermissions
{
    public const ALL_SHOW = 'establishments.all.show';

    public const ALL_PRINT = 'establishments.all.print';

    public const ALL_CREATE = 'establishments.all.create';

    public const ALL_UPDATE = 'establishments.all.update';

    public const ALL_DELETE = 'establishments.all.delete';

    public const ESTABLISHMENTS_SHOW = 'establishments.establishments.show';

    public const ESTABLISHMENT_SHOW = 'establishments.establishment.show';

    public const ESTABLISHMENT_CREATE = 'establishments.establishment.create';

    public const ESTABLISHMENT_UPDATE = 'establishments.establishment.update';

    public const ESTABLISHMENT_DELETE = 'establishments.establishment.delete';

    public const COMPANY_SHOW = 'establishments.company.show';

    public const COMPANY_UPDATE = 'establishments.company.update';

    /**
     * @return list<string>
     */
    public static function menuShowAny(): array
    {
        return [
            self::ALL_SHOW,
            self::ESTABLISHMENTS_SHOW,
            self::ESTABLISHMENT_SHOW,
            self::COMPANY_SHOW,
        ];
    }

    /**
     * POS device tab on the establishments hub (no devices.* catalog row).
     *
     * @return list<string>
     */
    public static function deviceShowAny(): array
    {
        return [self::ESTABLISHMENTS_SHOW, self::ESTABLISHMENT_SHOW, self::ESTABLISHMENT_UPDATE];
    }

    /**
     * @return array<string, string>
     */
    public static function crud(string $entity): array
    {
        return match ($entity) {
            'establishments' => [
                'show' => self::ESTABLISHMENTS_SHOW,
            ],
            'establishment' => [
                'show' => self::ESTABLISHMENT_SHOW,
                'create' => self::ESTABLISHMENT_CREATE,
                'update' => self::ESTABLISHMENT_UPDATE,
                'delete' => self::ESTABLISHMENT_DELETE,
            ],
            'company' => [
                'show' => self::COMPANY_SHOW,
                'update' => self::COMPANY_UPDATE,
            ],
            default => throw new \InvalidArgumentException("Unknown establishments EMS entity [{$entity}]"),
        };
    }

    /**
     * @return list<array{name: string, name_ar: string, description: string, description_ar: string, type: string}>
     */
    public static function definitions(): array
    {
        $rows = array_filter(
            include base_path('Modules/Employee/data/dashboard-permissions.php'),
            static fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'establishments.')
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
}
