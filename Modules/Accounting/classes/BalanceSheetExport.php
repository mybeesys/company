<?php

namespace Modules\Accounting\classes;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BalanceSheetExport implements FromCollection, WithHeadings, WithStyles
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
                __('accounting::lang.balance_sheet').' - '.
                    __('accounting::lang.from_date').': '.$this->meta['start_date'].' - '.
                    __('accounting::lang.to_date').': '.$this->meta['end_date'],
            ],
            [
                __('accounting::lang.balance').': '.$this->meta['balance_status'].
                    ' | '.__('accounting::lang.difference').': '.number_format((float) $this->meta['difference'], 2, '.', ''),
            ],
            [__('accounting::lang.account_type'), __('accounting::lang.account_name'), __('employee::fields.amount')],
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
