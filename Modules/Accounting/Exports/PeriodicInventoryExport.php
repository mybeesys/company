<?php

namespace Modules\Accounting\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Accounting\Models\PeriodicInventory;

class PeriodicInventoryExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected PeriodicInventory $inventory)
    {
        $this->inventory->load(['items.product', 'establishment', 'creator']);
    }

    public function collection()
    {
        return $this->inventory->items;
    }

    public function headings(): array
    {
        return [
            __('accounting::lang.sku'),
            __('accounting::lang.product'),
            __('accounting::lang.unit'),
            __('accounting::lang.system_quantity'),
            __('accounting::lang.physical_quantity'),
            __('accounting::lang.cost_price'),
            __('accounting::lang.difference'),
            __('accounting::lang.total_variance_value'),
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
            (float) $item->system_quantity,
            (float) $item->physical_quantity,
            (float) $item->unit_cost,
            $variance,
            $varianceValue,
        ];
    }
}
