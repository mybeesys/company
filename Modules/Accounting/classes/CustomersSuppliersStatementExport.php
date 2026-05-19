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
                __('accounting::lang.css_opening_balance').': '.number_format((float) ($this->meta['opening_balance'] ?? 0), 2, '.', '').
                    ' | '.__('accounting::lang.css_closing_balance').': '.number_format((float) ($this->meta['closing_balance'] ?? 0), 2, '.', '').
                    ' | '.__('accounting::lang.balance').': '.number_format((float) $this->meta['current_balance'], 2, '.', ''),
            ],
            [
                __('accounting::lang.operation_date'),
                __('accounting::lang.transaction_number'),
                __('accounting::lang.transaction_type'),
                __('accounting::lang.ledger_stmt_col_description'),
                __('accounting::lang.establishment_name'),
                __('accounting::lang.cost_center'),
                __('accounting::lang.debit'),
                __('accounting::lang.credit'),
                __('accounting::lang.css_running_balance'),
                __('accounting::lang.added_by'),
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
