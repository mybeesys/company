<?php

namespace Modules\Accounting\classes;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersSuppliersStatementExport implements FromCollection, WithHeadings, WithStyles
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
                __('accounting::lang.customers_and_suppliers_statement_of_account_report').' - '.$this->meta['contact_name'],
            ],
            [
                __('accounting::lang.from_date').': '.$this->meta['start_date'].' - '.
                    __('accounting::lang.to_date').': '.$this->meta['end_date'],
            ],
            [
                __('accounting::lang.balance').': '.number_format((float) $this->meta['current_balance'], 2, '.', '').
                    ' | '.__('accounting::lang.debit').': '.number_format((float) $this->meta['period_debit'], 2, '.', '').
                    ' | '.__('accounting::lang.credit').': '.number_format((float) $this->meta['period_credit'], 2, '.', ''),
            ],
            [
                __('accounting::lang.number'),
                __('accounting::lang.operation_date'),
                __('accounting::lang.transaction'),
                __('accounting::lang.cost_center'),
                __('employee::general.notes'),
                __('accounting::lang.added_by'),
                __('accounting::lang.debit'),
                __('accounting::lang.credit'),
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
        ];
    }
}
