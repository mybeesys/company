<?php

namespace Tests\Unit;

use App\Support\Zatca\FatooraZatcaPackage;
use Tests\TestCase;

class FatooraZatcaPackageTest extends TestCase
{
    public function test_local_environment_uses_sandbox_package(): void
    {
        config([
            'zatca.app.environment' => 'local',
            'zatca.packages.sandbox' => base_path('packages/fatoora-zatca'),
            'zatca.packages.production' => base_path('packages/fatoora-zatca-production'),
        ]);

        $this->assertSame('local', FatooraZatcaPackage::environment());
        $this->assertFalse(FatooraZatcaPackage::isProduction());
        $this->assertSame(FatooraZatcaPackage::VARIANT_SANDBOX, FatooraZatcaPackage::variant());
        $this->assertSame(base_path('packages/fatoora-zatca'), FatooraZatcaPackage::rootPath());
        $this->assertDirectoryExists(FatooraZatcaPackage::srcPath());
    }

    public function test_simulation_environment_uses_sandbox_package(): void
    {
        config([
            'zatca.app.environment' => 'simulation',
            'zatca.packages.sandbox' => base_path('packages/fatoora-zatca'),
            'zatca.packages.production' => base_path('packages/fatoora-zatca-production'),
        ]);

        $this->assertSame(FatooraZatcaPackage::VARIANT_SANDBOX, FatooraZatcaPackage::variant());
        $this->assertSame(base_path('packages/fatoora-zatca'), FatooraZatcaPackage::rootPath());
    }

    public function test_production_environment_uses_production_package(): void
    {
        config([
            'zatca.app.environment' => 'production',
            'zatca.packages.sandbox' => base_path('packages/fatoora-zatca'),
            'zatca.packages.production' => base_path('packages/fatoora-zatca-production'),
        ]);

        $this->assertTrue(FatooraZatcaPackage::isProduction());
        $this->assertSame(FatooraZatcaPackage::VARIANT_PRODUCTION, FatooraZatcaPackage::variant());
        $this->assertSame(base_path('packages/fatoora-zatca-production'), FatooraZatcaPackage::rootPath());
        $this->assertDirectoryExists(FatooraZatcaPackage::srcPath());
        $this->assertFileExists(FatooraZatcaPackage::srcPath().DIRECTORY_SEPARATOR.'clients.txt');
    }

    public function test_invalid_environment_falls_back_to_local_sandbox(): void
    {
        config([
            'zatca.app.environment' => 'staging',
            'zatca.packages.sandbox' => base_path('packages/fatoora-zatca'),
        ]);

        $this->assertSame('local', FatooraZatcaPackage::environment());
        $this->assertSame(FatooraZatcaPackage::VARIANT_SANDBOX, FatooraZatcaPackage::variant());
    }
}
