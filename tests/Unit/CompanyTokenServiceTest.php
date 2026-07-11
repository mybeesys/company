<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CompanyTokenService;
use Tests\TestCase;

final class CompanyTokenServiceTest extends TestCase
{
    public function test_token_name_includes_client_type_and_device(): void
    {
        $service = new CompanyTokenService();

        $this->assertSame('company-api:waiter', $service->tokenName('waiter'));
        $this->assertSame('company-api:kitchen', $service->tokenName('kitchen'));
        $this->assertSame('company-api:waiter:tablet-1', $service->tokenName('waiter', 'tablet-1'));
        $this->assertSame('company-api:default', $service->tokenName(null));
        $this->assertSame('company-api:default', $service->tokenName('unknown-app'));
    }
}
