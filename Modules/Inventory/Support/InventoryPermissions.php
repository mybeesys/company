<?php

namespace Modules\Inventory\Support;

/**
 * Central Inventory EMS permission names (must match dashboard-permissions.php).
 */
final class InventoryPermissions
{
    public const ALL_SHOW = 'inventory.all.show';

    public const ALL_PRINT = 'inventory.all.print';

    public const ALL_CREATE = 'inventory.all.create';

    public const ALL_UPDATE = 'inventory.all.update';

    public const ALL_DELETE = 'inventory.all.delete';

    public const DASHBOARD_SHOW = 'inventory.dashboard.show';

    public const PRODUCT_SHOW = 'inventory.product.show';

    public const PRODUCT_UPDATE = 'inventory.product.update';

    public const PREP_SHOW = 'inventory.prep.show';

    public const PREP_CREATE = 'inventory.prep.create';

    public const PREP_UPDATE = 'inventory.prep.update';

    public const TRANSFER_SHOW = 'inventory.transfer.show';

    public const TRANSFER_CREATE = 'inventory.transfer.create';

    public const TRANSFER_UPDATE = 'inventory.transfer.update';

    public const WASTE_SHOW = 'inventory.waste.show';

    public const WASTE_CREATE = 'inventory.waste.create';

    public const WASTE_UPDATE = 'inventory.waste.update';

    public const IMPORT_SHOW = 'inventory.import.show';

    public const IMPORT_CREATE = 'inventory.import.create';

    public const IMPORT_UPDATE = 'inventory.import.update';

    /**
     * @return array{show?: string, create?: string, update?: string, delete?: string}
     */
    public static function crud(string $entity): array
    {
        return match ($entity) {
            'product' => [
                'show' => self::PRODUCT_SHOW,
                'update' => self::PRODUCT_UPDATE,
            ],
            'prep' => [
                'show' => self::PREP_SHOW,
                'create' => self::PREP_CREATE,
                'update' => self::PREP_UPDATE,
            ],
            'transfer' => [
                'show' => self::TRANSFER_SHOW,
                'create' => self::TRANSFER_CREATE,
                'update' => self::TRANSFER_UPDATE,
            ],
            'waste' => [
                'show' => self::WASTE_SHOW,
                'create' => self::WASTE_CREATE,
                'update' => self::WASTE_UPDATE,
            ],
            'import' => [
                'show' => self::IMPORT_SHOW,
                'create' => self::IMPORT_CREATE,
                'update' => self::IMPORT_UPDATE,
            ],
            default => throw new \InvalidArgumentException("Unknown inventory EMS entity [{$entity}]"),
        };
    }

    /**
     * Map inventory document types. Returns null for PO/RMA/sales so those stay unconstrained here.
     */
    public static function forTransactionType(?string $type, string $action): ?string
    {
        $map = match ($type) {
            'PREP' => [
                'show' => self::PREP_SHOW,
                'create' => self::PREP_CREATE,
                'update' => self::PREP_UPDATE,
            ],
            'TRANSFER' => [
                'show' => self::TRANSFER_SHOW,
                'create' => self::TRANSFER_CREATE,
                'update' => self::TRANSFER_UPDATE,
            ],
            'WASTE' => [
                'show' => self::WASTE_SHOW,
                'create' => self::WASTE_CREATE,
                'update' => self::WASTE_UPDATE,
            ],
            default => null,
        };

        if ($map === null) {
            return null;
        }

        return $map[$action] ?? null;
    }

    /**
     * @return list<array{name: string, name_ar: string, description: string, description_ar: string, type: string}>
     */
    public static function definitions(): array
    {
        return array_values(array_filter(
            include base_path('Modules/Employee/data/dashboard-permissions.php'),
            static fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'inventory.')
        ));
    }
}
