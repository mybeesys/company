<?php

namespace Modules\Accounting\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PeriodicInventoryListExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected Collection $inventories) {}

    public function collection()
    {
        return $this->inventories;
    }

    public function headings(): array
    {
        return [
            __('accounting::lang.inventory_number'),
            __('accounting::lang.periodic_count_date'),
            __('accounting::lang.periodic_purchases_from_reference'),
            __('accounting::lang.establishment_name'),
            __('accounting::lang.opening_value'),
            __('accounting::lang.purchases_value'),
            __('accounting::lang.closing_value'),
            __('accounting::lang.cogs_value'),
            __('accounting::lang.created_by_user'),
            __('accounting::lang.with_adjustment'),
        ];
    }

    public function map($inv): array
    {
        return [
            $inv->id,
            $inv->end_date,
            $inv->start_date,
            $inv->establishment?->name ?? '—',
            (float) $inv->opening_stock_value,
            (float) $inv->purchases_value,
            (float) $inv->closing_stock_value,
            (float) $inv->cogs,
            $inv->creator?->name ?? '—',
            $inv->adjustment_entry_id ? __('messages.yes') : __('messages.no'),
        ];
    }
}
