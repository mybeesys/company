<?php

namespace Tests\Unit;

use Modules\Purchases\Support\PurchasesPermissions;
use PHPUnit\Framework\TestCase;

class PurchasesPermissionsTest extends TestCase
{
    public function test_maps_purchases_document_types_to_ems_permissions(): void
    {
        $this->assertSame(PurchasesPermissions::INVOICES_SHOW, PurchasesPermissions::forTransactionType('purchases', 'show'));
        $this->assertSame(PurchasesPermissions::INVOICES_PRINT, PurchasesPermissions::forTransactionType('purchase', 'print'));
        $this->assertSame(PurchasesPermissions::INVOICES_CREATE, PurchasesPermissions::forTransactionType('purchases', 'create'));
        $this->assertSame(PurchasesPermissions::ORDERS_SHOW, PurchasesPermissions::forTransactionType('purchases-order', 'show'));
        $this->assertSame(PurchasesPermissions::RETURNS_CREATE, PurchasesPermissions::forTransactionType('purchases-return', 'create'));
    }

    public function test_leaves_sales_documents_unmapped(): void
    {
        $this->assertNull(PurchasesPermissions::forTransactionType('sell', 'show'));
        $this->assertNull(PurchasesPermissions::forTransactionType('sell-return', 'print'));
        $this->assertFalse(PurchasesPermissions::isPurchasesTransactionType('sell'));
        $this->assertTrue(PurchasesPermissions::isPurchasesTransactionType('purchases'));
    }
}
