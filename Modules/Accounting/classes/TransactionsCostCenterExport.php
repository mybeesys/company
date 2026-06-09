<?php

namespace Modules\Accounting\classes;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Support\AccountingNote;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsCostCenterExport implements FromCollection, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $costCenter;

    protected Collection $transactions;

    protected float $totalDebit;

    protected float $totalCredit;

    public function __construct($costCenter, Collection $transactions, float $totalDebit, float $totalCredit)
    {
        $this->costCenter = $costCenter;
        $this->transactions = $transactions;
        $this->totalDebit = $totalDebit;
        $this->totalCredit = $totalCredit;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        $name = app()->getLocale() == 'ar' ? $this->costCenter->name_ar : $this->costCenter->name_en;

        return [
            [__('accounting::lang.cost_center_transactions').' - '.__('accounting::lang.cost_center').' '.$name.' ('.$this->costCenter->account_center_number.')'],
            [
                __('accounting::lang.transaction_number'),
                __('accounting::lang.operation_date'),
                __('accounting::lang.account_name'),
                __('accounting::lang.ledger_narration'),
                __('accounting::lang.added_by'),
                __('accounting::lang.debit'),
                __('accounting::lang.credit'),
            ],
        ];
    }

    /** @param  AccountingAccountsTransaction  $transaction */
    public function map($transaction): array
    {
        $subType = $transaction->sub_type ?? null;
        $typeLabel = $subType
            ? (Lang::has('accounting::lang.'.$subType) ? __('accounting::lang.'.$subType) : $subType)
            : '—';

        $accountLabel = $transaction->account
            ? $transaction->account->gl_code.' - '.(app()->getLocale() == 'ar' ? $transaction->account->name_ar : $transaction->account->name_en)
            : '—';

        return [
            $transaction->displayRefNo().' ('.$typeLabel.')',
            \Carbon\Carbon::parse($transaction->operation_date)->format('d/m/Y'),
            $accountLabel,
            AccountingNote::resolveForDisplay($transaction->note, $transaction->accTransMapping?->note, true),
            $transaction->createdBy?->name ?? '—',
            $transaction->type == 'debit' ? $transaction->amount : '',
            $transaction->type == 'credit' ? $transaction->amount : '',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $footer = [
                    '',
                    '',
                    '',
                    '',
                    __('accounting::lang.total'),
                    $this->totalDebit,
                    $this->totalCredit,
                ];
                $event->sheet->getDelegate()->fromArray([$footer], null, 'A'.($event->sheet->getHighestRow() + 1));
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        foreach (range('A', 'G') as $letter) {
            $sheet->getColumnDimension($letter)->setWidth(22);
        }

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => Color::COLOR_BLACK],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'DAEEF3'],
                ],
            ],
            2 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => Color::COLOR_BLACK],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'E4DFEC'],
                ],
            ],
        ];
    }
}
