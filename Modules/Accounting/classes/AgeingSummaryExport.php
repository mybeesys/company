<?php

namespace Modules\Accounting\classes;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AgeingSummaryExport implements FromCollection, WithHeadings, WithStyles
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
            [__('accounting::lang.to_date').': '.$this->meta['as_of_date']],
            [
                $this->meta['name_header'],
                __('accounting::lang.current'),
                __('accounting::lang.1_30_days'),
                __('accounting::lang.31_60_days'),
                __('accounting::lang.61_90_days'),
                __('accounting::lang.91_and_over'),
                __('accounting::lang.total'),
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
