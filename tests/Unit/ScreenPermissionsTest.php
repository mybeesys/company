<?php

namespace Tests\Unit;

use Modules\Screen\Support\ScreenPermissions;
use PHPUnit\Framework\TestCase;

class ScreenPermissionsTest extends TestCase
{
    public function test_maps_hub_and_tab_entities_to_catalog_names(): void
    {
        $this->assertSame('screens.main.show', ScreenPermissions::crud('main')['show']);
        $this->assertSame('screens.Ad materials.create', ScreenPermissions::crud('promos')['create']);
        $this->assertSame('screens.Playlists.update', ScreenPermissions::crud('playlists')['update']);
        $this->assertSame('screens.Devices.delete', ScreenPermissions::crud('devices')['delete']);
        $this->assertSame(ScreenPermissions::crud('promos'), ScreenPermissions::crud('Ad materials'));
        $this->assertSame(ScreenPermissions::crud('playlists'), ScreenPermissions::crud('Playlists'));
        $this->assertSame(ScreenPermissions::crud('devices'), ScreenPermissions::crud('Devices'));
    }

    public function test_tab_gates_or_main_and_module_all(): void
    {
        $promoShow = ScreenPermissions::for('promos', 'show');
        $this->assertContains(ScreenPermissions::PROMO_SHOW, $promoShow);
        $this->assertContains(ScreenPermissions::MAIN_SHOW, $promoShow);
        $this->assertContains(ScreenPermissions::MODULE_ALL_SHOW, $promoShow);

        $deviceCreate = ScreenPermissions::for('devices', 'create');
        $this->assertContains(ScreenPermissions::DEVICE_CREATE, $deviceCreate);
        $this->assertContains(ScreenPermissions::MAIN_CREATE, $deviceCreate);
        $this->assertContains(ScreenPermissions::MODULE_ALL_CREATE, $deviceCreate);
    }

    public function test_hub_show_covers_any_tab(): void
    {
        $show = ScreenPermissions::action('show');
        $this->assertContains(ScreenPermissions::MAIN_SHOW, $show);
        $this->assertContains(ScreenPermissions::MODULE_ALL_SHOW, $show);
        $this->assertContains(ScreenPermissions::PROMO_SHOW, $show);
        $this->assertContains(ScreenPermissions::PLAYLIST_SHOW, $show);
        $this->assertContains(ScreenPermissions::DEVICE_SHOW, $show);
        $this->assertSame($show, ScreenPermissions::menuShowAny());
    }

    public function test_catalog_keeps_hub_and_adds_tab_entities_without_print(): void
    {
        $catalog = include dirname(__DIR__, 2).'/Modules/Employee/data/dashboard-permissions.php';
        $names = array_column($catalog, 'name');

        foreach ([
            ScreenPermissions::MAIN_SHOW,
            ScreenPermissions::ALL_PRINT,
            ScreenPermissions::MODULE_ALL_SHOW,
            ScreenPermissions::PROMO_SHOW,
            ScreenPermissions::PLAYLIST_CREATE,
            ScreenPermissions::DEVICE_DELETE,
        ] as $name) {
            $this->assertContains($name, $names);
        }

        $this->assertNotContains('screens.main.print', $names);
        $this->assertNotContains('screens.Ad materials.print', $names);
        $this->assertNotContains('screens.Playlists.print', $names);
        $this->assertNotContains('screens.Devices.print', $names);
        $this->assertNotContains('screens.playlist.show', $names);
        $this->assertNotContains('screens.promo.create', $names);
        $this->assertNotContains('screens.device.delete', $names);
    }

    public function test_hub_row_label_is_not_the_module_title(): void
    {
        $catalog = include dirname(__DIR__, 2).'/Modules/Employee/data/dashboard-permissions.php';
        $labels = [];
        foreach ($catalog as $row) {
            if (in_array($row['name'] ?? '', [
                ScreenPermissions::MAIN_SHOW,
                ScreenPermissions::MAIN_CREATE,
                ScreenPermissions::MAIN_UPDATE,
                ScreenPermissions::MAIN_DELETE,
            ], true)) {
                $labels[] = $row['name_ar'] ?? '';
            }
        }

        $this->assertNotEmpty($labels);
        foreach ($labels as $label) {
            $this->assertSame('الصفحة الرئيسية', $label);
        }
    }

    public function test_rejects_entities_outside_screens_hub(): void
    {
        foreach (['playlist', 'promo', 'device', 'warehouse'] as $entity) {
            try {
                ScreenPermissions::crud($entity);
                $this->fail("Expected unknown screens EMS entity [{$entity}]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString($entity, $e->getMessage());
            }
        }
    }
}
