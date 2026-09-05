<?php

namespace Tests\Unit;

use Modules\Accounting\Support\CoaColorSystem;
use Modules\Accounting\Support\MyBeeMasterCoaRules;
use PHPUnit\Framework\TestCase;

class MyBeeMasterCoaRulesTest extends TestCase
{
    public function test_skips_illustrative_party_leaves_and_keeps_control_accounts(): void
    {
        $this->assertTrue(MyBeeMasterCoaRules::isIllustrativePartyAccount('عميل 1', 'Customer 1'));
        $this->assertTrue(MyBeeMasterCoaRules::isIllustrativePartyAccount('مورد خارجي 2', 'Foreign Supplier 2'));
        $this->assertTrue(MyBeeMasterCoaRules::isIllustrativePartyAccount('حساب بنكي 1', 'Bank Account 1'));
        $this->assertFalse(MyBeeMasterCoaRules::isIllustrativePartyAccount('العملاء - محلي', 'Trade Receivables - Local'));
        $this->assertFalse(MyBeeMasterCoaRules::isIllustrativePartyAccount('البنك - حساب جاري', 'Bank - Current Account'));
    }

    public function test_maps_english_class_to_system_primary_types(): void
    {
        $this->assertSame('asset', MyBeeMasterCoaRules::primaryTypeFromClass('Assets'));
        $this->assertSame('liabilities', MyBeeMasterCoaRules::primaryTypeFromClass('Liabilities'));
        $this->assertSame('equity', MyBeeMasterCoaRules::primaryTypeFromClass('Equity'));
        $this->assertSame('income', MyBeeMasterCoaRules::primaryTypeFromClass('Revenue'));
        $this->assertSame('expenses', MyBeeMasterCoaRules::primaryTypeFromClass('Expenses'));
    }

    public function test_routing_candidates_prefer_master_codes_then_legacy(): void
    {
        $candidates = MyBeeMasterCoaRules::routingGlCandidates();

        $this->assertSame('21301', $candidates['sales_vat'][0]);
        $this->assertContains('522', $candidates['sales_vat']);
        $this->assertSame('11901', $candidates['purchases_vat'][0]);
        $this->assertSame('41101', $candidates['sales'][0]);
        $this->assertContains('411', $candidates['sales']);
        $this->assertContains('513', $candidates['purchases']);
        $this->assertSame('42101', $candidates['sales_return'][0]);
        $this->assertSame('11505', $candidates['inventory'][0]);
        $this->assertSame('51101', $candidates['cogs'][0]);
    }

    public function test_color_system_uses_workbook_families_and_levels(): void
    {
        $assetL1 = CoaColorSystem::resolve('asset', 1);
        $expenseL4 = CoaColorSystem::resolve('expenses', 4);
        $equityL5 = CoaColorSystem::resolve('equity', 5);

        $this->assertSame('#1F4E78', $assetL1['background']);
        $this->assertSame('#FFF2CC', $expenseL4['background']);
        $this->assertSame('#F4F1F8', $equityL5['background']);
        $this->assertSame('coa-tone coa-tone-income-l1', CoaColorSystem::toneClass('income', 1));

        $assetUi = CoaColorSystem::uiAccentFromPalette(MyBeeMasterCoaRules::colorSystem()['asset']);
        $this->assertSame($assetUi, $assetL1['accent']);
        $this->assertSame($assetUi, CoaColorSystem::resolve('asset', 4)['accent']);
        $this->assertNotSame('#1F4E78', $assetL1['accent']);
        $this->assertNotSame('#7F6000', $expenseL4['accent']);
    }

    public function test_compiled_catalog_excludes_placeholders_and_keeps_posting_leaves(): void
    {
        $path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.'Accounting'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'mybee-master-coa-v5.php';
        $this->assertFileExists($path);

        /** @var array<string, mixed> $catalog */
        $catalog = require $path;
        $codes = array_column($catalog['accounts'], 'gl_code');

        $this->assertCount(17, $catalog['types']);
        $this->assertNotContains('1130101', $codes);
        $this->assertNotContains('2110101', $codes);
        $this->assertContains('11301', $codes);
        $this->assertContains('41101', $codes);
        $this->assertContains('21301', $codes);
        $this->assertContains('11901', $codes);

        $byGl = [];
        foreach ($catalog['accounts'] as $row) {
            $byGl[$row['gl_code']] = $row;
        }

        $this->assertTrue($byGl['41101']['allow_direct_posting']);
        $this->assertFalse($byGl['411']['allow_direct_posting']);
        $this->assertSame('income', $byGl['41101']['account_primary_type']);
    }
}
