<?php

namespace Modules\Report\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Modules\Report\Utils\ReportTransactionsUtile;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SalesComparisonExcelExport implements FromArray, WithColumnWidths, WithEvents
{
    private const COL_LAST = 'Y';

    public function __construct(
        private readonly Collection $rows,
        private readonly array $meta
    ) {}

    public function array(): array
    {
        $n = 25;
        $empty = array_fill(0, $n, '');
        $out = [];

        $r = $empty;
        $r[0] = $this->meta['title'];
        $out[] = $r;

        $r = $empty;
        $r[0] = __('report::general.export_generated_at');
        $r[1] = $this->meta['generated_at'];
        $out[] = $r;

        $r = $empty;
        $r[0] = __('report::general.sales_comparison_period_a');
        $r[1] = $this->meta['period_a_line'];
        $out[] = $r;

        $r = $empty;
        $r[0] = __('report::general.sales_comparison_period_b');
        $r[1] = $this->meta['period_b_line'];
        $out[] = $r;

        $r = $empty;
        $r[0] = __('report::general.export_filters_heading');
        $r[1] = $this->meta['filters'];
        $out[] = $r;

        $r = $empty;
        $r[0] = __('report::general.sales_comparison_group_context');
        $r[6] = __('report::general.sales_comparison_group_period_a');
        $r[12] = __('report::general.sales_comparison_group_period_b');
        $r[18] = __('report::general.sales_comparison_group_variance');
        $out[] = $r;

        $out[] = $this->headerRow();

        foreach ($this->rows as $row) {
            $out[] = $this->mapDataRow($row);
        }

        return $out;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 18,
            'C' => 18,
            'D' => 18,
            'E' => 12,
            'F' => 20,
            'G' => 12,
            'H' => 14,
            'I' => 12,
            'J' => 12,
            'K' => 14,
            'L' => 11,
            'M' => 12,
            'N' => 14,
            'O' => 12,
            'P' => 12,
            'Q' => 14,
            'R' => 11,
            'S' => 12,
            'T' => 12,
            'U' => 14,
            'V' => 12,
            'W' => 12,
            'X' => 12,
            'Y' => 11,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                $sheet->mergeCells('A1:'.self::COL_LAST.'1');
                $sheet->mergeCells('B5:'.self::COL_LAST.'5');
                $sheet->mergeCells('A6:F6');
                $sheet->mergeCells('G6:L6');
                $sheet->mergeCells('M6:R6');
                $sheet->mergeCells('S6:'.self::COL_LAST.'6');

                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getStyle('A1')->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('A1:'.self::COL_LAST.'1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E3A8A'],
                    ],
                ]);

                $sheet->getStyle('A2:'.self::COL_LAST.'5')->applyFromArray([
                    'font' => ['size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC'],
                    ],
                ]);
                $sheet->getStyle('A2:A5')->getFont()->setBold(true);
                $sheet->getStyle('B5:'.self::COL_LAST.'5')->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);

                $groupColors = [
                    'A6:F6' => 'E5E7EB',
                    'G6:L6' => 'BFDBFE',
                    'M6:R6' => 'FED7AA',
                    'S6:'.self::COL_LAST.'6' => 'DDD6FE',
                ];
                foreach ($groupColors as $range => $rgb) {
                    $sheet->getStyle($range)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $rgb],
                        ],
                    ]);
                }

                $sheet->getStyle('A7:'.self::COL_LAST.'7')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F1F5F9'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                ]);

                if ($lastRow >= 8) {
                    $sheet->getStyle('A8:'.self::COL_LAST.$lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'E5E7EB'],
                            ],
                        ],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);

                    for ($r = 8; $r <= $lastRow; $r++) {
                        if ($r % 2 === 0) {
                            $sheet->getStyle('A'.$r.':'.self::COL_LAST.$r)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F9FAFB'],
                                ],
                            ]);
                        }
                    }
                }

                $sheet->freezePane('A8');
            },
        ];
    }

    private function headerRow(): array
    {
        return [
            __('report::fields.product_name'),
            __('report::fields.category'),
            __('report::fields.subcategory'),
            __('report::fields.establishment_name'),
            __('report::fields.SKU'),
            __('report::fields.customer'),
            __('report::fields.qty_period_a'),
            __('report::fields.avg_unit_price_period_a'),
            __('report::fields.discount_period_a'),
            __('report::fields.tax_period_a'),
            __('report::fields.subtotal_period_a'),
            __('report::fields.lines_period_a'),
            __('report::fields.qty_period_b'),
            __('report::fields.avg_unit_price_period_b'),
            __('report::fields.discount_period_b'),
            __('report::fields.tax_period_b'),
            __('report::fields.subtotal_period_b'),
            __('report::fields.lines_period_b'),
            __('report::fields.qty_difference'),
            __('report::fields.qty_change_percent'),
            __('report::fields.subtotal_difference'),
            __('report::fields.subtotal_change_percent'),
            __('report::fields.discount_difference'),
            __('report::fields.tax_difference'),
            __('report::fields.lines_difference'),
        ];
    }

    private function mapDataRow(object $row): array
    {
        $fmt = static fn ($v) => number_format((float) $v, 2);
        $fmtQty = static fn ($v) => number_format((float) $v, 3);
        $fmtPct = static function ($v) {
            if ($v === null) {
                return '—';
            }

            return number_format($v, 2).'%';
        };
        $fmtAvg = static function ($v) {
            if ($v === null) {
                return '—';
            }

            return number_format((float) $v, 4);
        };

        return [
            $row->product_name ?? '--',
            $row->category ?? '--',
            $row->subcategory ?? '--',
            $row->establishment_name ?? '--',
            $row->SKU ?? '--',
            $row->customer ?? '--',
            $fmtQty($row->qty_period_a),
            $fmtAvg($row->avg_unit_price_period_a),
            $fmt($row->discount_period_a),
            $fmt($row->tax_period_a),
            $fmt($row->subtotal_period_a),
            (string) (int) $row->lines_period_a,
            $fmtQty($row->qty_period_b),
            $fmtAvg($row->avg_unit_price_period_b),
            $fmt($row->discount_period_b),
            $fmt($row->tax_period_b),
            $fmt($row->subtotal_period_b),
            (string) (int) $row->lines_period_b,
            $fmtQty($row->qty_difference),
            $fmtPct(ReportTransactionsUtile::computePercentChange(
                (float) $row->qty_period_a,
                (float) $row->qty_period_b
            )),
            $fmt($row->subtotal_difference),
            $fmtPct(ReportTransactionsUtile::computePercentChange(
                (float) $row->subtotal_period_a,
                (float) $row->subtotal_period_b
            )),
            $fmt($row->discount_difference),
            $fmt($row->tax_difference),
            (string) (int) $row->lines_difference,
        ];
    }
}
