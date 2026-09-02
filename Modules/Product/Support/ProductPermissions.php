<?php

namespace Modules\Product\Support;

/**
 * Central Products EMS permission names (must match dashboard-permissions.php).
 */
final class ProductPermissions
{
    public const ALL_SHOW = 'products.all.show';

    public const ALL_PRINT = 'products.all.print';

    public const ALL_CREATE = 'products.all.create';

    public const ALL_UPDATE = 'products.all.update';

    public const ALL_DELETE = 'products.all.delete';

    public const DASHBOARD_SHOW = 'products.dashboard.show';

    public const CATEGORY_SHOW = 'products.category.show';

    public const CATEGORY_CREATE = 'products.category.create';

    public const CATEGORY_UPDATE = 'products.category.update';

    public const CATEGORY_DELETE = 'products.category.delete';

    public const SUBCATEGORY_CREATE = 'products.sub catgeory.create';

    public const SUBCATEGORY_UPDATE = 'products.sub catgeory.update';

    public const SUBCATEGORY_DELETE = 'products.sub catgeory.delete';

    public const PRODUCT_SHOW = 'products.product.show';

    public const PRODUCT_CREATE = 'products.product.create';

    public const PRODUCT_UPDATE = 'products.product.update';

    public const PRODUCT_DELETE = 'products.product.delete';

    public const MODIFIER_SHOW = 'products.modifier.show';

    public const MODIFIER_CREATE = 'products.modifier.create';

    public const MODIFIER_UPDATE = 'products.modifier.update';

    public const MODIFIER_DELETE = 'products.modifier.delete';

    public const ATTRIBUTE_SHOW = 'products.attribute.show';

    public const ATTRIBUTE_CREATE = 'products.attribute.create';

    public const ATTRIBUTE_UPDATE = 'products.attribute.update';

    public const ATTRIBUTE_DELETE = 'products.attribute.delete';

    public const INGREDIENT_SHOW = 'products.ingredient.show';

    public const INGREDIENT_CREATE = 'products.ingredient.create';

    public const INGREDIENT_UPDATE = 'products.ingredient.update';

    public const INGREDIENT_DELETE = 'products.ingredient.delete';

    public const CUSTOM_MENU_SHOW = 'products.custom menu.show';

    public const CUSTOM_MENU_CREATE = 'products.custom menu.create';

    public const CUSTOM_MENU_UPDATE = 'products.custom menu.update';

    public const CUSTOM_MENU_DELETE = 'products.custom menu.delete';

    public const TYPE_SERVICE_SHOW = 'products.type service.show';

    public const TYPE_SERVICE_CREATE = 'products.type service.create';

    public const TYPE_SERVICE_UPDATE = 'products.type service.update';

    public const TYPE_SERVICE_DELETE = 'products.type service.delete';

    public const PRICE_TIER_SHOW = 'products.price tier.show';

    public const PRICE_TIER_CREATE = 'products.price tier.create';

    public const PRICE_TIER_UPDATE = 'products.price tier.update';

    public const PRICE_TIER_DELETE = 'products.price tier.delete';

    public const DISCOUNT_SHOW = 'products.discount.show';

    public const DISCOUNT_CREATE = 'products.discount.create';

    public const DISCOUNT_UPDATE = 'products.discount.update';

    public const DISCOUNT_DELETE = 'products.discount.delete';

    public const BARCODE_SHOW = 'products.product barcode.show';

    public const BARCODE_PRINT = 'products.product barcode.print';

    public const IMPORT_SHOW = 'products.importProduct.show';

    public const IMPORT_CREATE = 'products.importProduct.create';

    public const IMPORT_UPDATE = 'products.importProduct.update';

    public const IMPORT_DELETE = 'products.importProduct.delete';

    /**
     * @return array{show?: string, create: string, update: string, delete: string}
     */
    public static function crud(string $entity): array
    {
        return match ($entity) {
            'category' => [
                'show' => self::CATEGORY_SHOW,
                'create' => self::CATEGORY_CREATE,
                'update' => self::CATEGORY_UPDATE,
                'delete' => self::CATEGORY_DELETE,
            ],
            'subcategory' => [
                'create' => self::SUBCATEGORY_CREATE,
                'update' => self::SUBCATEGORY_UPDATE,
                'delete' => self::SUBCATEGORY_DELETE,
            ],
            'product' => [
                'show' => self::PRODUCT_SHOW,
                'create' => self::PRODUCT_CREATE,
                'update' => self::PRODUCT_UPDATE,
                'delete' => self::PRODUCT_DELETE,
            ],
            'modifier', 'modifierClass' => [
                'show' => self::MODIFIER_SHOW,
                'create' => self::MODIFIER_CREATE,
                'update' => self::MODIFIER_UPDATE,
                'delete' => self::MODIFIER_DELETE,
            ],
            'attribute', 'attributeClass' => [
                'show' => self::ATTRIBUTE_SHOW,
                'create' => self::ATTRIBUTE_CREATE,
                'update' => self::ATTRIBUTE_UPDATE,
                'delete' => self::ATTRIBUTE_DELETE,
            ],
            'ingredient' => [
                'show' => self::INGREDIENT_SHOW,
                'create' => self::INGREDIENT_CREATE,
                'update' => self::INGREDIENT_UPDATE,
                'delete' => self::INGREDIENT_DELETE,
            ],
            'customMenu' => [
                'show' => self::CUSTOM_MENU_SHOW,
                'create' => self::CUSTOM_MENU_CREATE,
                'update' => self::CUSTOM_MENU_UPDATE,
                'delete' => self::CUSTOM_MENU_DELETE,
            ],
            'typeService' => [
                'show' => self::TYPE_SERVICE_SHOW,
                'create' => self::TYPE_SERVICE_CREATE,
                'update' => self::TYPE_SERVICE_UPDATE,
                'delete' => self::TYPE_SERVICE_DELETE,
            ],
            'priceTier' => [
                'show' => self::PRICE_TIER_SHOW,
                'create' => self::PRICE_TIER_CREATE,
                'update' => self::PRICE_TIER_UPDATE,
                'delete' => self::PRICE_TIER_DELETE,
            ],
            'discount' => [
                'show' => self::DISCOUNT_SHOW,
                'create' => self::DISCOUNT_CREATE,
                'update' => self::DISCOUNT_UPDATE,
                'delete' => self::DISCOUNT_DELETE,
            ],
            'importProduct' => [
                'show' => self::IMPORT_SHOW,
                'create' => self::IMPORT_CREATE,
                'update' => self::IMPORT_UPDATE,
                'delete' => self::IMPORT_DELETE,
            ],
            default => throw new \InvalidArgumentException("Unknown products EMS entity [{$entity}]"),
        };
    }

    /**
     * @return list<array{name: string, name_ar: string, description: string, description_ar: string, type: string}>
     */
    public static function definitions(): array
    {
        return array_values(array_filter(
            include base_path('Modules/Employee/data/dashboard-permissions.php'),
            static fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'products.')
                && ! str_starts_with((string) ($row['name'] ?? ''), 'products.service fee.')
        ));
    }
}
