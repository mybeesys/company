<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingAccount;
use Modules\General\Models\Setting;

/**
 * Settings for auto-provisioned customer/supplier ledger accounts.
 * Stored in the shared `settings` key/value table (same pattern as period locking).
 */
final class ContactAccountSettings
{
    public const KEY_AUTO_CREATE = 'contact_accounts_auto_create';

    public const KEY_CUSTOMER_PARENT_ID = 'customer_account_parent_id';

    public const KEY_SUPPLIER_PARENT_ID = 'supplier_account_parent_id';

    public const KEY_SHOW_IN_COA = 'contact_accounts_show_in_coa';

    public const DEFAULT_CUSTOMER_PARENT_GL = '113';

    public const DEFAULT_SUPPLIER_PARENT_GL = '211';

    public static function autoCreateEnabled(): bool
    {
        $raw = Setting::query()->where('key', self::KEY_AUTO_CREATE)->value('value');

        // Default ON when the setting was never saved (ERP expectation).
        if ($raw === null) {
            return true;
        }

        return (string) $raw === '1';
    }

    public static function showInCoaEnabled(): bool
    {
        $raw = Setting::query()->where('key', self::KEY_SHOW_IN_COA)->value('value');

        // Default OFF so contact leaves stay out of the chart clutter until enabled.
        if ($raw === null) {
            return false;
        }

        return (string) $raw === '1';
    }

    public static function customerParentId(): ?int
    {
        return self::resolveParentId(self::KEY_CUSTOMER_PARENT_ID, self::DEFAULT_CUSTOMER_PARENT_GL);
    }

    public static function supplierParentId(): ?int
    {
        return self::resolveParentId(self::KEY_SUPPLIER_PARENT_ID, self::DEFAULT_SUPPLIER_PARENT_GL);
    }

    public static function parentIdForBusinessType(string $businessType): ?int
    {
        return $businessType === 'supplier'
            ? self::supplierParentId()
            : self::customerParentId();
    }

    /**
     * @return array{
     *     auto_create: bool,
     *     show_in_coa: bool,
     *     customer_parent_id: ?int,
     *     supplier_parent_id: ?int,
     *     customer_parent: ?AccountingAccount,
     *     supplier_parent: ?AccountingAccount,
     *     default_customer_gl: string,
     *     default_supplier_gl: string
     * }
     */
    public static function snapshot(): array
    {
        $customerParentId = self::customerParentId();
        $supplierParentId = self::supplierParentId();

        return [
            'auto_create' => self::autoCreateEnabled(),
            'show_in_coa' => self::showInCoaEnabled(),
            'customer_parent_id' => $customerParentId,
            'supplier_parent_id' => $supplierParentId,
            'customer_parent' => $customerParentId
                ? AccountingAccount::query()->find($customerParentId)
                : null,
            'supplier_parent' => $supplierParentId
                ? AccountingAccount::query()->find($supplierParentId)
                : null,
            'default_customer_gl' => self::DEFAULT_CUSTOMER_PARENT_GL,
            'default_supplier_gl' => self::DEFAULT_SUPPLIER_PARENT_GL,
        ];
    }

    /**
     * @param  array{auto_create?: bool, show_in_coa?: bool, customer_parent_id?: int|null, supplier_parent_id?: int|null}  $data
     */
    public static function save(array $data): void
    {
        self::put(self::KEY_AUTO_CREATE, ! empty($data['auto_create']) ? '1' : '0');
        self::put(self::KEY_SHOW_IN_COA, ! empty($data['show_in_coa']) ? '1' : '0');

        $customerParentId = (int) ($data['customer_parent_id'] ?? 0);
        $supplierParentId = (int) ($data['supplier_parent_id'] ?? 0);

        self::put(self::KEY_CUSTOMER_PARENT_ID, $customerParentId > 0 ? (string) $customerParentId : '');
        self::put(self::KEY_SUPPLIER_PARENT_ID, $supplierParentId > 0 ? (string) $supplierParentId : '');

        if (Schema::hasColumn('accounting_accounts', 'show_in_coa')) {
            self::syncContactAccountsVisibility(self::showInCoaEnabled());
        }
    }

    public static function syncContactAccountsVisibility(bool $showInCoa): void
    {
        if (! Schema::hasColumn('accounting_accounts', 'show_in_coa')) {
            return;
        }

        $accountIds = \Modules\ClientsAndSuppliers\Models\Contact::query()
            ->whereNotNull('account_id')
            ->pluck('account_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        if ($accountIds === []) {
            return;
        }

        AccountingAccount::query()
            ->whereIn('id', $accountIds)
            ->update(['show_in_coa' => $showInCoa]);
    }

    private static function resolveParentId(string $settingKey, string $defaultGl): ?int
    {
        $stored = (int) (Setting::query()->where('key', $settingKey)->value('value') ?? 0);
        if ($stored > 0 && AccountingAccount::query()->whereKey($stored)->exists()) {
            return $stored;
        }

        $byGl = AccountingAccount::query()
            ->where('gl_code', $defaultGl)
            ->value('id');

        return $byGl ? (int) $byGl : null;
    }

    private static function put(string $key, string $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
