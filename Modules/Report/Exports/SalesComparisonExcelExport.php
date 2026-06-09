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
    private string $lastCol;

    private int $colCount;

    private bool $weekdaySingleWindow;

    private bool $hasWeekdaysRow;

    private int $filterRow;

    private int $groupRow;

    private int $headerRow;

    private int $dataStartRow;

    private int $metaLastRow;

    private string $wsrViewMode;

    public function __construct(
        private readonly Collection $rows,
        private readonly array $meta
    ) {
        $this->weekdaySingleWindow = ! empty($this->meta['wsr_export_single_window']);
        $this->hasWeekdaysRow = isset($this->meta['weekdays_line']) && (string) $this->meta['weekdays_line'] !== '';
        $this->wsrViewMode = (string) ($this->meta['wsr_view_mode'] ?? '');

        if ($this->weekdaySingleWindow) {
            $this->colCount = 12;
            $this->lastCol = 'L';
        } else {
            $this->colCount = 25;
            $this->lastCol = 'Y';
        }

        $lastMetaBeforeFilter = 3;
        if (! $this->weekdaySingleWindow) {
            $lastMetaBeforeFilter++;
        }
        if ($this->hasWeekdaysRow) {
            $lastMetaBeforeFilter++;
        }

        $this->filterRow = $lastMetaBeforeFilter + 1;
        $this->groupRow = $this->filterRow + 1;
        $this->headerRow = $this->groupRow + 1;
        $this->dataStartRow = $this->headerRow + 1;
        $this->metaLastRow = $this->filterRow;
    }

    public function array(): array
    {
        $empty = array_fill(0, $this->colCount, '');
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

        if (! $this->weekdaySingleWindow) {
            $r = $empty;
            $r[0] = __('report::general.sales_comparison_period_b');
            $r[1] = $this->meta['period_b_line'];
            $out[] = $r;
        }

        if ($this->hasWeekdaysRow) {
            $r = $empty;
            $r[0] = __('report::general.weekday_export_selected_days');
            $r[1] = $this->meta['weekdays_line'];
            $out[] = $r;
        }

        $r = $empty;
        $r[0] = __('report::general.export_filters_heading');
        $r[1] = $this->meta['filters'];
        $out[] = $r;

        $r = $empty;
        $r[0] = __('report::general.sales_comparison_group_context');
        if ($this->weekdaySingleWindow) {
            $r[6] = __('report::general.weekday_report_export_metrics_period');
        } else {
            $r[6] = __('report::general.sales_comparison_group_period_a');
            $r[12] = __('report::general.sales_comparison_group_period_b');
            $r[18] = __('report::general.sales_comparison_group_variance');
        }
        $out[] = $r;

        $out[] = $this->headerRowData();

        foreach ($this->rows as $row) {
            $out[] = $this->mapDataRow($row);
        }

        return $out;
    }

    public function columnWidths(): array
    {
        if ($this->weekdaySingleWindow) {
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
            ];
        }

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

                $fr = $this->filterRow;
                $gr = $this->groupRow;
                $hr = $this->headerRow;
                $dr = $this->dataStartRow;
                $metaEnd = $this->metaLastRow;
                $lc = $this->lastCol;

                $sheet->mergeCells('A1:'.$lc.'1');
                $sheet->mergeCells('B'.$fr.':'.$lc.$fr);

                if ($this->weekdaySingleWindow) {
                    $sheet->mergeCells('A'.$gr.':F'.$gr);
                    $sheet->mergeCells('G'.$gr.':'.$lc.$gr);
                } else {
                    $sheet->mergeCells('A'.$gr.':F'.$gr);
                    $sheet->mergeCells('G'.$gr.':L'.$gr);
                    $sheet->mergeCells('M'.$gr.':R'.$gr);
                    $sheet->mergeCells('S'.$gr.':'.$lc.$gr);
                }

                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getStyle('A1')->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('A1:'.$lc.'1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E3A8A'],
                    ],
                ]);

                $sheet->getStyle('A2:'.$lc.$metaEnd)->applyFromArray([
                    'font' => ['size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC'],
                    ],
                ]);
                $sheet->getStyle('A2:A'.$metaEnd)->getFont()->setBold(true);
                $sheet->getStyle('B'.$fr.':'.$lc.$fr)->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);

                if ($this->weekdaySingleWindow) {
                    $groupColors = [
                        'A'.$gr.':F'.$gr => 'E5E7EB',
                        'G'.$gr.':'.$lc.$gr => 'BFDBFE',
                    ];
                } else {
                    $groupColors = [
                        'A'.$gr.':F'.$gr => 'E5E7EB',
                        'G'.$gr.':L'.$gr => 'BFDBFE',
                        'M'.$gr.':R'.$gr => 'FED7AA',
                        'S'.$gr.':'.$lc.$gr => 'DDD6FE',
                    ];
                }
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

                $sheet->getStyle('A'.$hr.':'.$lc.$hr)->applyFromArray([
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

                if ($lastRow >= $dr) {
                    $sheet->getStyle('A'.$dr.':'.$lc.$lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'E5E7EB'],
                            ],
                        ],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);

                    for ($r = $dr; $r <= $lastRow; $r++) {
                        if ($r % 2 === 0) {
                            $sheet->getStyle('A'.$r.':'.$lc.$r)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F9FAFB'],
                                ],
                            ]);
                        }
                    }
                }

                $sheet->freezePane('A'.$dr);
            },
        ];
    }

    /**
     * @return list<string>
     */
    private function headerRowData(): array
    {
        $colA = __('report::fields.product_name');
        $colB = __('report::fields.category');

        if ($this->wsrViewMode === 'by_date') {
            $colA = __('report::fields.transaction_date');
        } elseif ($this->wsrViewMode === 'by_date_product') {
            // In this view we put the date string into the "category" column.
            $colB = __('report::fields.transaction_date');
        } elseif ($this->wsrViewMode === 'by_day') {
            $colA = __('report::general.weekday_report_column_day');
        }

        $base = [
            $colA,
            $colB,
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
        ];

        if ($this->weekdaySingleWindow) {
            return $base;
        }

        return array_merge($base, [
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
        ]);
    }

    /**
     * @return list<string>
     */
    private function mapDataRow(object $row): array
    {
        $fmt = static fn ($v) => number_format((float) $v, 2);
        $fmtQty = static fn ($v) => number_format((float) $v, 3);
        $fmtAvg = static function ($v) {
            if ($v === null) {
                return '—';
            }

            return number_format((float) $v, 4);
        };

        $cells = [
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
        ];

        if ($this->weekdaySingleWindow) {
            return $cells;
        }

        return array_merge($cells, [
            $fmtQty($row->qty_period_b),
            $fmtAvg($row->avg_unit_price_period_b),
            $fmt($row->discount_period_b),
            $fmt($row->tax_period_b),
            $fmt($row->subtotal_period_b),
            (string) (int) $row->lines_period_b,
            $fmtQty($row->qty_difference),
            ReportTransactionsUtile::formatPercentChangeForDisplay(
                ReportTransactionsUtile::computePercentChange(
                    (float) $row->qty_period_a,
                    (float) $row->qty_period_b
                ),
                (float) $row->qty_period_a,
                (float) $row->qty_period_b
            ),
            $fmt($row->subtotal_difference),
            ReportTransactionsUtile::formatPercentChangeForDisplay(
                ReportTransactionsUtile::computePercentChange(
                    (float) $row->subtotal_period_a,
                    (float) $row->subtotal_period_b
                ),
                (float) $row->subtotal_period_a,
                (float) $row->subtotal_period_b
            ),
            $fmt($row->discount_difference),
            $fmt($row->tax_difference),
            (string) (int) $row->lines_difference,
        ]);
    }
}
