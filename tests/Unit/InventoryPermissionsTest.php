<?php

namespace Tests\Unit;

use Modules\Inventory\Http\Controllers\PrepController;
use Modules\Inventory\Support\InventoryPermissions;
use PHPUnit\Framework\TestCase;

class InventoryPermissionsTest extends TestCase
{
    public function test_maps_menu_entities_to_existing_ems_names(): void
    {
        $this->assertSame('inventory.product.show', InventoryPermissions::crud('product')['show']);
        $this->assertSame('inventory.product.update', InventoryPermissions::crud('product')['update']);
        $this->assertArrayNotHasKey('create', InventoryPermissions::crud('product'));
        $this->assertArrayNotHasKey('delete', InventoryPermissions::crud('product'));
        $this->assertSame('inventory.prep.create', InventoryPermissions::crud('prep')['create']);
        $this->assertSame('inventory.transfer.update', InventoryPermissions::crud('transfer')['update']);
        $this->assertSame('inventory.waste.show', InventoryPermissions::crud('waste')['show']);
        $this->assertSame('inventory.import.create', InventoryPermissions::crud('import')['create']);
    }

    public function test_maps_inventory_documents_and_leaves_purchases_unmapped(): void
    {
        $this->assertSame(InventoryPermissions::WASTE_UPDATE, InventoryPermissions::forTransactionType('WASTE', 'update'));
        $this->assertSame(InventoryPermissions::TRANSFER_CREATE, InventoryPermissions::forTransactionType('TRANSFER', 'create'));
        $this->assertNull(InventoryPermissions::forTransactionType('purchases', 'show'));
        $this->assertNull(InventoryPermissions::forTransactionType('sell', 'update'));
    }

    public function test_rejects_entities_outside_visible_inventory_menu(): void
    {
        foreach (['purchaseOrder', 'warehouse', 'rma', 'ingredient'] as $entity) {
            try {
                InventoryPermissions::crud($entity);
                $this->fail("Expected unknown inventory EMS entity [{$entity}]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString($entity, $e->getMessage());
            }
        }
    }

    public function test_prep_pages_use_laravel11_controller_middleware(): void
    {
        $aliases = array_map(
            static fn ($middleware) => is_string($middleware) ? $middleware : $middleware->middleware,
            PrepController::middleware()
        );

        $this->assertContains('dashboard.perm:inventory.prep.show', $aliases);
        $this->assertContains('dashboard.perm:inventory.prep.create', $aliases);
        $this->assertContains('dashboard.perm:inventory.prep.update', $aliases);
    }
}
