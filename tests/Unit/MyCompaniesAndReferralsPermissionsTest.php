<?php

namespace Tests\Unit;

use Modules\Employee\Support\MyCompaniesPermissions;
use Modules\Employee\Support\ReferralsPermissions;
use PHPUnit\Framework\TestCase;

class MyCompaniesAndReferralsPermissionsTest extends TestCase
{
    public function test_sidebar_pages_use_own_modules_not_employees(): void
    {
        $this->assertSame('my_companies.My companies.show', MyCompaniesPermissions::SHOW);
        $this->assertSame('referrals.Referrals.show', ReferralsPermissions::SHOW);
        $this->assertSame('referrals.Referrals.create', ReferralsPermissions::CREATE);

        $catalog = include dirname(__DIR__, 2).'/Modules/Employee/data/dashboard-permissions.php';
        $names = array_column($catalog, 'name');

        $this->assertContains(MyCompaniesPermissions::SHOW, $names);
        $this->assertContains(ReferralsPermissions::SHOW, $names);
        $this->assertContains(ReferralsPermissions::CREATE, $names);
        $this->assertNotContains('employees.my_companies.show', $names);
        $this->assertNotContains('employees.referrals.show', $names);
        $this->assertNotContains('my_companies.all.show', $names);
        $this->assertNotContains('referrals.Referrals.update', $names);
    }
}
