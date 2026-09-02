<?php

namespace Modules\Franchise\Support;

/**
 * Franchise EMS names (must match dashboard-permissions.php).
 * Keep the existing prefix "Franchise Companies" — wildcard is Franchise Companies.all.{action}.
 *
 * Tabs: Companies, Branches, Products (show/update), Menus (show/update).
 * Contracts live on the company page and follow Companies CUD.
 */
final class FranchisePermissions
{
    public const ALL_SHOW = 'Franchise Companies.all.show';

    public const ALL_PRINT = 'Franchise Companies.all.print';

    public const ALL_CREATE = 'Franchise Companies.all.create';

    public const ALL_UPDATE = 'Franchise Companies.all.update';

    public const ALL_DELETE = 'Franchise Companies.all.delete';

    public const COMPANIES_SHOW = 'Franchise Companies.Companies.show';

    public const COMPANIES_CREATE = 'Franchise Companies.Companies.create';

    public const COMPANIES_UPDATE = 'Franchise Companies.Companies.update';

    public const COMPANIES_DELETE = 'Franchise Companies.Companies.delete';

    public const BRANCHES_SHOW = 'Franchise Companies.Branches.show';

    public const BRANCHES_CREATE = 'Franchise Companies.Branches.create';

    public const BRANCHES_UPDATE = 'Franchise Companies.Branches.update';

    public const BRANCHES_DELETE = 'Franchise Companies.Branches.delete';

    public const PRODUCTS_SHOW = 'Franchise Companies.Products.show';

    public const PRODUCTS_UPDATE = 'Franchise Companies.Products.update';

    public const MENUS_SHOW = 'Franchise Companies.Menus.show';

    public const MENUS_UPDATE = 'Franchise Companies.Menus.update';

    /**
     * Tab gate: entity permission OR Franchise Companies.all.{action}.
     *
     * @return list<string>
     */
    public static function for(string $entity, string $action): array
    {
        $crud = self::crud($entity);
        if (! isset($crud[$action])) {
            throw new \InvalidArgumentException("Unknown franchise EMS action [{$action}] for [{$entity}]");
        }

        return array_values(array_unique([
            $crud[$action],
            self::moduleAll($action),
        ]));
    }

    /**
     * @return list<string>
     */
    public static function menuShowAny(): array
    {
        return array_values(array_unique([
            self::ALL_SHOW,
            self::COMPANIES_SHOW,
            self::BRANCHES_SHOW,
            self::PRODUCTS_SHOW,
            self::MENUS_SHOW,
        ]));
    }

    /**
     * Hub tabs that were split out of all.* (copy-from source).
     *
     * @return list<string>
     */
    public static function tabEntities(): array
    {
        return ['Companies', 'Branches', 'Products', 'Menus'];
    }

    /**
     * @return array<string, string>
     */
    public static function crud(string $entity): array
    {
        return match ($entity) {
            'Companies', 'companies' => [
                'show' => self::COMPANIES_SHOW,
                'create' => self::COMPANIES_CREATE,
                'update' => self::COMPANIES_UPDATE,
                'delete' => self::COMPANIES_DELETE,
            ],
            'Branches', 'branches' => [
                'show' => self::BRANCHES_SHOW,
                'create' => self::BRANCHES_CREATE,
                'update' => self::BRANCHES_UPDATE,
                'delete' => self::BRANCHES_DELETE,
            ],
            'Products', 'products' => [
                'show' => self::PRODUCTS_SHOW,
                'update' => self::PRODUCTS_UPDATE,
            ],
            'Menus', 'menus' => [
                'show' => self::MENUS_SHOW,
                'update' => self::MENUS_UPDATE,
            ],
            default => throw new \InvalidArgumentException("Unknown franchise EMS entity [{$entity}]"),
        };
    }

    /**
     * @return list<array{name: string, name_ar: string, description: string, description_ar: string, type: string}>
     */
    public static function definitions(): array
    {
        $rows = array_filter(
            include base_path('Modules/Employee/data/dashboard-permissions.php'),
            static fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'Franchise Companies.')
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
            'show' => self::ALL_SHOW,
            'create' => self::ALL_CREATE,
            'update' => self::ALL_UPDATE,
            'delete' => self::ALL_DELETE,
            'print' => self::ALL_PRINT,
            default => throw new \InvalidArgumentException("Unknown franchise EMS action [{$action}]"),
        };
    }
}
