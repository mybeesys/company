<?php

namespace Modules\Report\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class WeekdaySimpleGridExcelExport implements FromArray, WithEvents
{
    private int $headerRow1;

    private int $headerRow2;

    private int $dataStartRow;

    private int $colCount;

    private string $lastCol;

    public function __construct(
        private readonly array $datesMeta,
        private readonly array $gridRows,
        private readonly array $meta
    ) {
        $n = count($this->datesMeta);
        $this->colCount = max(3, 3 + (2 * $n));
        $this->lastCol = Coordinate::stringFromColumnIndex($this->colCount);
        $this->headerRow1 = 7;
        $this->headerRow2 = 8;
        $this->dataStartRow = 9;
    }

    public function array(): array
    {
        $dates = $this->datesMeta;
        $qtyLbl = __('report::general.weekday_simple_grid_col_qty');
        $priceLbl = __('report::general.weekday_simple_grid_col_price');
        $product = __('report::fields.product_name');
        $branch = __('report::fields.establishment_name');
        $unit = __('report::general.filter_panel_unit');

        $pad = fn (array $r): array => array_pad($r, $this->colCount, '');

        $out = [];
        $out[] = $pad([$this->meta['title']]);
        $out[] = $pad([__('report::general.export_generated_at'), $this->meta['generated_at']]);
        $out[] = $pad([__('report::general.weekday_report_kpi_period_note'), $this->meta['period_line']]);
        $out[] = $pad([
            __('report::general.weekday_export_selected_days'),
            (string) ($this->meta['weekdays_line'] ?? ''),
        ]);
        $out[] = $pad([__('report::general.export_filters_heading'), $this->meta['filters']]);
        $out[] = $pad([]);

        $h1 = [$product, $branch, $unit];
        foreach ($dates as $dm) {
            $h1[] = (string) ($dm['label'] ?? $dm['date'] ?? '');
            $h1[] = '';
        }
        $out[] = $pad($h1);

        $h2 = ['', '', ''];
        foreach ($dates as $_) {
            $h2[] = $qtyLbl;
            $h2[] = $priceLbl;
        }
        $out[] = $pad($h2);

        foreach ($this->gridRows as $row) {
            $line = [
                (string) ($row['product_name'] ?? ''),
                (string) ($row['establishment_name'] ?? ''),
                (string) ($row['unit_label'] ?? ''),
            ];
            $cells = $row['cells'] ?? [];
            foreach ($dates as $dm) {
                $d = (string) ($dm['date'] ?? '');
                $c = $cells[$d] ?? ['qty' => 0.0, 'unit_sale_price' => null];
                $q = (float) ($c['qty'] ?? 0);
                $p = $c['unit_sale_price'] ?? null;
                $line[] = $q;
                $line[] = ($p !== null && is_numeric($p)) ? (float) $p : '';
            }
            $out[] = $pad($line);
        }

        return $out;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('A1:'.$this->lastCol.'1');
                $sheet->getStyle('A1')->getFont()->setBold(true);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                for ($i = 0; $i < count($this->datesMeta); $i++) {
                    $cStart = 4 + (2 * $i);
                    $a = Coordinate::stringFromColumnIndex($cStart).$this->headerRow1;
                    $b = Coordinate::stringFromColumnIndex($cStart + 1).$this->headerRow1;
                    $sheet->mergeCells($a.':'.$b);
                    $sheet->getStyle($a)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle($a)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle($a)->getFont()->setBold(true);
                    $sheet->getStyle($a)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFE5E7EB');
                }

                $lastDataRow = $this->dataStartRow + max(0, count($this->gridRows)) - 1;
                if ($lastDataRow < $this->headerRow2) {
                    $lastDataRow = $this->headerRow2;
                }
                $range = 'A'.$this->headerRow1.':'.$this->lastCol.$lastDataRow;
                $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle('A'.$this->headerRow2.':'.$this->lastCol.$this->headerRow2)->getFont()->setBold(true);
                $sheet->getStyle('A'.$this->headerRow2.':'.$this->lastCol.$this->headerRow2)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF1F5F9');

                $qtyFmt = '#,##0.###';
                $priceFmt = '#,##0.00';
                for ($i = 0; $i < count($this->datesMeta); $i++) {
                    $cq = Coordinate::stringFromColumnIndex(4 + (2 * $i));
                    $cp = Coordinate::stringFromColumnIndex(5 + (2 * $i));
                    $sheet->getStyle($cq.$this->dataStartRow.':'.$cq.$lastDataRow)->getNumberFormat()->setFormatCode($qtyFmt);
                    $sheet->getStyle($cp.$this->dataStartRow.':'.$cp.$lastDataRow)->getNumberFormat()->setFormatCode($priceFmt);
                }

                $sheet->freezePane('A'.$this->dataStartRow);
            },
        ];
    }
}
