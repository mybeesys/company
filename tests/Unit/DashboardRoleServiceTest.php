<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use Modules\Employee\Services\DashboardRoleService;
use PHPUnit\Framework\TestCase;

class DashboardRoleServiceTest extends TestCase
{
    public function test_folds_screen_module_into_screens_accordion(): void
    {
        $modules = collect([
            'screens' => collect([
                'all' => collect(['show' => 1]),
                'main.الصفحة الرئيسية' => collect(['show' => 2]),
            ]),
            'screen_module' => collect([
                'all' => collect(['show' => 99, 'print' => 98]),
            ]),
            'sales' => collect([
                'all' => collect(['show' => 3]),
            ]),
        ]);

        $folded = DashboardRoleService::foldScreenModuleAlias($modules);

        $this->assertFalse($folded->has('screen_module'));
        $this->assertTrue($folded->has('screens'));
        $this->assertTrue($folded->has('sales'));
        $this->assertInstanceOf(Collection::class, $folded->get('screens')->get('_screen_module_all'));
        $this->assertSame(99, $folded->get('screens')->get('_screen_module_all')->get('show'));
        $this->assertSame(1, $folded->get('screens')->get('all')->get('show'));
    }

    public function test_keeps_screen_module_when_screens_hub_is_missing(): void
    {
        $folded = DashboardRoleService::foldScreenModuleAlias(collect([
            'screen_module' => collect([
                'all' => collect(['show' => 99]),
            ]),
        ]));

        $this->assertTrue($folded->has('screen_module'));
    }
}
