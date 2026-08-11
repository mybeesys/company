<?php

declare(strict_types=1);

namespace Modules\Accounting\Utils;

use Illuminate\Support\Collection;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Establishment\Models\Establishment;

final class InternalConsumptionAccountResolver
{
    /**
     * Resolve expense account for internal consumption, walking parent establishments.
     */
    public static function resolveExpenseAccountId(?int $establishmentId): ?int
    {
        if (! $establishmentId) {
            return null;
        }

        $currentId = $establishmentId;
        $guard = 0;

        while ($currentId && $guard < 20) {
            $row = Establishment::query()
                ->withTrashed()
                ->where('id', $currentId)
                ->first(['id', 'parent_id', 'internal_consumption_expense_account_id']);

            if (! $row) {
                break;
            }

            if (! empty($row->internal_consumption_expense_account_id)) {
                return (int) $row->internal_consumption_expense_account_id;
            }

            $currentId = $row->parent_id ? (int) $row->parent_id : null;
            $guard++;
        }

        return null;
    }

    /**
     * Leaf expense accounts suitable for the establishment settings dropdown.
     *
     * @return Collection<int, AccountingAccount>
     */
    public static function linkableExpenseAccounts(): Collection
    {
        $parentIds = AccountingAccount::query()
            ->whereNotNull('parent_account_id')
            ->pluck('parent_account_id')
            ->unique()
            ->filter()
            ->values();

        return AccountingAccount::query()
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '')->orWhere('status', 'active');
            })
            ->where(function ($q) {
                $q->whereRaw('LOWER(account_primary_type) = ?', ['expenses'])
                    ->orWhereRaw('LOWER(account_primary_type) = ?', ['expense'])
                    ->orWhereRaw('LOWER(account_type) = ?', ['expenses'])
                    ->orWhereRaw('LOWER(account_type) = ?', ['expense'])
                    ->orWhereRaw("LEFT(REPLACE(gl_code, '.', ''), 1) = '5'");
            })
            ->when($parentIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $parentIds))
            ->orderBy('gl_code')
            ->get(['id', 'gl_code', 'name_ar', 'name_en', 'account_primary_type', 'account_category']);
    }
}
