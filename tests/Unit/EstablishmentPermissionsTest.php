<?php

namespace Tests\Unit;

use Modules\Establishment\Support\EstablishmentPermissions;
use PHPUnit\Framework\TestCase;

class EstablishmentPermissionsTest extends TestCase
{
    public function test_maps_list_vs_item_and_company(): void
    {
        $this->assertSame('establishments.establishments.show', EstablishmentPermissions::crud('establishments')['show']);
        $this->assertSame('establishments.establishment.create', EstablishmentPermissions::crud('establishment')['create']);
        $this->assertSame('establishments.establishment.delete', EstablishmentPermissions::crud('establishment')['delete']);
        $this->assertSame('establishments.company.update', EstablishmentPermissions::crud('company')['update']);
        $this->assertArrayNotHasKey('create', EstablishmentPermissions::crud('company'));
    }

    public function test_catalog_includes_establishment_delete_gap(): void
    {
        $catalog = include dirname(__DIR__, 2).'/Modules/Employee/data/dashboard-permissions.php';
        $names = array_column($catalog, 'name');

        foreach ([
            EstablishmentPermissions::ESTABLISHMENTS_SHOW,
            EstablishmentPermissions::ESTABLISHMENT_UPDATE,
            EstablishmentPermissions::ESTABLISHMENT_DELETE,
            EstablishmentPermissions::COMPANY_SHOW,
        ] as $name) {
            $this->assertContains($name, $names);
        }

        $this->assertNotContains('establishments.devices.show', $names);
        $this->assertNotContains('establishments.establishments.print', $names);

        $byName = [];
        foreach ($catalog as $row) {
            $byName[(string) ($row['name'] ?? '')] = (string) ($row['name_ar'] ?? '');
        }
        $this->assertSame('الأفرع', $byName[EstablishmentPermissions::ESTABLISHMENTS_SHOW]);
        $this->assertSame('فرع', $byName[EstablishmentPermissions::ESTABLISHMENT_SHOW]);
    }

    public function test_device_tab_follows_branch_show_or_update(): void
    {
        $show = EstablishmentPermissions::deviceShowAny();
        $this->assertContains(EstablishmentPermissions::ESTABLISHMENTS_SHOW, $show);
        $this->assertContains(EstablishmentPermissions::ESTABLISHMENT_SHOW, $show);
        $this->assertContains(EstablishmentPermissions::ESTABLISHMENT_UPDATE, $show);
    }

    public function test_rejects_invented_entities(): void
    {
        foreach (['devices', 'areas', 'pos'] as $entity) {
            try {
                EstablishmentPermissions::crud($entity);
                $this->fail("Expected unknown establishments EMS entity [{$entity}]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString($entity, $e->getMessage());
            }
        }
    }
}
