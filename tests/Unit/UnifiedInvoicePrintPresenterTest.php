<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\General\Models\Transaction;
use Modules\General\Support\UnifiedInvoicePrintPresenter;
use Tests\TestCase;

class UnifiedInvoicePrintPresenterTest extends TestCase
{
    private function company(): object
    {
        return (object) [
            'name' => 'MyBee Co',
            'name_ar' => 'شركتي',
            'tax_number' => '300000000000003',
            'commercial_register' => '1010101010',
            'street_name' => 'King Fahd',
            'city' => 'Riyadh',
            'state' => 'Riyadh',
            'postal_code' => '12345',
            'mobile' => '0500000000',
        ];
    }

    private function contact(string $name = 'Supplier X'): Contact
    {
        $contact = new Contact;
        $contact->forceFill([
            'name' => $name,
            'tax_number' => '310000000000003',
            'commercial_register' => '2020202020',
            'mobile_number' => '0555555555',
        ]);
        $contact->setRelation('billingAddress', null);

        return $contact;
    }

    private function transaction(string $type, Contact $contact): Transaction
    {
        $tx = new Transaction;
        $tx->forceFill([
            'type' => $type,
            'ref_no' => 'T-001',
            'transaction_date' => '2026-08-01',
            'due_date' => null,
            'payment_status' => 'due',
            'total_before_tax' => 100,
            'tax_amount' => 15,
            'final_total' => 115,
            'discount_amount' => 0,
            'discount_type' => 'fixed',
            'parent_id' => null,
        ]);
        $tx->setRelation('client', $contact);
        $tx->setRelation('sell_lines', new Collection);
        $tx->setRelation('purchases_lines', new Collection);
        $tx->setRelation('payment', new Collection);
        $tx->setRelation('parentSell', null);
        $tx->setRelation('zatcaInvoiceSync', null);

        return $tx;
    }

    public function test_supports_only_invoice_family_types(): void
    {
        $this->assertTrue(UnifiedInvoicePrintPresenter::supports('sell'));
        $this->assertTrue(UnifiedInvoicePrintPresenter::supports('purchases'));
        $this->assertTrue(UnifiedInvoicePrintPresenter::supports('sell-return'));
        $this->assertTrue(UnifiedInvoicePrintPresenter::supports('purchases-return'));
        $this->assertFalse(UnifiedInvoicePrintPresenter::supports('quotation'));
        $this->assertFalse(UnifiedInvoicePrintPresenter::supports('purchases-order'));
    }

    public function test_sales_invoice_company_is_seller_client_is_buyer(): void
    {
        $data = UnifiedInvoicePrintPresenter::build(
            $this->transaction('sell', $this->contact('Customer A')),
            $this->company(),
            '<svg></svg>',
            true
        );

        $this->assertSame('البائع', $data['sellerRoleAr']);
        $this->assertSame('العميل', $data['buyerRoleAr']);
        $this->assertStringContainsString('شركتي', $data['seller']['name']);
        $this->assertSame('Customer A', $data['buyer']['name']);
        $this->assertFalse($data['isPurchaseSide']);
    }

    public function test_purchase_invoice_supplier_is_seller_company_is_buyer(): void
    {
        $data = UnifiedInvoicePrintPresenter::build(
            $this->transaction('purchases', $this->contact('Vendor B')),
            $this->company(),
            '<svg></svg>',
            true
        );

        $this->assertSame('المورد', $data['sellerRoleAr']);
        $this->assertSame('المشتري', $data['buyerRoleAr']);
        $this->assertSame('Vendor B', $data['seller']['name']);
        $this->assertStringContainsString('شركتي', $data['buyer']['name']);
        $this->assertTrue($data['isPurchaseSide']);
    }

    public function test_purchase_return_keeps_supplier_as_seller(): void
    {
        $data = UnifiedInvoicePrintPresenter::build(
            $this->transaction('purchases-return', $this->contact('Vendor C')),
            $this->company(),
            '<svg></svg>',
            true
        );

        $this->assertSame('Vendor C', $data['seller']['name']);
        $this->assertTrue($data['isReturn']);
        $this->assertTrue($data['isPurchaseSide']);
    }
}
