<?php

namespace Modules\Accounting\classes;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AgeingDetailsExport implements FromCollection, WithHeadings, WithStyles
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
            [$this->meta['title']],
            [__('accounting::lang.to_date') . ': ' . $this->meta['as_of_date']],
            [
                __('accounting::lang.current_or_overdue'),
                __('reports.date'),
                __('accounting::lang.transaction_type'),
                __('sales::fields.ref_no'),
                $this->meta['contact_header'],
                __('sales::fields.due_date'),
                __('report::general.cash_due'),
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

