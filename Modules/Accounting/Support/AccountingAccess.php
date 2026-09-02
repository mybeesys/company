<?php

namespace Modules\Accounting\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Employee\Support\DashboardAccess;

/**
 * Accounting web authorization: EMS dashboard permissions.
 */
final class AccountingAccess
{
    /**
     * @param  string|list<string>  $permissions
     */
    public static function can(string|array $permissions, ?Authenticatable $user = null): bool
    {
        return DashboardAccess::allows($user ?? auth()->user(), $permissions);
    }

    /**
     * @param  string|list<string>  $permissions
     */
    public static function authorize(string|array $permissions, ?Authenticatable $user = null): void
    {
        DashboardAccess::authorize($user ?? auth()->user(), $permissions);
    }

    public static function authorizeAccountToggle(AccountingAccount $account): void
    {
        self::authorize(
            $account->status === 'active'
                ? AccountingPermissions::TREE_DEACTIVATE
                : AccountingPermissions::TREE_ACTIVATE
        );
    }

    public static function authorizeCostCenterToggle(AccountingCostCenter $costCenter): void
    {
        self::authorize(
            (int) $costCenter->active === 1
                ? AccountingPermissions::COST_CENTER_DEACTIVATE
                : AccountingPermissions::COST_CENTER_ACTIVATE
        );
    }
}
