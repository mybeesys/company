<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\FinancialYear;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Utils\AccountingUtil;

class FiscalPeriodAccountingCloseService
{
    public const SUB_TYPE = 'fiscal_period_close';

    public function __construct(
        private readonly FiscalCloseRoutingResolver $routing,
        private readonly FiscalPeriodPlBalanceCalculator $plCalculator,
        private readonly FiscalPeriodCloseReadinessChecker $readinessChecker,
    ) {}

    /**
     * @return array{readiness: array, year: array}
     */
    public function readiness(FinancialYear $year, ?FiscalPeriod $closingPeriod = null): array
    {
        $readiness = $this->enrichReadiness($this->readinessChecker->check($year, $closingPeriod), $year);

        return [
            'readiness' => $readiness,
            'year' => $this->presentYear($year),
        ];
    }

    /**
     * @return array{
     *     readiness: array,
     *     preview: array|null,
     *     year: array
     * }
     */
    public function preview(FinancialYear $year, ?FiscalPeriod $closingPeriod = null): array
    {
        $readiness = $this->enrichReadiness($this->readinessChecker->check($year, $closingPeriod), $year);

        if (! $readiness['can_preview']) {
            return [
                'readiness' => $readiness,
                'preview' => null,
                'year' => $this->presentYear($year),
            ];
        }

        return [
            'readiness' => $readiness,
            'preview' => $this->buildPreview($year, (bool) ($readiness['is_repair'] ?? false)),
            'year' => $this->presentYear($year),
        ];
    }

    /**
     * @return array{
     *     posted: bool,
     *     already_posted: bool,
     *     repaired: bool,
     *     journal_id: int|null,
     *     ref_no: string|null,
     *     preview: array,
     *     year: array
     * }
     */
    public function execute(FinancialYear $year, ?FiscalPeriod $closingPeriod = null, ?int $userId = null): array
    {
        $userId = $userId ?? (int) Auth::id();
        if ($userId <= 0) {
            throw new \InvalidArgumentException(__('accounting::fiscal_close.execute_auth_required'));
        }

        $year->refresh();

        $readiness = $this->enrichReadiness($this->readinessChecker->check($year, $closingPeriod), $year);
        if (! $readiness['can_preview']) {
            throw new \InvalidArgumentException(
                implode(' ', $readiness['blocking_messages']) ?: __('accounting::fiscal_close.preview_not_available')
            );
        }

        $isRepair = (bool) ($readiness['is_repair'] ?? false);
        $isRemedial = (bool) ($readiness['is_remedial'] ?? false);
        $alreadyPosted = $this->hasAccountingClosePosted($year);

        $preview = $this->buildPreview($year, $isRepair);
        $lines = $preview['lines'] ?? [];

        if ($alreadyPosted && ! $isRepair) {
            return [
                'posted' => false,
                'already_posted' => true,
                'repaired' => false,
                'journal_id' => $year->accounting_close_journal_id
                    ? (int) $year->accounting_close_journal_id
                    : null,
                'ref_no' => $this->existingCloseRefNo($year),
                'preview' => $preview,
                'year' => $this->presentYear($year->fresh()),
            ];
        }

        if ($alreadyPosted && $isRepair && $lines === []) {
            return [
                'posted' => false,
                'already_posted' => true,
                'repaired' => false,
                'journal_id' => $year->accounting_close_journal_id
                    ? (int) $year->accounting_close_journal_id
                    : null,
                'ref_no' => $this->existingCloseRefNo($year),
                'preview' => $preview,
                'year' => $this->presentYear($year->fresh()),
            ];
        }

        if ($lines !== [] && ! ($preview['totals']['is_balanced'] ?? false)) {
            throw new \InvalidArgumentException(__('accounting::fiscal_close.execute_unbalanced'));
        }

        if (! $isRemedial && ! $isRepair) {
            FiscalPeriodGatekeeper::assertPostable($preview['journal_date']);
        }

        $journalId = null;
        $refNo = null;

        DB::transaction(function () use ($year, $userId, $preview, $lines, $isRepair, $alreadyPosted, &$journalId, &$refNo) {
            if ($lines !== []) {
                $refNo = $isRepair
                    ? $this->buildRepairReferenceNumber($year)
                    : $this->buildReferenceNumber($year);
                $operationDateTime = $preview['journal_date'].' 23:59:59';
                $now = now();

                $note = $isRepair
                    ? __('accounting::fiscal_close.repair_journal_note', ['name' => $year->name])
                    : __('accounting::fiscal_close.journal_note', ['name' => $year->name]);

                $mapping = AccountingAccTransMapping::query()->create([
                    'ref_no' => $refNo,
                    'note' => $note,
                    'type' => 'journal_entry',
                    'created_by' => $userId,
                    'is_manual' => 0,
                    'operation_date' => $operationDateTime,
                ]);

                $rows = [];
                foreach ($lines as $line) {
                    if ((float) ($line['debit'] ?? 0) <= 0 && (float) ($line['credit'] ?? 0) <= 0) {
                        continue;
                    }

                    $type = (float) ($line['debit'] ?? 0) > 0 ? 'debit' : 'credit';
                    $amount = $type === 'debit' ? (float) $line['debit'] : (float) $line['credit'];

                    $rows[] = [
                        'accounting_account_id' => (int) $line['account_id'],
                        'amount' => $amount,
                        'type' => $type,
                        'cost_center_id' => null,
                        'note' => $line['description'] ?? null,
                        'created_by' => $userId,
                        'operation_date' => $operationDateTime,
                        'sub_type' => self::SUB_TYPE,
                        'acc_trans_mapping_id' => $mapping->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($rows, 500) as $chunk) {
                    AccountingAccountsTransaction::query()->insert($chunk);
                }

                $journalId = (int) $mapping->id;
            }

            if (! $alreadyPosted) {
                $this->markAccountingClosePosted($year, $journalId, $userId);
            } elseif ($isRepair && $journalId) {
                $year->update([
                    'accounting_close_journal_id' => $journalId,
                ]);
            }
        });

        return [
            'posted' => true,
            'already_posted' => false,
            'repaired' => $isRepair,
            'journal_id' => $journalId,
            'ref_no' => $refNo,
            'preview' => $preview,
            'year' => $this->presentYear($year->fresh()),
        ];
    }

    /**
     * @param  array<string, mixed>  $readiness
     * @return array<string, mixed>
     */
    private function enrichReadiness(array $readiness, FinancialYear $year): array
    {
        $hasResiduals = false;
        if ($this->hasAccountingClosePosted($year) && ($readiness['can_preview'] ?? false)) {
            $hasResiduals = $this->plCalculator->accountsWithBalances($year)->isNotEmpty();
            if ($hasResiduals) {
                $readiness['warnings'][] = __('accounting::fiscal_close.residual_pl_balances');
                $readiness['warnings'] = array_values(array_unique($readiness['warnings']));
            }
        }

        $readiness['has_residual_pl'] = $hasResiduals;
        $readiness['is_repair'] = $hasResiduals;

        return $readiness;
    }

    /**
     * @return array{
     *     journal_date: string,
     *     totals: array,
     *     routing: array,
     *     lines: list<array>,
     *     note: string,
     *     is_repair: bool
     * }
     */
    private function buildPreview(FinancialYear $year, bool $isRepair = false): array
    {
        $routing = $this->routing->status();
        $currentResult = $this->routing->currentPeriodResultAccount();
        $retained = $this->routing->retainedEarningsAccount();
        $totals = $this->plCalculator->totals($year);
        $accounts = $this->plCalculator->accountsWithBalances($year);

        $lines = [];

        foreach ($accounts as $account) {
            $signed = (float) $account->signed_balance;
            if (abs($signed) < 0.0001 || $currentResult === null) {
                continue;
            }

            if ($account->is_income) {
                $lines[] = $this->line(
                    step: 'pl_close',
                    account: $account,
                    debit: abs($signed),
                    credit: 0.0,
                    description: __('accounting::fiscal_close.line_pl_close'),
                );
                $lines[] = $this->line(
                    step: 'pl_close',
                    account: $currentResult,
                    debit: 0.0,
                    credit: abs($signed),
                    description: __('accounting::fiscal_close.line_pl_close_counter'),
                );
            } else {
                $lines[] = $this->line(
                    step: 'pl_close',
                    account: $currentResult,
                    debit: abs($signed),
                    credit: 0.0,
                    description: __('accounting::fiscal_close.line_pl_close_counter'),
                );
                $lines[] = $this->line(
                    step: 'pl_close',
                    account: $account,
                    debit: 0.0,
                    credit: abs($signed),
                    description: __('accounting::fiscal_close.line_pl_close'),
                );
            }
        }

        $netIncome = round($totals['net_income'], 2);

        if (abs($netIncome) > 0.0001 && $retained !== null && $currentResult !== null) {
            if ($netIncome > 0) {
                $lines[] = $this->line(
                    step: 'retained_transfer',
                    account: $currentResult,
                    debit: $netIncome,
                    credit: 0.0,
                    description: __('accounting::fiscal_close.line_to_retained'),
                );
                $lines[] = $this->line(
                    step: 'retained_transfer',
                    account: $retained,
                    debit: 0.0,
                    credit: $netIncome,
                    description: __('accounting::fiscal_close.line_to_retained'),
                );
            } else {
                $loss = abs($netIncome);
                $lines[] = $this->line(
                    step: 'retained_transfer',
                    account: $retained,
                    debit: $loss,
                    credit: 0.0,
                    description: __('accounting::fiscal_close.line_to_retained_loss'),
                );
                $lines[] = $this->line(
                    step: 'retained_transfer',
                    account: $currentResult,
                    debit: 0.0,
                    credit: $loss,
                    description: __('accounting::fiscal_close.line_to_retained_loss'),
                );
            }
        }

        $totalDebit = round(collect($lines)->sum('debit'), 2);
        $totalCredit = round(collect($lines)->sum('credit'), 2);

        $note = match (true) {
            $isRepair => __('accounting::fiscal_close.repair_preview_note'),
            $this->hasAccountingClosePosted($year) => __('accounting::fiscal_close.already_posted_note'),
            default => __('accounting::fiscal_close.preview_execute_note'),
        };

        return [
            'journal_date' => $year->end_date->toDateString(),
            'totals' => array_merge($totals, [
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
            ]),
            'routing' => $routing,
            'lines' => $lines,
            'note' => $note,
            'is_repair' => $isRepair,
        ];
    }

    private function hasAccountingClosePosted(FinancialYear $year): bool
    {
        if (! Schema::hasColumn($year->getTable(), 'accounting_closed_at')) {
            return false;
        }

        return $year->accounting_closed_at !== null;
    }

    private function markAccountingClosePosted(FinancialYear $year, ?int $journalId, int $userId): void
    {
        if (! Schema::hasColumn($year->getTable(), 'accounting_closed_at')) {
            return;
        }

        $year->update([
            'accounting_close_journal_id' => $journalId,
            'accounting_closed_at' => now(),
            'accounting_closed_by' => $userId,
        ]);
    }

    private function buildReferenceNumber(FinancialYear $year): string
    {
        $base = 'FY-CLOSE-'.$year->id.'-'.str_replace('-', '', $year->end_date->toDateString());

        if (! AccountingAccTransMapping::query()->where('ref_no', $base)->exists()) {
            return $base;
        }

        return AccountingUtil::generateReferenceNumber('journal_entry');
    }

    private function buildRepairReferenceNumber(FinancialYear $year): string
    {
        $base = 'FY-CLOSE-REPAIR-'.$year->id.'-'.str_replace('-', '', $year->end_date->toDateString());

        if (! AccountingAccTransMapping::query()->where('ref_no', $base)->exists()) {
            return $base;
        }

        return AccountingUtil::generateReferenceNumber('journal_entry');
    }

    private function existingCloseRefNo(FinancialYear $year): ?string
    {
        if (empty($year->accounting_close_journal_id)) {
            return null;
        }

        return AccountingAccTransMapping::query()
            ->where('id', $year->accounting_close_journal_id)
            ->value('ref_no');
    }

    /**
     * @return array{
     *     step: string,
     *     account_id: int,
     *     account_label: string,
     *     gl_code: string,
     *     debit: float,
     *     credit: float,
     *     description: string
     * }
     */
    private function line(string $step, object $account, float $debit, float $credit, string $description): array
    {
        $name = app()->getLocale() === 'ar'
            ? (($account->name_ar ?? null) ?: ($account->name_en ?? ''))
            : (($account->name_en ?? null) ?: ($account->name_ar ?? ''));

        return [
            'step' => $step,
            'account_id' => (int) $account->id,
            'account_label' => trim((string) ($account->gl_code ?? '').' — '.$name),
            'gl_code' => (string) ($account->gl_code ?? ''),
            'debit' => $debit,
            'credit' => $credit,
            'description' => $description,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     start_date: string,
     *     end_date: string,
     *     status: string,
     *     accounting_closed_at: string|null,
     *     accounting_close_journal_id: int|null
     * }
     */
    private function presentYear(FinancialYear $year): array
    {
        return [
            'id' => (int) $year->id,
            'name' => (string) $year->name,
            'start_date' => $year->start_date->toDateString(),
            'end_date' => $year->end_date->toDateString(),
            'status' => (string) $year->status,
            'accounting_closed_at' => $year->accounting_closed_at?->toIso8601String(),
            'accounting_close_journal_id' => $year->accounting_close_journal_id
                ? (int) $year->accounting_close_journal_id
                : null,
        ];
    }
}
