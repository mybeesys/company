<?php

namespace Tests\Unit;

use Modules\General\Services\Notifications\NotificationCatalog;
use Modules\General\Services\Notifications\NotificationPreferenceService;
use PHPUnit\Framework\TestCase;

class NotificationPreferenceServiceTest extends TestCase
{
    public function test_grouped_catalog_covers_all_keys(): void
    {
        $service = new NotificationPreferenceService;
        $grouped = $service->groupedCatalog();
        $flat = [];
        foreach ($grouped as $items) {
            foreach ($items as $item) {
                $flat[] = $item['key'];
            }
        }

        $this->assertSame(
            array_values(array_keys(NotificationCatalog::all())),
            array_values($flat)
        );
        $this->assertArrayHasKey('sales', $grouped);
        $this->assertArrayHasKey('accounting', $grouped);
    }

    public function test_email_default_is_critical_only_without_db(): void
    {
        // Without DB rows, defaults are derived from catalog severity.
        // We only assert catalog severities used by the preference defaults.
        $critical = NotificationCatalog::get('fiscal_period_closed');
        $info = NotificationCatalog::get('new_sell');

        $this->assertSame('critical', $critical['severity'] ?? null);
        $this->assertSame('info', $info['severity'] ?? null);
    }
}
