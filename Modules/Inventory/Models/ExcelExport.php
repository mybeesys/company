<?php

namespace Modules\Inventory\Models;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
class ExcelExport implements FromArray, WithHeadings, WithColumnWidths, WithStyles, WithEvents
{
    protected $data;

    public function __construct(array $data)
    {
        
        $this->data = $data;
    }

    public function array(): array
    {
        $result = [];
        $this->toExcelArray($this->data, 0, $result);
        return  $result;
    }

    private function indent($level)
    {
        return str_repeat('    ', $level); // Four spaces for each level
    }

    public function headings(): array
    {
        return [__('reports.description'), __('reports.qty')];
    }

    public function toExcelArray($data, $level, &$result)
    {
        foreach ($data as $parent) {
            // Add parent
            $result[] = [$this->indent($level) . ($parent['name_ar'] ?? $parent['name']), $parent['qty']?? null];

            if (!empty($parent['children'])) {
                $this->toExcelArray($parent['children'], $level+1, $result);
            }
        }
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, // ID column
            'B' => 20, // Name column
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:B' . $sheet->getHighestRow())->getAlignment()->setHorizontal('right');
        $sheet->getStyle('A1:B' . $sheet->getHighestRow())->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'], // Black color
                ],
            ],
        ]);

        return [
            1 => ['font' => ['bold' => true]], // Row 1 (headers)
        ];

        // // Make the headers bold
        // return [
        //     1 => [
        //         'font' => ['bold' => true],
        //     'borders' => [
        //         'allBorders' => [
        //             'borderStyle' => Border::BORDER_THIN,
        //             'color' => ['argb' => '000000'], // Black color
        //         ],
        //     ]], // Row 1 (headers)
        // ];
    }

    public function registerEvents(): array
    {
        return [

            BeforeSheet::class  =>function(BeforeSheet $event){
                $event->getDelegate()->setRightToLeft(true);
            }
        ];
    }
}