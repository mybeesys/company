<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Accounting\Support\AccountingNote;
use PHPUnit\Framework\TestCase;

final class AccountingNoteTest extends TestCase
{
    public function test_compose_drops_leading_journal_word_and_joins_parts(): void
    {
        $note = AccountingNote::compose(
            'قيد رسوم خدمة',
            'عمولة جاهز',
            'INV-2026/0021',
            1
        );

        $this->assertSame('رسوم خدمة — عمولة جاهز — INV-2026/0021 [#1]', $note);
    }

    public function test_compose_sales_document_without_item_id(): void
    {
        $this->assertSame(
            'مبيعات — INV-2026/0021',
            AccountingNote::compose('مبيعات', null, 'INV-2026/0021')
        );
    }

    public function test_without_leading_journal_word_english(): void
    {
        $this->assertSame('Service fee', AccountingNote::withoutLeadingJournalWord('Journal Service fee'));
        $this->assertSame('Service fee', AccountingNote::withoutLeadingJournalWord('Entry Service fee'));
    }
}
