<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountsRoting;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Accounting\Utils\AutoJournalGuard;
use Modules\Accounting\Utils\PerpetualInventoryAccountResolver;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\General\Models\Setting;
use RuntimeException;

/**
 * Posts automated purchase / purchase-return journals with explicit Saudi-style signs.
 *
 * Purchase (فاتورة مشتريات):
 *   Dr Purchases (gross before discount)
 *   Dr Input VAT
 *   Cr Earned discount (vendor discount) — NEVER sales_discount_allowed
 *   Cr Supplier AP (net payable)
 *   + optional cash settlement: Dr Supplier / Cr Cash
 *
 * Purchase return (مردود مشتريات):
 *   Dr Supplier AP (or Cash on cash refund) — net
 *   Dr Earned discount (reverse)
 *   Cr Purchase returns / purchases (gross)
 *   Cr Input VAT
 *
 * Historical journals are never rewritten here.
 */
class PurchaseJournalPoster
{
    public function __construct(
        private readonly AccountingUtil $accountingUtil
    ) {}

    /**
     * @param  object  $transactionPayment  Payment-like object (account_id/amount mutated while posting)
     * @param  mixed  $request
     */
    public function postPurchase(
        $transaction,
        $transactionPayment,
        int $accTransMappingId,
        $request = null,
        ?int $cashAccountId = null
    ): void {
        $amounts = $this->resolveAmounts($transaction);
        $accounts = $this->resolvePurchaseAccounts($transaction, $amounts['discount_amount']);

        $lines = [];

        // Always recognize the purchase against supplier AP (net payable).
        $lines[] = ['type' => 'credit', 'account_id' => $accounts['supplier_id'], 'amount' => $amounts['final_total'], 'role' => 'supplier_ap'];
        $lines[] = ['type' => 'debit', 'account_id' => $accounts['purchases_id'], 'amount' => $amounts['gross_before_discount'], 'role' => 'purchases'];
        if ($amounts['discount_amount'] > 0) {
            $lines[] = ['type' => 'credit', 'account_id' => $accounts['earned_discount_id'], 'amount' => $amounts['discount_amount'], 'role' => 'earned_discount'];
        }
        $lines[] = ['type' => 'debit', 'account_id' => $accounts['vat_id'], 'amount' => $amounts['tax_amount'], 'role' => 'input_vat'];

        $invoiceType = $this->normalizeInvoiceType($transaction->invoice_type ?? null);
        if ($invoiceType === 'cash') {
            $cashId = $this->requireCashAccountId($cashAccountId, 'purchases cash');
            // Settle AP immediately: Dr Supplier / Cr Cash
            $lines[] = ['type' => 'debit', 'account_id' => $accounts['supplier_id'], 'amount' => $amounts['final_total'], 'role' => 'supplier_settle'];
            $lines[] = ['type' => 'credit', 'account_id' => $cashId, 'amount' => $amounts['final_total'], 'role' => 'cash'];
        }

        $this->assertProposedBalanced($lines);
        $this->persistLines($lines, $transactionPayment, $transaction, $accTransMappingId, $request);
        AutoJournalGuard::assertBalanced($accTransMappingId);
    }

    /**
     * @param  object  $transactionPayment
     * @param  mixed  $request
     */
    public function postPurchaseReturn(
        $transaction,
        $transactionPayment,
        int $accTransMappingId,
        $request = null,
        ?int $cashAccountId = null
    ): void {
        $amounts = $this->resolveAmounts($transaction);
        $accounts = $this->resolvePurchaseReturnAccounts($transaction, $amounts['discount_amount']);

        $lines = [];
        $invoiceType = $this->normalizeInvoiceType($transaction->invoice_type ?? null);

        if ($invoiceType === 'cash') {
            $cashId = $this->requireCashAccountId($cashAccountId, 'purchases-return cash');
            // Cash refund received from vendor.
            $lines[] = ['type' => 'debit', 'account_id' => $cashId, 'amount' => $amounts['final_total'], 'role' => 'cash_refund'];
        } else {
            // Reduce supplier payable (debit AP).
            $lines[] = ['type' => 'debit', 'account_id' => $accounts['supplier_id'], 'amount' => $amounts['final_total'], 'role' => 'supplier_ap'];
        }

        $lines[] = ['type' => 'credit', 'account_id' => $accounts['return_id'], 'amount' => $amounts['gross_before_discount'], 'role' => 'purchase_return'];
        if ($amounts['discount_amount'] > 0) {
            // Reverse earned discount recognized on the original purchase.
            $lines[] = ['type' => 'debit', 'account_id' => $accounts['earned_discount_id'], 'amount' => $amounts['discount_amount'], 'role' => 'earned_discount_reversal'];
        }
        $lines[] = ['type' => 'credit', 'account_id' => $accounts['vat_id'], 'amount' => $amounts['tax_amount'], 'role' => 'input_vat_reversal'];

        $this->assertProposedBalanced($lines);
        $this->persistLines($lines, $transactionPayment, $transaction, $accTransMappingId, $request);
        AutoJournalGuard::assertBalanced($accTransMappingId);
    }

    /**
     * @return array{final_total: float, tax_amount: float, discount_amount: float, gross_before_discount: float}
     */
    public function resolveAmounts($transaction): array
    {
        $resolved = $this->accountingUtil->resolveSellRevenueAmounts($transaction);

        $gross = round((float) $resolved['sales_gross_before_discount'], 2);
        $discount = round((float) $resolved['discount_amount'], 2);
        $tax = round((float) $resolved['tax_amount'], 2);
        $final = round((float) $resolved['final_total'], 2);

        // Prefer header gross when it is the true pre-discount taxable base.
        $headerGross = round((float) ($transaction->total_before_tax ?? 0), 2);
        $headerAfter = round((float) ($transaction->totalAfterDiscount ?? $transaction->total_after_discount ?? 0), 2);
        if ($headerGross > 0 && $headerAfter >= 0 && $headerGross + 0.001 >= $headerAfter) {
            $delta = round(max(0, $headerGross - $headerAfter), 2);
            if ($delta > 0) {
                $discount = $delta;
                $gross = $headerGross;
            } elseif ($discount <= 0) {
                $gross = $headerGross > 0 ? $headerGross : $gross;
            }
        }

        return [
            'final_total' => $final,
            'tax_amount' => $tax,
            'discount_amount' => $discount,
            'gross_before_discount' => $gross,
        ];
    }

    /**
     * @return array{purchases_id: int, vat_id: int, earned_discount_id: ?int, supplier_id: int}
     */
    private function resolvePurchaseAccounts($transaction, float $discountAmount): array
    {
        $purchasesRoute = AccountsRoting::where('type', 'purchases_purchase')->first();
        $vatRoute = AccountsRoting::where('type', 'purchases_vat_calculation')->first();

        $inventoryAssetAccountId = Setting::isPerpetualInventory()
            ? PerpetualInventoryAccountResolver::resolveInventoryAssetAccountId(
                isset($transaction->establishment_id) ? (int) $transaction->establishment_id : null
            )
            : null;

        $purchasesId = Setting::isPerpetualInventory()
            ? (int) ($inventoryAssetAccountId ?: $purchasesRoute?->account_id)
            : (int) ($purchasesRoute?->account_id);

        if ($purchasesId <= 0) {
            throw new RuntimeException('Accounting routing missing for purchases. Please configure purchases_purchase or Inventory (perpetual_inventory_asset).');
        }
        if (! $vatRoute?->account_id) {
            throw new RuntimeException('Accounting routing missing for purchases VAT. Please configure purchases_vat_calculation.');
        }

        return [
            'purchases_id' => $purchasesId,
            'vat_id' => (int) $vatRoute->account_id,
            'earned_discount_id' => $this->requireEarnedDiscountAccountId($discountAmount),
            'supplier_id' => $this->resolveSupplierAccountId($transaction, 'purchases'),
        ];
    }

    /**
     * @return array{return_id: int, vat_id: int, earned_discount_id: ?int, supplier_id: int}
     */
    private function resolvePurchaseReturnAccounts($transaction, float $discountAmount): array
    {
        $purchaseRoute = AccountsRoting::where('type', 'purchases_purchase')->first();
        $returnRoute = AccountsRoting::where('type', 'purchases_purchase_return')->first();
        $vatRoute = AccountsRoting::where('type', 'purchases_vat_calculation')->first();

        if (! $returnRoute?->account_id || ! $vatRoute?->account_id) {
            throw new RuntimeException('Accounting routing missing for purchases-return. Please configure purchases_purchase_return and purchases_vat_calculation.');
        }

        $inventoryAssetAccountId = Setting::isPerpetualInventory()
            ? PerpetualInventoryAccountResolver::resolveInventoryAssetAccountId(
                isset($transaction->establishment_id) ? (int) $transaction->establishment_id : null
            )
            : null;

        $returnId = Setting::isPerpetualInventory()
            ? (int) ($inventoryAssetAccountId ?: $purchaseRoute?->account_id ?: $returnRoute->account_id)
            : (int) $returnRoute->account_id;

        return [
            'return_id' => $returnId,
            'vat_id' => (int) $vatRoute->account_id,
            'earned_discount_id' => $this->requireEarnedDiscountAccountId($discountAmount),
            'supplier_id' => $this->resolveSupplierAccountId($transaction, 'purchases-return'),
        ];
    }

    /**
     * Vendor-side earned discount only — never sales_discount_allowed.
     */
    public function requireEarnedDiscountAccountId(float $discountAmount): ?int
    {
        $discountAmount = round(max(0, $discountAmount), 2);
        if ($discountAmount <= 0) {
            return null;
        }

        $route = AccountsRoting::where('type', 'purchases_earned_discount')->first();
        $accountId = (int) ($route?->account_id ?? 0);
        if ($accountId <= 0) {
            throw new RuntimeException('Purchase discount is present but purchases_earned_discount is not configured in Accounts Routing (do not use sales discount allowed).');
        }

        // Hard isolation: refuse if the same account is accidentally the only sales-allowed route
        // and purchases route points at sales key (misconfiguration guard is route-type based above).
        return $accountId;
    }

    private function resolveSupplierAccountId($transaction, string $context): int
    {
        $contactId = (int) ($transaction->contact_id ?? 0);
        $client = $contactId > 0 ? Contact::find($contactId) : null;
        if (! $client || ! $client->account_id) {
            throw new RuntimeException("Supplier account is missing for {$context}. Please link an accounting account to the supplier.");
        }

        $accountId = (int) $client->account_id;
        if ($accountId <= 0 || ! AccountingAccount::whereKey($accountId)->exists()) {
            throw new RuntimeException("Supplier accounting account #{$accountId} is invalid for {$context}.");
        }

        return $accountId;
    }

    private function requireCashAccountId(?int $cashAccountId, string $context): int
    {
        $cashId = (int) ($cashAccountId ?? 0);
        if ($cashId <= 0) {
            throw new RuntimeException("Cash/bank account is missing for {$context}.");
        }
        if (! AccountingAccount::whereKey($cashId)->exists()) {
            throw new RuntimeException("Cash account #{$cashId} is invalid for {$context}.");
        }

        return $cashId;
    }

    private function normalizeInvoiceType(mixed $invoiceType): string
    {
        $type = strtolower(trim((string) $invoiceType));
        if ($type === 'credit') {
            return 'due';
        }
        if (! in_array($type, ['cash', 'due'], true)) {
            return 'due';
        }

        return $type;
    }

    /**
     * @param  array<int, array{type: string, account_id: int, amount: float, role: string}>  $lines
     */
    public function assertProposedBalanced(array $lines): void
    {
        $debit = '0.00';
        $credit = '0.00';

        foreach ($lines as $line) {
            $amount = number_format(round((float) ($line['amount'] ?? 0), 2), 2, '.', '');
            if ((float) $amount <= 0) {
                // Allow zero tax lines to be skipped by caller; reject negative.
                if ((float) $amount < 0) {
                    throw new RuntimeException('Purchase auto journal contains a negative line amount.');
                }
                continue;
            }
            if (($line['type'] ?? '') === 'debit') {
                $debit = $this->bcAdd($debit, $amount);
            } elseif (($line['type'] ?? '') === 'credit') {
                $credit = $this->bcAdd($credit, $amount);
            } else {
                throw new RuntimeException('Purchase auto journal line has invalid type.');
            }
        }

        if ($this->bcComp($debit, $credit) !== 0) {
            throw new RuntimeException("Purchase auto journal is not balanced before save: debit {$debit} != credit {$credit}");
        }
    }

    /**
     * @param  array<int, array{type: string, account_id: int, amount: float, role?: string}>  $lines
     * @param  object  $transactionPayment
     * @param  mixed  $request
     */
    private function persistLines(
        array $lines,
        $transactionPayment,
        $transaction,
        int $accTransMappingId,
        $request
    ): void {
        foreach ($lines as $line) {
            $amount = round((float) $line['amount'], 2);
            if ($amount <= 0) {
                continue;
            }
            $transactionPayment->account_id = (int) $line['account_id'];
            $transactionPayment->amount = $amount;
            $this->accountingUtil->saveAccountRouteTransaction(
                (string) $line['type'],
                $transactionPayment,
                $transaction,
                $accTransMappingId,
                $request
            );
        }
    }

    private function bcAdd(string $left, string $right): string
    {
        if (function_exists('bcadd')) {
            return bcadd($left, $right, 2);
        }

        return number_format((float) $left + (float) $right, 2, '.', '');
    }

    private function bcComp(string $left, string $right): int
    {
        if (function_exists('bccomp')) {
            return bccomp($left, $right, 2);
        }

        $diff = (float) $left - (float) $right;
        if (abs($diff) < 0.005) {
            return 0;
        }

        return $diff > 0 ? 1 : -1;
    }
}
