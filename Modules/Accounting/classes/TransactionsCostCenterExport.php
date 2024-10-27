<?php

namespace Modules\Accounting\classes;

use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TransactionsCostCenterExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $costCenters;

    public function __construct($costCenters)
    {
        $this->costCenters = $costCenters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->costCenters['transactions']);
    }

    public function headings(): array
    {
        return [
            [__('accounting::lang.cost_center_transactions') . ' - ' . __('accounting::lang.cost_center') . ' ' . (app()->getLocale() == 'ar' ? $this->costCenters->name_ar : $this->costCenters->name_en) . ' (' . $this->costCenters->account_center_number . ')'],
            [__('accounting::lang.transaction_number'), __('accounting::lang.operation_date'), __('accounting::lang.transaction'), __('accounting::lang.added_by'), __('accounting::lang.amount')],

        ];
    }

    public function map($transactions): array
    {



        return [
            $transactions->accTransMapping->ref_no,
            \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $transactions->operation_date)->format('d/m/Y h:i A'),
            __('accounting::lang.' . $transactions->sub_type),
            $transactions->createdBy->name,
            $transactions->amount,

        ];
    }


    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    // 'size' => 14,
                    'color' => ['argb' => Color::COLOR_BLACK],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'DAEEF3'],
                ]
            ],
            2 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => Color::COLOR_BLACK],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'E4DFEC'],
                ]
            ],
        ];
    }
}