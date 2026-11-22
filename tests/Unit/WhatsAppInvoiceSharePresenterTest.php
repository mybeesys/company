<?php

namespace Tests\Unit;

use Modules\General\Models\Transaction;
use Modules\General\Support\WhatsAppInvoiceSharePresenter;
use Tests\TestCase;

class WhatsAppInvoiceSharePresenterTest extends TestCase
{
    public function test_normalizes_saudi_local_mobile_numbers(): void
    {
        $this->assertSame('966501234567', WhatsAppInvoiceSharePresenter::normalizePhone('0501234567'));
        $this->assertSame('966501234567', WhatsAppInvoiceSharePresenter::normalizePhone('501234567'));
        $this->assertSame('966501234567', WhatsAppInvoiceSharePresenter::normalizePhone('+966 50 123 4567'));
        $this->assertSame('966501234567', WhatsAppInvoiceSharePresenter::normalizePhone('00966501234567'));
        $this->assertSame('', WhatsAppInvoiceSharePresenter::normalizePhone(''));
    }

    public function test_renders_template_placeholders(): void
    {
        $message = WhatsAppInvoiceSharePresenter::renderTemplate(
            'Hello {customer_name} — {invoice_no} / {amount} / {download_link}',
            [
                'customer_name' => 'Ali',
                'invoice_no' => 'INV-1',
                'amount' => '100.00',
                'date' => '2026-01-01',
                'company_name' => 'My Bee',
                'download_link' => 'https://example.test/invoice',
            ]
        );

        $this->assertSame('Hello Ali — INV-1 / 100.00 / https://example.test/invoice', $message);
    }

    public function test_supports_only_finalized_sell_family(): void
    {
        $sell = new Transaction;
        $sell->forceFill(['type' => 'sell', 'status' => 'final']);
        $this->assertTrue(WhatsAppInvoiceSharePresenter::supports($sell));

        $draft = new Transaction;
        $draft->forceFill(['type' => 'sell', 'status' => 'draft']);
        $this->assertFalse(WhatsAppInvoiceSharePresenter::supports($draft));

        $purchase = new Transaction;
        $purchase->forceFill(['type' => 'purchases', 'status' => 'final']);
        $this->assertFalse(WhatsAppInvoiceSharePresenter::supports($purchase));

        $sellReturn = new Transaction;
        $sellReturn->forceFill(['type' => 'sell-return', 'status' => 'approved']);
        $this->assertTrue(WhatsAppInvoiceSharePresenter::supports($sellReturn));
    }

    public function test_wa_url_includes_phone_and_encoded_text(): void
    {
        $url = WhatsAppInvoiceSharePresenter::waUrl('966501234567', "Hello\nWorld");
        $this->assertStringStartsWith('https://wa.me/966501234567?', $url);
        $this->assertStringContainsString('text=Hello', $url);
    }

    public function test_default_template_contains_required_placeholders(): void
    {
        $template = WhatsAppInvoiceSharePresenter::defaultTemplate();
        foreach (['{customer_name}', '{invoice_no}', '{amount}', '{date}', '{company_name}', '{download_link}'] as $token) {
            $this->assertStringContainsString($token, $template);
        }
        $this->assertStringContainsString('تحميل / Download', $template);
        $this->assertStringContainsString('To download the invoice, please click on Download', $template);
        $this->assertStringNotContainsString('7 أيام', $template);
        $this->assertStringNotContainsString('7 days', $template);
    }

    public function test_api_template_has_no_download_link(): void
    {
        $template = WhatsAppInvoiceSharePresenter::defaultApiTemplate();
        $this->assertStringNotContainsString('{download_link}', $template);
        $this->assertStringContainsString('{customer_name}', $template);
        $this->assertStringContainsString('To download the invoice, please click on Download', $template);
    }

    public function test_strip_links_from_message_removes_urls(): void
    {
        $cleaned = WhatsAppInvoiceSharePresenter::stripLinksFromMessage(
            "Hello\n⬇️ *تحميل / Download*\nhttps://example.test/invoice?x=1\n\nBye"
        );
        $this->assertStringNotContainsString('https://', $cleaned);
        $this->assertStringContainsString('Hello', $cleaned);
        $this->assertStringContainsString('Bye', $cleaned);
    }

    public function test_default_link_ttl_is_24_hours(): void
    {
        $this->assertSame(24, WhatsAppInvoiceSharePresenter::DEFAULT_TTL_HOURS);
        $this->assertSame('24 ساعة / 24 hours', WhatsAppInvoiceSharePresenter::linkValidityLabel(24));
        $this->assertSame('ساعة واحدة / 1 hour', WhatsAppInvoiceSharePresenter::linkValidityLabel(1));
    }

    public function test_public_https_url_detection(): void
    {
        $this->assertTrue(\Modules\General\Support\WhatsAppCloudApiClient::isPublicHttpsUrl('https://app.example.com/pdf'));
        $this->assertFalse(\Modules\General\Support\WhatsAppCloudApiClient::isPublicHttpsUrl('http://test.localhost:8000/pdf'));
        $this->assertFalse(\Modules\General\Support\WhatsAppCloudApiClient::isPublicHttpsUrl('https://localhost/pdf'));
    }
}
