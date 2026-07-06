<?php

namespace Tests\Unit;

use Modules\Accounting\Services\JournalEntry\OpeningBalanceExcelParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class OpeningBalanceExcelParserTest extends TestCase
{
    public function test_rebalances_two_cent_drift_after_line_rounding(): void
    {
        $path = $this->makeWorkbook([
            ['الاسم', 'الكود', 'مدين', 'دائن'],
            ['Asset A', '1001', '100.004', '0'],
            ['Equity A', '3001', '0', '100.006'],
        ]);

        $parsed = (new OpeningBalanceExcelParser)->parse($path);

        $this->assertSame([], $parsed['errors']);
        $this->assertSame('100.01', $parsed['debit_total']);
        $this->assertSame('100.01', $parsed['credit_total']);
    }

    private function makeWorkbook(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
            }
        }

        $path = sys_get_temp_dir().'/opening-balance-parser-test-'.uniqid('', true).'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
