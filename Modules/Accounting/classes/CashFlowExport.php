<?php

namespace Modules\Accounting\classes;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashFlowExport implements FromCollection, WithHeadings, WithStyles
{
    public function __construct(
        private readonly Collection $rows,
        private readonly array $meta
    ) {}

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            [
                __('accounting::lang.cash_flow_statement').' - '.
                    __('accounting::lang.from_date').': '.$this->meta['start_date'].' - '.
                    __('accounting::lang.to_date').': '.$this->meta['end_date'],
            ],
            [
                __('accounting::lang.cash_inflows').': '.number_format((float) $this->meta['cash_inflows'], 2, '.', '').
                    ' | '.__('accounting::lang.cash_outflows').': '.number_format((float) $this->meta['cash_outflows'], 2, '.', '').
                    ' | '.__('accounting::lang.net_cash_flows').': '.number_format((float) $this->meta['net_cash_flow'], 2, '.', ''),
            ],
            [
                __('accounting::lang.activity_section'),
                __('accounting::lang.operation_date'),
                __('accounting::lang.transaction_number'),
                __('accounting::lang.transaction_type'),
                __('accounting::lang.movement_type'),
                __('accounting::lang.cost_center'),
                __('employee::fields.amount'),
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
        ];
    }
}
