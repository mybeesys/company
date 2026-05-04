<?php

namespace Modules\Accounting\classes;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LedgerExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    /** @var list<string> */
    private const LEDGER_COLUMN_ORDER = [
        'ref_no',
        'operation_date',
        'transaction',
        'cost_center',
        'added_by',
        'debit',
        'credit',
        'balance',
    ];

    protected $account;

    /** @var list<string> */
    protected $visibleColumns;

    protected $totalDebit = 0;

    protected $totalCredit = 0;

    protected $runningBalance = 0.0;

    public function __construct($account, ?array $ledgerVisibleColumns = null)
    {
        $this->account = $account;
        $order = self::LEDGER_COLUMN_ORDER;
        if ($ledgerVisibleColumns === null || $ledgerVisibleColumns === []) {
            $this->visibleColumns = array_values(array_diff($order, ['transaction']));
        } else {
            $this->visibleColumns = array_values(array_intersect($order, $ledgerVisibleColumns));
        }
        if (! in_array('balance', $this->visibleColumns, true)) {
            $this->visibleColumns[] = 'balance';
            $this->visibleColumns = array_values(array_intersect($order, $this->visibleColumns));
        }
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->account['transactions']);
    }

    protected function headingLabel(string $key): string
    {
        return match ($key) {
            'ref_no' => __('accounting::lang.transaction_number'),
            'operation_date' => __('accounting::lang.operation_date'),
            'transaction' => __('accounting::lang.transaction'),
            'cost_center' => __('accounting::lang.cost_center'),
            'added_by' => __('accounting::lang.added_by'),
            'debit' => __('accounting::lang.debit'),
            'credit' => __('accounting::lang.credit'),
            'balance' => __('accounting::lang.balance'),
            default => $key,
        };
    }

    protected function subTypeLabel($transaction): string
    {
        if ($transaction->sub_type === 'sell') {
            return __('accounting::lang.sell');
        }
        if ($transaction->sub_type === 'sell_cash') {
            return __('accounting::lang.receipt_voucher');
        }
        if ($transaction->sub_type === 'sales_revenue') {
            return __('accounting::lang.payment_voucher');
        }

        return __('accounting::lang.' . $transaction->sub_type);
    }

    public function headings(): array
    {
        $headers = [];
        foreach ($this->visibleColumns as $key) {
            $headers[] = $this->headingLabel($key);
        }

        return $headers;
    }

    public function map($transaction): array
    {
        if ($transaction->type == 'debit') {
            $this->totalDebit += $transaction->amount;
            $this->runningBalance += $transaction->amount;
        } elseif ($transaction->type == 'credit') {
            $this->totalCredit += $transaction->amount;
            $this->runningBalance -= $transaction->amount;
        }

        $ref = '--';
        if ($transaction->accTransMapping) {
            $ref = $transaction->accTransMapping->ref_no;
        } elseif ($transaction->transaction) {
            $ref = $transaction->transaction->ref_no;
        }

        $row = [];
        foreach ($this->visibleColumns as $key) {
            $row[] = match ($key) {
                'ref_no' => $ref,
                'operation_date' => \Carbon\Carbon::parse($transaction->operation_date)->format('d/m/Y h:i A'),
                'transaction' => $this->subTypeLabel($transaction),
                'cost_center' => ($transaction->costCenter
                    ? $transaction->costCenter->account_center_number . ' - ' . (App::getLocale() == 'ar' ? $transaction->costCenter->name_ar : $transaction->costCenter->name_en)
                    : '--'),
                'added_by' => $transaction->createdBy->name ?? '--',
                'debit' => $transaction->type == 'debit' ? $transaction->amount : '0.0',
                'credit' => $transaction->type == 'credit' ? $transaction->amount : '0.0',
                'balance' => $this->runningBalance,
                default => '',
            };
        }

        return $row;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $n = count($this->visibleColumns);
                $footer = array_fill(0, $n, '');
                $footer[0] = __('messages.total');
                $debitIdx = array_search('debit', $this->visibleColumns, true);
                $creditIdx = array_search('credit', $this->visibleColumns, true);
                if ($debitIdx !== false) {
                    $footer[$debitIdx] = $this->totalDebit;
                }
                if ($creditIdx !== false) {
                    $footer[$creditIdx] = $this->totalCredit;
                }
                $event->sheet->getDelegate()->fromArray([$footer], null, 'A' . ($event->sheet->getHighestRow() + 1));
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        foreach (range('A', 'Z') as $i => $letter) {
            if ($i >= count($this->visibleColumns)) {
                break;
            }
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
                    'startColor' => ['argb' => 'E4DFEC'],
                ],
            ],
        ];
    }
}
