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

class CostCenterExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
        return collect($this->costCenters);
    }

    public function headings(): array
    {
        return [
            [__('menuItemLang.costCenter')],
            [__('accounting::lang.cost_center'), __('accounting::lang.main_cost_center')],
        ];
    }

    public function map($costCenters): array
    {



        return [
            $costCenters->account_center_number . ' - ' . (App::getLocale() == 'ar' ? $costCenters->name_ar : $costCenters->name_en),
            $costCenters->parentCostCenter ? $costCenters->parentCostCenter?->account_center_number . ' - ' .(App::getLocale() == 'ar' ? $costCenters->parentCostCenter->name_ar : $costCenters->parentCostCenter->name_en) : '--',

        ];
    }


    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(40);


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