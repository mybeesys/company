<?php

namespace Modules\Accounting\classes;

use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LedgerExport implements FromCollection, WithEvents, WithHeadings, WithMapping, WithStyles
{
    /** @var list<string> */
    private const LEDGER_COLUMN_ORDER = [
        'ref_no',
        'operation_date',
        'narration',
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

    /** When true, transaction type is concatenated into the ref_no column (no separate column). */
    protected bool $mergeTransactionIntoRef = false;

    protected $totalDebit = 0;

    protected $totalCredit = 0;

    protected float $runningBalance = 0.0;

    protected bool $isDebitNature = true;

    public function __construct($account, ?array $ledgerVisibleColumns = null)
    {
        $this->account = $account;
        $order = self::LEDGER_COLUMN_ORDER;
        if ($ledgerVisibleColumns === null || $ledgerVisibleColumns === []) {
            $this->visibleColumns = $order;
        } else {
            $this->visibleColumns = array_values(array_intersect($order, $ledgerVisibleColumns));
        }
        if (! in_array('balance', $this->visibleColumns, true)) {
            $this->visibleColumns[] = 'balance';
            $this->visibleColumns = array_values(array_intersect($order, $this->visibleColumns));
        }
        if (in_array('ref_no', $this->visibleColumns, true) && in_array('transaction', $this->visibleColumns, true)) {
            $this->mergeTransactionIntoRef = true;
            $this->visibleColumns = array_values(array_diff($this->visibleColumns, ['transaction']));
            $this->visibleColumns = array_values(array_intersect($order, $this->visibleColumns));
        }
        $this->runningBalance = (float) ($this->account['opening_balance'] ?? 0);
        $this->isDebitNature = (bool) ($this->account['is_debit_nature'] ?? true);
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
            'ref_no' => $this->mergeTransactionIntoRef
                ? __('accounting::lang.ledger_column_ref_with_type')
                : __('accounting::lang.transaction_number'),
            'operation_date' => __('accounting::lang.operation_date'),
            'narration' => __('accounting::lang.ledger_narration'),
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
        $st = $transaction->sub_type ?? '';
        if ($st === '') {
            return '—';
        }

        return \Illuminate\Support\Facades\Lang::has('accounting::lang.'.$st)
            ? __('accounting::lang.'.$st)
            : $st;
    }

    protected function narrationText($transaction): string
    {
        $t = trim((string) ($transaction->note ?? ''));
        if ($t === '' && $transaction->accTransMapping && $transaction->accTransMapping->note) {
            $t = trim((string) $transaction->accTransMapping->note);
        }

        return $t !== '' ? $t : '—';
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
        if ($this->isDebitNature) {
            if ($transaction->type == 'debit') {
                $this->runningBalance += $transaction->amount;
                $this->totalDebit += $transaction->amount;
            } else {
                $this->runningBalance -= $transaction->amount;
                $this->totalCredit += $transaction->amount;
            }
        } else {
            if ($transaction->type == 'debit') {
                $this->runningBalance -= $transaction->amount;
                $this->totalDebit += $transaction->amount;
            } else {
                $this->runningBalance += $transaction->amount;
                $this->totalCredit += $transaction->amount;
            }
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
                'ref_no' => $this->mergeTransactionIntoRef
                    ? trim($ref.' — '.$this->subTypeLabel($transaction))
                    : $ref,
                'operation_date' => \Carbon\Carbon::parse($transaction->operation_date)->format('d/m/Y'),
                'narration' => $this->narrationText($transaction),
                'transaction' => $this->subTypeLabel($transaction),
                'cost_center' => ($transaction->costCenter
                    ? $transaction->costCenter->account_center_number.' - '.(App::getLocale() == 'ar' ? $transaction->costCenter->name_ar : $transaction->costCenter->name_en)
                    : '--'),
                'added_by' => $transaction->createdBy->name ?? '--',
                'debit' => $transaction->type == 'debit' ? $transaction->amount : '0.0',
                'credit' => $transaction->type == 'credit' ? $transaction->amount : '0.0',
                'balance' => round($this->runningBalance, 2),
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
                $event->sheet->getDelegate()->fromArray([$footer], null, 'A'.($event->sheet->getHighestRow() + 1));
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
