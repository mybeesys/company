<?php

namespace Tests\Unit;

use Modules\General\Support\SettingPermissions;
use PHPUnit\Framework\TestCase;

class SettingPermissionsTest extends TestCase
{
    public function test_maps_settings_menu_entities_without_inventing_tax_names(): void
    {
        $this->assertSame('setting.General setting.show', SettingPermissions::crud('general')['show']);
        $this->assertSame('setting.General setting.update', SettingPermissions::crud('General setting')['update']);
        $this->assertArrayNotHasKey('create', SettingPermissions::crud('general'));
        $this->assertArrayNotHasKey('delete', SettingPermissions::crud('general'));
        $this->assertArrayNotHasKey('print', SettingPermissions::crud('general'));

        $this->assertSame('setting.tables.print', SettingPermissions::crud('tables')['print']);
        $this->assertSame('setting.tables_qr.show', SettingPermissions::crud('tables_qr')['show']);
        $this->assertSame('setting.menu_qr.delete', SettingPermissions::crud('menu_qr')['delete']);
        $this->assertSame('setting.menu_feedback.show', SettingPermissions::crud('menu_feedback')['show']);
        $this->assertArrayNotHasKey('create', SettingPermissions::crud('menu_feedback'));
        $this->assertArrayNotHasKey('update', SettingPermissions::crud('menu_feedback'));
        $this->assertArrayNotHasKey('delete', SettingPermissions::crud('menu_feedback'));
        $this->assertSame('setting.taxes.create', SettingPermissions::crud('taxes')['create']);
        $this->assertSame('setting.notifications.update', SettingPermissions::crud('notifications')['update']);
        $this->assertSame('setting.inventory costing.show', SettingPermissions::crud('inventory_costing')['show']);
        $this->assertSame('setting.default unit.update', SettingPermissions::crud('default unit')['update']);
    }

    public function test_tab_gates_or_module_all_not_the_hub(): void
    {
        $taxesShow = SettingPermissions::for('taxes', 'show');
        $this->assertContains(SettingPermissions::TAXES_SHOW, $taxesShow);
        $this->assertContains(SettingPermissions::ALL_SHOW, $taxesShow);
        $this->assertNotContains(SettingPermissions::GENERAL_SHOW, $taxesShow);

        $prefixUpdate = SettingPermissions::for('prefix', 'update');
        $this->assertContains(SettingPermissions::PREFIX_UPDATE, $prefixUpdate);
        $this->assertContains(SettingPermissions::ALL_UPDATE, $prefixUpdate);
        $this->assertNotContains(SettingPermissions::GENERAL_UPDATE, $prefixUpdate);
    }

    public function test_menu_parent_covers_settings_and_embedded_establishment_show(): void
    {
        $any = SettingPermissions::menuShowAny();
        $this->assertContains(SettingPermissions::GENERAL_SHOW, $any);
        $this->assertContains(SettingPermissions::TAXES_SHOW, $any);
        $this->assertContains(SettingPermissions::TABLES_SHOW, $any);
        $this->assertContains(SettingPermissions::MENU_QR_SHOW, $any);
        $this->assertContains(SettingPermissions::MENU_FEEDBACK_SHOW, $any);
        $this->assertContains('establishments.establishments.show', $any);
        $this->assertContains('establishments.company.show', $any);
    }

    public function test_areas_reuse_tables_and_branch_permissions(): void
    {
        $read = SettingPermissions::areaReadAny();
        $this->assertContains(SettingPermissions::TABLES_SHOW, $read);
        $this->assertContains(SettingPermissions::TABLES_QR_SHOW, $read);
        $this->assertContains('establishments.establishments.show', $read);

        $mutate = SettingPermissions::areaMutateAny();
        $this->assertContains(SettingPermissions::TABLES_CREATE, $mutate);
        $this->assertContains(SettingPermissions::TABLES_UPDATE, $mutate);
        $this->assertContains(SettingPermissions::TABLES_DELETE, $mutate);
        $this->assertContains('establishments.establishment.update', $mutate);
        $this->assertNotContains('setting.areas.create', $mutate);
    }

    public function test_catalog_keeps_existing_setting_names(): void
    {
        $catalog = include dirname(__DIR__, 2).'/Modules/Employee/data/dashboard-permissions.php';
        $names = array_column($catalog, 'name');

        foreach ([
            SettingPermissions::GENERAL_SHOW,
            SettingPermissions::GENERAL_UPDATE,
            SettingPermissions::TAXES_SHOW,
            SettingPermissions::TAXES_CREATE,
            SettingPermissions::MAIL_UPDATE,
            SettingPermissions::COSTING_SHOW,
            SettingPermissions::REWARDS_UPDATE,
            SettingPermissions::TABLES_CREATE,
            SettingPermissions::TABLES_QR_PRINT,
            SettingPermissions::MENU_QR_UPDATE,
            SettingPermissions::MENU_FEEDBACK_SHOW,
            SettingPermissions::ALL_PRINT,
        ] as $name) {
            $this->assertContains($name, $names);
        }

        $this->assertNotContains('setting.areas.create', $names);
        $this->assertNotContains('setting.General setting.print', $names);
        $this->assertNotContains('setting.purchases.show', $names);
    }

    public function test_menu_feedback_show_does_not_or_menu_qr(): void
    {
        $show = SettingPermissions::for('menu_feedback', 'show');
        $this->assertContains(SettingPermissions::MENU_FEEDBACK_SHOW, $show);
        $this->assertContains(SettingPermissions::ALL_SHOW, $show);
        $this->assertNotContains(SettingPermissions::MENU_QR_SHOW, $show);
    }

    public function test_rejects_entities_outside_settings_catalog(): void
    {
        foreach (['areas', 'devices', 'purchases'] as $entity) {
            try {
                SettingPermissions::crud($entity);
                $this->fail("Expected unknown settings EMS entity [{$entity}]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString($entity, $e->getMessage());
            }
        }
    }
}
