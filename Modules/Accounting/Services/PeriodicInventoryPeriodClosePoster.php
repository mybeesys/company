<?php

namespace Modules\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\AccountsRoting;
use Modules\Accounting\Models\PeriodicInventory;
use Modules\Accounting\Services\FiscalPeriod\PeriodicInventoryFiscalGuard;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Accounting\Utils\PerpetualInventoryAccountResolver;

/**
 * Posts the period-end inventory / COGS journal for periodic inventory policy.
 *
 * Balanced set (COGS = opening + purchases − closing):
 *   Dr COGS (opening + purchases − closing)
 *   Cr Purchases (purchases)
 *   Dr/Cr Inventory (closing − opening)
 */
final class PeriodicInventoryPeriodClosePoster
{
    /**
     * @return AccountingAccTransMapping|null
     */
    public function post(PeriodicInventory $inventory): ?AccountingAccTransMapping
    {
        $opening = round((float) ($inventory->opening_stock_value ?? 0), 2);
        $purchases = round((float) ($inventory->purchases_value ?? 0), 2);
        $closing = round((float) ($inventory->closing_stock_value ?? 0), 2);
        $cogs = round((float) ($inventory->cogs ?? ($opening + $purchases - $closing)), 2);

        if (abs($cogs) < 0.005 && abs($purchases) < 0.005 && abs($closing - $opening) < 0.005) {
            return null;
        }

        PeriodicInventoryFiscalGuard::assertInventoryPeriodPostable($inventory);

        $accounts = $this->resolveAccounts();
        $operationDate = Carbon::parse($inventory->end_date)->endOfDay()->format('Y-m-d H:i:s');
        $userId = Auth::id() ?? (int) ($inventory->created_by ?? 1);

        $lines = [];

        if (abs($cogs) >= 0.005) {
            $lines[] = [
                'accounting_account_id' => $accounts['cogs'],
                'amount' => abs($cogs),
                'type' => $cogs >= 0 ? 'debit' : 'credit',
                'note' => 'تكلفة مبيعات الفترة (مخزون أول + صافي المشتريات − مخزون آخر)',
            ];
        }

        if (abs($purchases) >= 0.005) {
            $lines[] = [
                'accounting_account_id' => $accounts['purchases'],
                'amount' => abs($purchases),
                'type' => $purchases >= 0 ? 'credit' : 'debit',
                'note' => 'إقفال صافي مشتريات الفترة',
            ];
        }

        $inventoryDelta = round($closing - $opening, 2);
        if (abs($inventoryDelta) >= 0.005) {
            $lines[] = [
                'accounting_account_id' => $accounts['inventory'],
                'amount' => abs($inventoryDelta),
                'type' => $inventoryDelta >= 0 ? 'debit' : 'credit',
                'note' => 'تسوية مخزون آخر المدة مقابل أول المدة',
            ];
        }

        if ($lines === []) {
            return null;
        }

        $this->assertBalanced($lines);

        return DB::transaction(function () use ($inventory, $operationDate, $userId, $lines) {
            $mapping = AccountingAccTransMapping::create([
                'ref_no' => AccountingUtil::generateReferenceNumber('journal_entry'),
                'note' => 'إقفال جرد دوري — تكلفة المبيعات والمخزون للفترة من '.$inventory->start_date.' إلى '.$inventory->end_date,
                'type' => 'journal_entry',
                'created_by' => $userId,
                'operation_date' => $operationDate,
            ]);

            foreach ($lines as $line) {
                AccountingAccountsTransaction::create([
                    'accounting_account_id' => $line['accounting_account_id'],
                    'amount' => $line['amount'],
                    'type' => $line['type'],
                    'note' => $line['note'],
                    'created_by' => $userId,
                    'operation_date' => $operationDate,
                    'sub_type' => 'periodic_inventory_close',
                    'acc_trans_mapping_id' => $mapping->id,
                ]);
            }

            $inventory->update(['adjustment_entry_id' => $mapping->id]);

            return $mapping;
        });
    }

    /**
     * @return array{inventory: int, purchases: int, cogs: int}
     */
    private function resolveAccounts(): array
    {
        $inventoryId = (int) (
            PerpetualInventoryAccountResolver::defaultGlobalInventoryAssetAccountId()
            ?: AccountingAccount::query()
                ->where(function ($q) {
                    $q->where('account_category', 'inventory')
                        ->orWhereIn('gl_code', ['11505', '1105', '11501']);
                })
                ->value('id')
        );

        $purchasesId = (int) AccountsRoting::query()
            ->where('type', 'purchases_purchase')
            ->value('account_id');

        $cogsId = (int) (
            PerpetualInventoryAccountResolver::resolveCogsAccountId()
            ?: AccountsRoting::query()
                ->where('type', 'periodic_inventory_adjustment')
                ->where('section', 'periodic_inventory')
                ->value('account_id')
            ?: AccountingAccount::query()
                ->where(function ($q) {
                    $q->whereIn('account_category', ['COGS', 'cost_of_goods_sold', 'inventory_adjustment'])
                        ->orWhereIn('gl_code', ['51101', '50101']);
                })
                ->value('id')
        );

        if ($inventoryId <= 0 || $purchasesId <= 0 || $cogsId <= 0) {
            throw new \RuntimeException(app()->getLocale() === 'ar'
                ? 'لا يمكن ترحيل إقفال الجرد الدوري. اضبط حسابات المخزون والمشتريات وتكلفة المبيعات / تسوية الجرد من توجيه الحسابات.'
                : 'Cannot post periodic inventory close. Configure Inventory, Purchases, and COGS / periodic adjustment accounts in Accounts Routing.');
        }

        return [
            'inventory' => $inventoryId,
            'purchases' => $purchasesId,
            'cogs' => $cogsId,
        ];
    }

    /**
     * @param  list<array{amount: float, type: string}>  $lines
     */
    private function assertBalanced(array $lines): void
    {
        $debit = 0.0;
        $credit = 0.0;
        foreach ($lines as $line) {
            if ($line['type'] === 'debit') {
                $debit += (float) $line['amount'];
            } else {
                $credit += (float) $line['amount'];
            }
        }

        if (abs($debit - $credit) > 0.02) {
            throw new \RuntimeException(app()->getLocale() === 'ar'
                ? 'قيد إقفال الجرد الدوري غير متوازن. راجع قيم أول المدة والمشتريات وآخر المدة.'
                : 'Periodic inventory close journal is unbalanced. Review opening, purchases, and closing values.');
        }
    }
}
