<?php

namespace Modules\Accounting\classes;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JournalReportExport implements FromCollection, WithHeadings, WithStyles
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
                __('accounting::lang.journal_report') . ' - ' .
                    __('accounting::lang.from_date') . ': ' . $this->meta['start_date'] . ' - ' .
                    __('accounting::lang.to_date') . ': ' . $this->meta['end_date'],
            ],
            [
                __('accounting::lang.debit') . ': ' . number_format((float) $this->meta['total_debit'], 2, '.', '') .
                    ' | ' . __('accounting::lang.credit') . ': ' . number_format((float) $this->meta['total_credit'], 2, '.', '') .
                    ' | ' . __('accounting::lang.difference') . ': ' . number_format((float) $this->meta['difference'], 2, '.', ''),
            ],
            [
                __('accounting::lang.ref_no'),
                __('accounting::lang.operation_date'),
                __('accounting::lang.account_name'),
                __('accounting::lang.gl_code'),
                __('accounting::lang.note'),
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
        ];
    }
}

