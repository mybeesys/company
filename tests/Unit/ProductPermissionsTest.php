<?php

namespace Tests\Unit;

use Modules\Product\Http\Controllers\CategoryController;
use Modules\Product\Http\Controllers\SubCategoryController;
use Modules\Product\Support\ProductPermissions;
use PHPUnit\Framework\TestCase;

class ProductPermissionsTest extends TestCase
{
    public function test_maps_catalog_entities_to_existing_ems_names(): void
    {
        $this->assertSame('products.product.create', ProductPermissions::crud('product')['create']);
        $this->assertSame('products.sub catgeory.delete', ProductPermissions::crud('subcategory')['delete']);
        $this->assertSame('products.custom menu.show', ProductPermissions::crud('customMenu')['show']);
        $this->assertSame('products.price tier.delete', ProductPermissions::crud('priceTier')['delete']);
        $this->assertSame(ProductPermissions::MODIFIER_UPDATE, ProductPermissions::crud('modifierClass')['update']);
        $this->assertSame(ProductPermissions::ATTRIBUTE_CREATE, ProductPermissions::crud('attributeClass')['create']);
        $this->assertSame(ProductPermissions::IMPORT_CREATE, ProductPermissions::crud('importProduct')['create']);
    }

    public function test_rejects_entities_outside_item_management(): void
    {
        foreach (['linkedCombo', 'serviceFee'] as $entity) {
            try {
                ProductPermissions::crud($entity);
                $this->fail("Expected unknown products EMS entity [{$entity}]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString($entity, $e->getMessage());
            }
        }
    }

    public function test_product_pages_use_laravel11_controller_middleware(): void
    {
        $category = array_map(
            static fn ($middleware) => is_string($middleware) ? $middleware : $middleware->middleware,
            CategoryController::middleware()
        );
        $subcategory = array_map(
            static fn ($middleware) => is_string($middleware) ? $middleware : $middleware->middleware,
            SubCategoryController::middleware()
        );

        $this->assertContains('dashboard.perm:products.category.show', $category);
        $this->assertContains('dashboard.perm:products.category.create', $category);
        $this->assertNotContains('dashboard.perm:products.sub catgeory.show', $subcategory);
        $this->assertContains('dashboard.perm:products.sub catgeory.create', $subcategory);
        $this->assertContains('dashboard.perm:products.sub catgeory.update', $subcategory);
    }
}
