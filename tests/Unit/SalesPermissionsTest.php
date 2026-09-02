<?php

namespace Tests\Unit;

use Modules\Sales\Support\SalesPermissions;
use PHPUnit\Framework\TestCase;

class SalesPermissionsTest extends TestCase
{
    public function test_maps_sales_document_types_to_ems_permissions(): void
    {
        $this->assertSame(SalesPermissions::INVOICES_SHOW, SalesPermissions::forTransactionType('sell', 'show'));
        $this->assertSame(SalesPermissions::INVOICES_PRINT, SalesPermissions::forTransactionType('sell', 'print'));
        $this->assertSame(SalesPermissions::INVOICES_CREATE, SalesPermissions::forTransactionType('sell', 'create'));
        $this->assertSame(SalesPermissions::QUOTATIONS_SHOW, SalesPermissions::forTransactionType('quotation', 'show'));
        $this->assertSame(SalesPermissions::RETURNS_CREATE, SalesPermissions::forTransactionType('sell-return', 'create'));
    }

    public function test_leaves_purchases_documents_unmapped(): void
    {
        $this->assertNull(SalesPermissions::forTransactionType('purchases', 'show'));
        $this->assertNull(SalesPermissions::forTransactionType('purchases-return', 'print'));
        $this->assertFalse(SalesPermissions::isSalesTransactionType('purchases'));
        $this->assertTrue(SalesPermissions::isSalesTransactionType('sell'));
    }
}
