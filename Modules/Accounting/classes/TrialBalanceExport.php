<?php

namespace Modules\Accounting\classes;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class TrialBalanceExport implements FromCollection, WithHeadings, WithStyles
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
                __('accounting::lang.trial_balance') . ' - ' .
                    __('accounting::lang.from_date') . ': ' . $this->meta['start_date'] . ' - ' .
                    __('accounting::lang.to_date') . ': ' . $this->meta['end_date'],
            ],
            [
                __('accounting::lang.balance') . ': ' . $this->meta['balance_status'] .
                    ' | ' . __('accounting::lang.difference') . ': ' . number_format((float) $this->meta['difference'], 2, '.', ''),
            ],
            [
                __('accounting::lang.number'),
                __('accounting::lang.name'),
                __('accounting::lang.debit') . ' (' . __('accounting::lang.opening_balance') . ')',
                __('accounting::lang.credit') . ' (' . __('accounting::lang.opening_balance') . ')',
                __('accounting::lang.debit') . ' (' . __('accounting::lang.accounting_transactions') . ')',
                __('accounting::lang.credit') . ' (' . __('accounting::lang.accounting_transactions') . ')',
                __('accounting::lang.debit') . ' (' . __('accounting::lang.closing_balance') . ')',
                __('accounting::lang.credit') . ' (' . __('accounting::lang.closing_balance') . ')',
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

