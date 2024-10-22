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

class JournalExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    protected $journal;
    protected $totalDebit = 0;
    protected $totalCredit = 0;

    public function __construct($journal)
    {
        $this->journal = $journal;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Returning only transactions
        return collect($this->journal['transactions']);
    }

    public function headings(): array
    {
        return [
            [__('accounting::lang.ref_number') . ' : ' . $this->journal['ref_no'], __('accounting::lang.operation_date') . ' : ' . $this->journal['operation_date'], __('accounting::lang.additionalNotes') . ' : ' . $this->journal['note']],
            [__('accounting::lang.account_name'), __('accounting::lang.cost_center'), __('accounting::lang.debit'), __('accounting::lang.credit'), __('accounting::lang.additionalNotes')],
        ];
    }

    public function map($transaction): array
    {

        if ($transaction['type'] == 'debit') {
            $this->totalDebit += $transaction['amount'];
        } elseif ($transaction['type'] == 'credit') {
            $this->totalCredit += $transaction['amount'];
        }
        return [
            $transaction->account->gl_code . ' - ' . (App::getLocale() == 'ar' ? $transaction->account->name_ar : $transaction->account->name_en),
            $transaction['cost_center_id'] ?? '--',
            $transaction['type'] == 'debit' ? $transaction['amount'] : "0.0",
            $transaction['type'] == 'credit' ? $transaction['amount'] : "0.0",
            $transaction['note'] ?? '--',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->fromArray([
                    ['', __('messages.total'), $this->totalDebit, $this->totalCredit, '']
                ], null, 'A' . ($event->sheet->getHighestRow() + 1));
            },
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);

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