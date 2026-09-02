<?php

namespace Tests\Unit;

use Modules\Franchise\Support\FranchisePermissions;
use PHPUnit\Framework\TestCase;

class FranchisePermissionsTest extends TestCase
{
    public function test_keeps_existing_all_prefix_and_maps_hub_tabs(): void
    {
        $this->assertSame('Franchise Companies.all.show', FranchisePermissions::ALL_SHOW);
        $this->assertSame('Franchise Companies.Companies.create', FranchisePermissions::crud('Companies')['create']);
        $this->assertSame('Franchise Companies.Branches.delete', FranchisePermissions::crud('branches')['delete']);
        $this->assertSame('Franchise Companies.Products.update', FranchisePermissions::crud('Products')['update']);
        $this->assertArrayNotHasKey('create', FranchisePermissions::crud('Products'));
        $this->assertArrayNotHasKey('delete', FranchisePermissions::crud('Menus'));
    }

    public function test_tab_gates_or_module_all(): void
    {
        $show = FranchisePermissions::for('Companies', 'show');
        $this->assertContains(FranchisePermissions::COMPANIES_SHOW, $show);
        $this->assertContains(FranchisePermissions::ALL_SHOW, $show);

        $update = FranchisePermissions::for('Products', 'update');
        $this->assertContains(FranchisePermissions::PRODUCTS_UPDATE, $update);
        $this->assertContains(FranchisePermissions::ALL_UPDATE, $update);
        $this->assertNotContains(FranchisePermissions::ALL_SHOW, $update);
    }

    public function test_menu_parent_covers_each_tab_show(): void
    {
        $any = FranchisePermissions::menuShowAny();
        $this->assertContains(FranchisePermissions::ALL_SHOW, $any);
        $this->assertContains(FranchisePermissions::COMPANIES_SHOW, $any);
        $this->assertContains(FranchisePermissions::BRANCHES_SHOW, $any);
        $this->assertContains(FranchisePermissions::PRODUCTS_SHOW, $any);
        $this->assertContains(FranchisePermissions::MENUS_SHOW, $any);
    }

    public function test_catalog_keeps_all_and_adds_tabs_without_renaming_prefix(): void
    {
        $catalog = include dirname(__DIR__, 2).'/Modules/Employee/data/dashboard-permissions.php';
        $names = array_column($catalog, 'name');

        foreach ([
            FranchisePermissions::ALL_SHOW,
            FranchisePermissions::ALL_DELETE,
            FranchisePermissions::COMPANIES_SHOW,
            FranchisePermissions::BRANCHES_CREATE,
            FranchisePermissions::PRODUCTS_UPDATE,
            FranchisePermissions::MENUS_SHOW,
        ] as $name) {
            $this->assertContains($name, $names);
        }

        $this->assertNotContains('franchise.companies.show', $names);
        $this->assertNotContains('Franchise Companies.Companies.print', $names);
    }

    public function test_rejects_invented_entities(): void
    {
        foreach (['contracts', 'devices'] as $entity) {
            try {
                FranchisePermissions::crud($entity);
                $this->fail("Expected unknown franchise EMS entity [{$entity}]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString($entity, $e->getMessage());
            }
        }
    }
}
