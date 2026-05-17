<?php

namespace Modules\Accounting\classes;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpenseReportExport implements FromCollection, WithHeadings, WithStyles
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
                __('accounting::lang.expense_report').' - '.
                    __('accounting::lang.from_date').': '.$this->meta['start_date'].' - '.
                    __('accounting::lang.to_date').': '.$this->meta['end_date'],
            ],
            [
                __('accounting::lang.expense_report_count').': '.(int) $this->meta['count'].
                    ' | '.__('accounting::lang.expense_report_net').': '.number_format((float) $this->meta['net'], 2, '.', '').
                    ' | '.__('accounting::lang.expense_report_tax').': '.number_format((float) $this->meta['tax'], 2, '.', '').
                    ' | '.__('accounting::lang.expense_report_gross').': '.number_format((float) $this->meta['gross'], 2, '.', ''),
            ],
            [
                __('expense::fields.expense_date'),
                __('expense::fields.id'),
                __('expense::fields.debit_account'),
                __('expense::fields.credit_account'),
                __('expense::fields.cost_center'),
                __('expense::fields.description'),
                __('expense::fields.net_amount'),
                __('expense::fields.tax_amount'),
                __('expense::fields.gross_amount'),
                __('expense::fields.attachments'),
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
