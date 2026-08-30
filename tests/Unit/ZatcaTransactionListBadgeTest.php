<?php

namespace Tests\Unit;

use Modules\General\Models\Transaction;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use Modules\Zatca\Support\ZatcaTransactionListBadge;
use Tests\TestCase;

class ZatcaTransactionListBadgeTest extends TestCase
{
    public function test_renders_synced_badge_with_reporting_status(): void
    {
        config(['zatca.show_in_menu' => true]);

        $sync = new ZatcaInvoiceSync([
            'status' => ZatcaInvoiceSync::STATUS_SYNCED,
            'reporting_status' => 'REPORTED',
            'synced_at' => now(),
        ]);

        $transaction = new Transaction(['type' => 'sell', 'status' => 'approved']);
        $transaction->setRelation('zatcaInvoiceSync', $sync);

        $html = ZatcaTransactionListBadge::render($transaction);

        $this->assertStringContainsString('zatca-list-badge--synced', $html);
        $this->assertStringContainsString('data-zatca-tip', $html);
    }

    public function test_renders_failed_badge_with_error_tooltip(): void
    {
        config(['zatca.show_in_menu' => true]);

        $sync = new ZatcaInvoiceSync([
            'status' => ZatcaInvoiceSync::STATUS_FAILED,
            'last_error' => 'Invalid buyer VAT number',
        ]);

        $transaction = new Transaction(['type' => 'sell-return', 'status' => 'approved']);
        $transaction->setRelation('zatcaInvoiceSync', $sync);

        $html = ZatcaTransactionListBadge::render($transaction);

        $this->assertStringContainsString('zatca-list-badge--failed', $html);
        $this->assertStringContainsString('Invalid buyer VAT number', $html);
    }

    public function test_does_not_render_for_purchases(): void
    {
        config(['zatca.show_in_menu' => true]);

        $transaction = new Transaction(['type' => 'purchases', 'status' => 'approved']);

        $this->assertSame('', ZatcaTransactionListBadge::render($transaction));
    }
}
