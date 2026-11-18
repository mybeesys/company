<?php

namespace Tests\Unit;

use Modules\Accounting\Support\DefaultAccountRoutingMap;
use Modules\Accounting\Support\MyBeeMasterCoaRules;
use PHPUnit\Framework\TestCase;

class DefaultAccountRoutingMapFiscalCloseTest extends TestCase
{
    public function test_routing_rows_include_distinct_fiscal_close_equity_types(): void
    {
        $candidates = MyBeeMasterCoaRules::routingGlCandidates();
        $this->assertSame(['32201'], $candidates['fiscal_close_current_period_result']);
        $this->assertSame(['32101'], $candidates['fiscal_close_retained_earnings']);

        // Reflection of row definitions without hitting the database: ensure the map
        // declares both fiscal-close routes as equity under section fiscal_close.
        $source = file_get_contents(
            dirname(__DIR__, 2).DIRECTORY_SEPARATOR
            .'Modules'.DIRECTORY_SEPARATOR
            .'Accounting'.DIRECTORY_SEPARATOR
            .'Support'.DIRECTORY_SEPARATOR
            .'DefaultAccountRoutingMap.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString("'type' => 'fiscal_close_current_period_result'", $source);
        $this->assertStringContainsString("'type' => 'fiscal_close_retained_earnings'", $source);
        $this->assertStringContainsString("'section' => 'fiscal_close'", $source);
        $this->assertStringContainsString("'routing_type' => 'equity'", $source);
        $this->assertTrue(class_exists(DefaultAccountRoutingMap::class));
    }
}
