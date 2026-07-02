<?php

namespace Tests\Unit;

use Modules\Accounting\Services\JournalEntry\JournalTransactionsExcelParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class JournalTransactionsExcelParserTest extends TestCase
{
    public function test_accepts_three_decimal_amounts_when_raw_totals_balance(): void
    {
        $path = $this->makeWorkbook([
            ['Date', 'Ref', 'Account', 'GL', 'Desc', '', 'Debit', 'Credit'],
            ['2058', '', '', '', '', '', '', ''],
            ['08/01/2025', '2058', 'Expense A', '1001', 'note', '', '822.955', '0'],
            ['08/01/2025', '2058', 'Expense B', '1001', 'note', '', '822.955', '0'],
            ['08/01/2025', '2058', 'Cash', '2001', 'note', '', '0', '1645.91'],
            ['', '2058', '', '', '', '', '1645.91', '1645.91'],
            ['المجموع', '2058', '', '', '', '', '1645.91', '1645.91'],
        ]);

        $parsed = (new JournalTransactionsExcelParser)->parse($path);

        $this->assertSame([], $parsed['errors']);
        $this->assertCount(1, $parsed['entries']);
        $this->assertSame('2058', $parsed['entries'][0]['ref_no']);

        $debitSum = 0.0;
        $creditSum = 0.0;
        foreach ($parsed['entries'][0]['lines'] as $line) {
            $debitSum += (float) $line['debit'];
            $creditSum += (float) $line['credit'];
        }

        $this->assertEqualsWithDelta($debitSum, $creditSum, 0.001);
        $this->assertEqualsWithDelta(1645.91, $debitSum, 0.02);
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function makeWorkbook(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Journal Transactions (2)');

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
            }
        }

        $path = storage_path('app/testing-journal-import.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
