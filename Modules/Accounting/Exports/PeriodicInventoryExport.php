<?php

namespace Modules\Accounting\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Accounting\Models\PeriodicInventory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PeriodicInventoryExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private int $columnHeaderRow = 5;

    public function __construct(protected PeriodicInventory $inventory)
    {
        $this->inventory->load(['items.product', 'establishment', 'creator']);
    }

    public function title(): string
    {
        return 'PI-'.$this->inventory->id;
    }

    public function collection()
    {
        return $this->inventory->items;
    }

    public function headings(): array
    {
        $inv = $this->inventory;

        return [
            [__('accounting::lang.periodic_inventory_detail_excel_title', ['id' => $inv->id])],
            [
                __('accounting::lang.establishment_name'),
                $inv->establishment?->name ?? '—',
                __('accounting::lang.period_from'),
                (string) $inv->start_date,
                __('accounting::lang.periodic_inventory_count_date'),
                (string) $inv->end_date,
                __('accounting::lang.created_by_user'),
                $inv->creator?->name ?? '—',
            ],
            [
                __('accounting::lang.opening_value'),
                round((float) $inv->opening_stock_value, 4),
                __('accounting::lang.purchases_value'),
                round((float) $inv->purchases_value, 4),
                __('accounting::lang.closing_value'),
                round((float) $inv->closing_stock_value, 4),
                __('accounting::lang.cogs_value'),
                round((float) $inv->cogs, 4),
            ],
            [],
            [
                __('accounting::lang.sku'),
                __('accounting::lang.product'),
                __('accounting::lang.unit'),
                __('accounting::lang.system_quantity'),
                __('accounting::lang.physical_quantity'),
                __('accounting::lang.cost_price'),
                __('accounting::lang.difference'),
                __('accounting::lang.total_variance_value'),
            ],
        ];
    }

    public function map($item): array
    {
        $variance = (float) $item->variance;
        $varianceValue = $variance * (float) $item->unit_cost;

        return [
            $item->product?->SKU ?? '',
            app()->getLocale() === 'ar'
                ? ($item->product?->name_ar ?: $item->product?->name_en)
                : ($item->product?->name_en ?: $item->product?->name_ar),
            $item->unit_label ?? '—',
            round((float) $item->system_quantity, 4),
            round((float) $item->physical_quantity, 4),
            round((float) $item->unit_cost, 4),
            round($variance, 4),
            round($varianceValue, 4),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 36,
            'C' => 12,
            'D' => 14,
            'E' => 14,
            'F' => 14,
            'G' => 12,
            'H' => 16,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 13]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
            $this->columnHeaderRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA'],
                ],
            ],
        ];
    }
}
