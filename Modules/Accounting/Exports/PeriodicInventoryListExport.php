<?php

namespace Modules\Accounting\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PeriodicInventoryListExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private int $columnHeaderRow = 1;

    public function __construct(
        protected Collection $inventories,
        protected array $meta = []
    ) {
        $this->columnHeaderRow = 3 + (! empty($this->meta['filter_note']) ? 1 : 0);
    }

    public function title(): string
    {
        return __('accounting::lang.periodic_inventory_log');
    }

    public function collection()
    {
        return $this->inventories;
    }

    public function headings(): array
    {
        $rows = [
            [__('accounting::lang.periodic_inventory_excel_title')],
            [__('accounting::lang.periodic_inventory_excel_generated', ['datetime' => $this->meta['generated_at'] ?? now()->format('Y-m-d H:i')])],
        ];
        if (! empty($this->meta['filter_note'])) {
            $rows[] = [__('accounting::lang.periodic_inventory_excel_filters') . ': ' . $this->meta['filter_note']];
        }
        $rows[] = [
            __('accounting::lang.inventory_number'),
            __('accounting::lang.periodic_count_date'),
            __('accounting::lang.periodic_purchases_from_reference'),
            __('accounting::lang.establishment_name'),
            __('accounting::lang.adjustment_status'),
            __('accounting::lang.opening_value'),
            __('accounting::lang.purchases_value'),
            __('accounting::lang.closing_value'),
            __('accounting::lang.cogs_value'),
            __('accounting::lang.created_by_user'),
        ];

        return $rows;
    }

    public function map($inv): array
    {
        $hasAdj = (bool) $inv->adjustment_entry_id;

        return [
            $inv->id,
            $inv->end_date,
            $inv->start_date,
            $inv->establishment?->name ?? '—',
            $hasAdj ? __('accounting::lang.with_adjustment') : __('accounting::lang.without_adjustment'),
            round((float) $inv->opening_stock_value, 4),
            round((float) $inv->purchases_value, 4),
            round((float) $inv->closing_stock_value, 4),
            round((float) $inv->cogs, 4),
            $inv->creator?->name ?? '—',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 14,
            'C' => 14,
            'D' => 30,
            'E' => 22,
            'F' => 16,
            'G' => 16,
            'H' => 16,
            'I' => 16,
            'J' => 24,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 13]],
            2 => ['font' => ['italic' => true, 'size' => 10]],
            $this->columnHeaderRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9ECFD'],
                ],
            ],
        ];
        if (! empty($this->meta['filter_note'])) {
            $styles[3] = ['font' => ['size' => 10]];
        }

        return $styles;
    }
}
