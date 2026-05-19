<?php

declare(strict_types=1);

namespace Modules\Expense\Services;

use Carbon\Carbon;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Expense\Models\Expense;

/**
 * Unified row for expense report (GL debits + optional linked expense voucher).
 */
final class ExpenseReportLine
{
    public float $share_percent = 0;

    public function __construct(
        public int $line_id,
        public string $description,
        public Carbon $date,
        public float $total,
        public float $tax,
        public float $net,
        public string $category_key,
        public string $category_label,
        public ?AccountingAccount $debitAccount = null,
        public ?AccountingAccount $creditAccount = null,
        public ?AccountingCostCenter $costCenter = null,
        public ?string $source_type = null,
        public ?string $source_label = null,
        public ?int $expense_id = null,
        public ?int $transaction_id = null,
        public ?int $mapping_id = null,
        public ?string $detail_url = null,
        public int $attachments_count = 0,
    ) {}

    public static function fromLedgerTransaction(
        AccountingAccountsTransaction $line,
        float $grossTotal,
        ?Expense $linkedExpense = null
    ): self {
        $account = $line->account;
        $categoryKey = ExpenseReportService::resolveLineCategory($account, (string) ($line->sub_type ?? ''));
        $amount = (float) $line->amount;
        $tax = ExpenseReportService::isVatRoutingAccount($account)
            ? $amount
            : (float) ($linkedExpense?->getRawOriginal('tax') ?? 0);
        $net = $tax > 0 && ! ExpenseReportService::isVatRoutingAccount($account)
            ? max($amount - $tax, 0)
            : ($linkedExpense ? (float) $linkedExpense->net_amount : $amount);

        if ($linkedExpense && $tax <= 0) {
            $tax = (float) $linkedExpense->getRawOriginal('tax');
            $net = (float) $linkedExpense->net_amount;
            $amount = (float) $linkedExpense->total;
        }

        $description = trim((string) (
            $linkedExpense?->description
            ?: $line->note
            ?: $line->accTransMapping?->note
            ?: ExpenseReportService::sourceLabel((string) ($line->sub_type ?? ''))
            ?: __('accounting::lang.expense_report_gl_line')
        ));

        $creditAccount = null;
        if ($line->relationLoaded('accTransMapping') && $line->accTransMapping) {
            $creditLine = $line->accTransMapping->transactions
                ->firstWhere('type', 'credit');
            $creditAccount = $creditLine?->account;
        }

        $reportLine = new self(
            line_id: (int) $line->id,
            description: $description,
            date: Carbon::parse($line->operation_date),
            total: $amount,
            tax: $tax,
            net: $net,
            category_key: $categoryKey,
            category_label: ExpenseReportService::categoryLabel($categoryKey),
            debitAccount: $account,
            creditAccount: $creditAccount,
            costCenter: $line->costCenter,
            source_type: (string) ($line->sub_type ?? ''),
            source_label: ExpenseReportService::sourceLabel((string) ($line->sub_type ?? '')),
            expense_id: $linkedExpense?->id,
            transaction_id: $line->transaction_id ? (int) $line->transaction_id : null,
            mapping_id: $line->acc_trans_mapping_id ? (int) $line->acc_trans_mapping_id : null,
            detail_url: $line->ledgerDetailUrl(),
            attachments_count: (int) ($linkedExpense?->attachments_count ?? 0),
        );

        $reportLine->share_percent = $grossTotal > 0.0001
            ? round(($amount / $grossTotal) * 100, 2)
            : 0.0;

        return $reportLine;
    }
}
