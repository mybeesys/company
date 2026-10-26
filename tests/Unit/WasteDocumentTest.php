<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Accounting\Services\WasteJournalPoster;
use Modules\Accounting\Support\WasteDocument;
use PHPUnit\Framework\TestCase;

class WasteDocumentTest extends TestCase
{
    public function test_purpose_aliases_are_waste(): void
    {
        $this->assertTrue(WasteDocument::isWaste('waste'));
        $this->assertTrue(WasteDocument::isWaste('spoilage'));
        $this->assertTrue(WasteDocument::isWaste('inventory_waste'));
        $this->assertFalse(WasteDocument::isWaste('internal_consumption'));
        $this->assertFalse(WasteDocument::isWaste('standard'));
    }

    public function test_transaction_type_waste(): void
    {
        $tx = (object) ['type' => 'WASTE', 'purpose' => 'standard'];
        $this->assertTrue(WasteDocument::isWaste($tx));
    }

    public function test_transaction_purpose_waste(): void
    {
        $tx = (object) ['type' => 'sell', 'purpose' => 'waste'];
        $this->assertTrue(WasteDocument::isWaste($tx));
    }

    public function test_consumption_type_name_is_waste(): void
    {
        $type = (object) [
            'type_key' => 'staff_meals',
            'name_ar' => 'إتلاف مخزون',
            'name_en' => 'Inventory waste',
        ];
        $this->assertTrue(WasteDocument::consumptionTypeLooksLikeWaste($type));

        $meals = (object) [
            'type_key' => 'staff_meals',
            'name_ar' => 'وجبات عاملين',
            'name_en' => 'Staff meals',
        ];
        $this->assertFalse(WasteDocument::consumptionTypeLooksLikeWaste($meals));
    }

    public function test_proposed_lines_are_balanced_debit_expense_credit_offset(): void
    {
        $lines = WasteJournalPoster::proposedLines(40.5, 11, 22);

        $this->assertSame('debit', $lines[0]['type']);
        $this->assertSame('waste_expense', $lines[0]['role']);
        $this->assertSame(11, $lines[0]['account_id']);
        $this->assertSame(40.5, $lines[0]['amount']);

        $this->assertSame('credit', $lines[1]['type']);
        $this->assertSame('inventory_or_purchases', $lines[1]['role']);
        $this->assertSame(22, $lines[1]['account_id']);
        $this->assertSame(40.5, $lines[1]['amount']);
    }
}
