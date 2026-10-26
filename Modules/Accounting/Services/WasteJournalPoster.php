<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\AccountsRoting;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodGatekeeper;
use Modules\Accounting\Support\AccountingNote;
use Modules\Accounting\Support\WasteDocument;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Accounting\Utils\AutoJournalGuard;
use Modules\Accounting\Utils\PerpetualInventoryAccountResolver;
use Modules\General\Models\Setting;
use Modules\Inventory\Services\InventoryCostingService;
use RuntimeException;

/**
 * Waste / spoilage GL:
 *   Perpetual: Dr waste_expense    Cr perpetual_inventory_asset
 *   Periodic:  Dr waste_expense    Cr purchases (purchases_purchase)
 *
 * Amount = inventory cost (average / FIFO / LIFO), never sales revenue or VAT.
 */
class WasteJournalPoster
{
    public function __construct(
        private readonly AccountingUtil $accountingUtil
    ) {}

    public function postForTransaction($transaction, $request = null, bool $required = false): bool
    {
        if (! WasteDocument::isWaste($transaction)) {
            if ($required) {
                throw new RuntimeException($this->trans('not_a_waste_document'));
            }

            return false;
        }

        if (($transaction->status ?? '') === 'draft') {
            if ($required) {
                throw new RuntimeException($this->trans('draft_not_allowed'));
            }

            return false;
        }

        $alreadyPosted = AccountingAccountsTransaction::query()
            ->where('transaction_id', $transaction->id)
            ->where('sub_type', WasteDocument::JOURNAL_SUB_TYPE)
            ->exists();

        if ($alreadyPosted) {
            return true;
        }

        app(InventoryCostingService::class)->processTransaction($transaction);

        $amount = $this->resolveCostAmount($transaction);
        if ($amount <= 0) {
            throw new RuntimeException($this->trans('cost_required'));
        }

        $accounts = $this->resolveAccounts($transaction);
        FiscalPeriodGatekeeper::assertPostable($transaction->transaction_date ?? now());

        $createdBy = $transaction->created_by ?? Auth::id() ?? 1;
        $operationDate = Carbon::parse($transaction->transaction_date ?? now())->format('Y-m-d H:i:s');

        $mapping = new AccountingAccTransMapping;
        $mapping->ref_no = AccountingUtil::generateReferenceNumber('journal_entry');
        $mapping->note = AccountingNote::compose(
            app()->getLocale() === 'ar' ? 'إتلاف مخزون' : 'Inventory waste',
            Setting::isPerpetualInventory()
                ? (app()->getLocale() === 'ar' ? 'الجرد المستمر' : 'Perpetual')
                : (app()->getLocale() === 'ar' ? 'الجرد الدوري' : 'Periodic'),
            AccountingNote::documentRef($transaction)
        );
        $mapping->type = 'journal_entry';
        $mapping->created_by = $createdBy;
        $mapping->is_manual = 0;
        $mapping->operation_date = $operationDate;
        $mapping->save();

        $lines = self::proposedLines($amount, $accounts['expense'], $accounts['credit']);
        $this->persistLines($lines, $transaction, (int) $mapping->id, $request, $createdBy, $operationDate);
        AutoJournalGuard::assertBalanced((int) $mapping->id);

        return true;
    }

    /**
     * @return list<array{type: string, account_id: int, amount: float, role: string}>
     */
    public static function proposedLines(float $amount, int $expenseAccountId, int $creditAccountId): array
    {
        $amount = round($amount, 2);

        return [
            [
                'type' => 'debit',
                'account_id' => $expenseAccountId,
                'amount' => $amount,
                'role' => 'waste_expense',
            ],
            [
                'type' => 'credit',
                'account_id' => $creditAccountId,
                'amount' => $amount,
                'role' => 'inventory_or_purchases',
            ],
        ];
    }

    /**
     * @return array{expense: int, credit: int, policy: string}
     */
    public function resolveAccounts($transaction): array
    {
        $expenseId = (int) (
            AccountsRoting::query()
                ->where('type', 'waste_expense')
                ->where('section', 'waste')
                ->value('account_id')
        );
        if ($expenseId <= 0 || ! AccountingAccount::whereKey($expenseId)->exists()) {
            throw new RuntimeException($this->trans('routing_missing_waste_expense'));
        }

        $establishmentId = isset($transaction->establishment_id) ? (int) $transaction->establishment_id : 0;

        if (Setting::isPerpetualInventory()) {
            $creditId = PerpetualInventoryAccountResolver::resolveInventoryAssetAccountId($establishmentId);
            if (! $creditId) {
                throw new RuntimeException($this->trans('routing_missing_inventory_asset'));
            }

            return [
                'expense' => $expenseId,
                'credit' => (int) $creditId,
                'policy' => 'perpetual',
            ];
        }

        $creditId = (int) AccountsRoting::query()
            ->where('type', 'purchases_purchase')
            ->value('account_id');
        if ($creditId <= 0 || ! AccountingAccount::whereKey($creditId)->exists()) {
            throw new RuntimeException($this->trans('routing_missing_purchases'));
        }

        return [
            'expense' => $expenseId,
            'credit' => $creditId,
            'policy' => 'periodic',
        ];
    }

    private function resolveCostAmount($transaction): float
    {
        $fromEngine = round(
            app(InventoryCostingService::class)->resolveCogsAmountForSell((int) $transaction->id),
            2
        );
        if ($fromEngine > 0) {
            return $fromEngine;
        }

        $fromDocument = round((float) ($transaction->final_total ?? 0), 2);
        if ($fromDocument <= 0) {
            $fromDocument = round((float) ($transaction->total_before_tax
                ?? $transaction->totalAfterDiscount
                ?? $transaction->total_after_discount
                ?? 0), 2);
        }

        return max(0.0, $fromDocument);
    }

    /**
     * @param  list<array{type: string, account_id: int, amount: float, role: string}>  $lines
     */
    private function persistLines(
        array $lines,
        $transaction,
        int $mappingId,
        $request,
        $createdBy,
        string $operationDate
    ): void {
        $stub = (object) [
            'id' => null,
            'amount' => 0,
            'account_id' => 0,
            'paid_on' => $operationDate,
            'created_by' => $createdBy,
            'transaction_id' => $transaction->id,
        ];

        foreach ($lines as $line) {
            if (($line['amount'] ?? 0) <= 0) {
                continue;
            }
            $stub->account_id = (int) $line['account_id'];
            $stub->amount = (float) $line['amount'];
            $this->accountingUtil->saveAccountRouteTransaction(
                $line['type'],
                $stub,
                $transaction,
                $mappingId,
                $request,
                WasteDocument::JOURNAL_SUB_TYPE
            );
        }
    }

    private function trans(string $key): string
    {
        $locale = app()->getLocale();
        $messages = [
            'ar' => [
                'not_a_waste_document' => 'المستند ليس إتلاف مخزون.',
                'draft_not_allowed' => 'لا يمكن ترحيل قيد الإتلاف لمسودة. اعتمد العملية أولاً.',
                'cost_required' => 'تعذّر احتساب تكلفة الأصناف لقيد الإتلاف. راجع تكلفة المخزون والكميات.',
                'routing_missing_waste_expense' => 'توجيه الحسابات ناقص لمصروف الإتلاف (waste_expense). اضبطه من توجيه الحسابات → المخزون.',
                'routing_missing_inventory_asset' => 'الجرد المستمر يتطلب حساب مخزون (أصل). اضبط perpetual_inventory_asset في توجيه الحسابات.',
                'routing_missing_purchases' => 'الجرد الدوري يتطلب حساب المشتريات. اضبط purchases_purchase في توجيه الحسابات.',
            ],
            'en' => [
                'not_a_waste_document' => 'Document is not an inventory waste write-off.',
                'draft_not_allowed' => 'Cannot post a waste journal for a draft. Approve the operation first.',
                'cost_required' => 'Could not resolve inventory cost for the waste journal. Check costing and quantities.',
                'routing_missing_waste_expense' => 'Accounts routing is missing waste_expense. Configure it under Accounts Routing → Inventory.',
                'routing_missing_inventory_asset' => 'Perpetual inventory requires an Inventory asset account. Configure perpetual_inventory_asset.',
                'routing_missing_purchases' => 'Periodic inventory requires the purchases account. Configure purchases_purchase.',
            ],
        ];

        return $messages[$locale][$key] ?? $messages['en'][$key] ?? $key;
    }
}
