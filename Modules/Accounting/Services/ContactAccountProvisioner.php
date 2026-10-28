<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\ClientsAndSuppliers\Models\Contact;

/**
 * Creates / renames the GL leaf linked to a customer or supplier contact.
 */
final class ContactAccountProvisioner
{
    /**
     * Ensure the contact has a ledger account. Creates under the configured parent when needed.
     *
     * @throws ValidationException
     */
    public static function ensureForContact(Contact $contact, ?int $preferredAccountId = null): AccountingAccount
    {
        if ($preferredAccountId && $preferredAccountId > 0) {
            $existing = AccountingAccount::query()->find($preferredAccountId);
            if ($existing) {
                if ((int) $contact->account_id === (int) $existing->id) {
                    self::syncAccountNames($existing, (string) $contact->name);
                }

                return $existing;
            }
        }

        if ($contact->account_id) {
            $linked = AccountingAccount::query()->find((int) $contact->account_id);
            if ($linked) {
                self::syncAccountNames($linked, (string) $contact->name);

                return $linked;
            }
        }

        if (! ContactAccountSettings::autoCreateEnabled()) {
            throw ValidationException::withMessages([
                'account_id' => __('clientsandsuppliers::fields.accounting_account_required'),
            ]);
        }

        return self::createUnderConfiguredParent(
            (string) $contact->business_type,
            (string) $contact->name
        );
    }

    /**
     * Same dashboard rule: create/link the leaf GL and persist contacts.account_id.
     */
    public static function ensureLinked(Contact $contact): AccountingAccount
    {
        $account = self::ensureForContact($contact);
        if ((int) $contact->account_id !== (int) $account->id) {
            $contact->account_id = (int) $account->id;
            $contact->save();
        }

        return $account;
    }

    /**
     * Resolve account id for create/update before the contact row exists.
     *
     * @throws ValidationException
     */
    public static function resolveAccountIdForRequest(
        string $businessType,
        string $contactName,
        ?int $requestedAccountId,
        ?int $existingAccountId = null
    ): int {
        $requestedAccountId = $requestedAccountId && $requestedAccountId > 0 ? $requestedAccountId : null;

        if ($requestedAccountId) {
            $account = AccountingAccount::query()->find($requestedAccountId);
            if (! $account) {
                throw ValidationException::withMessages([
                    'account_id' => __('clientsandsuppliers::fields.accounting_account_required'),
                ]);
            }

            // Rename only when updating the contact's already-linked leaf — never rewrite a manually picked shared account.
            if ($existingAccountId && $existingAccountId === $requestedAccountId) {
                self::syncAccountNames($account, $contactName);
            }

            return (int) $account->id;
        }

        if ($existingAccountId) {
            $linked = AccountingAccount::query()->find($existingAccountId);
            if ($linked) {
                self::syncAccountNames($linked, $contactName);

                return (int) $linked->id;
            }
        }

        if (! ContactAccountSettings::autoCreateEnabled()) {
            throw ValidationException::withMessages([
                'account_id' => __('clientsandsuppliers::fields.accounting_account_required'),
            ]);
        }

        return (int) self::createUnderConfiguredParent($businessType, $contactName)->id;
    }

    public static function syncAccountNames(AccountingAccount $account, string $contactName): void
    {
        $name = trim($contactName);
        if ($name === '') {
            return;
        }

        $dirty = false;
        if ((string) $account->name_ar !== $name) {
            $account->name_ar = $name;
            $dirty = true;
        }
        if ((string) $account->name_en !== $name) {
            $account->name_en = $name;
            $dirty = true;
        }

        if ($dirty) {
            $account->save();
        }
    }

    /**
     * @throws ValidationException
     */
    public static function createUnderConfiguredParent(string $businessType, string $contactName): AccountingAccount
    {
        $name = trim($contactName);
        if ($name === '') {
            throw ValidationException::withMessages([
                'client_name' => __('validation.required', ['attribute' => 'name']),
            ]);
        }

        $parentId = ContactAccountSettings::parentIdForBusinessType($businessType);
        if (! $parentId) {
            $gl = $businessType === 'supplier'
                ? ContactAccountSettings::DEFAULT_SUPPLIER_PARENT_GL
                : ContactAccountSettings::DEFAULT_CUSTOMER_PARENT_GL;

            throw ValidationException::withMessages([
                'account_id' => __('accounting::lang.contact_accounts_parent_missing', ['gl' => $gl]),
            ]);
        }

        $configured = AccountingAccount::query()->find($parentId);
        if (! $configured) {
            throw ValidationException::withMessages([
                'account_id' => __('accounting::lang.contact_accounts_parent_missing', [
                    'gl' => $businessType === 'supplier'
                        ? ContactAccountSettings::DEFAULT_SUPPLIER_PARENT_GL
                        : ContactAccountSettings::DEFAULT_CUSTOMER_PARENT_GL,
                ]),
            ]);
        }

        $parent = self::creatableParent($configured, $businessType);

        $payload = [
            'name_ar' => $name,
            'name_en' => $name,
            'account_primary_type' => $parent->account_primary_type,
            'account_sub_type_id' => $parent->account_sub_type_id,
            'detail_type_id' => $parent->detail_type_id,
            'parent_account_id' => $parent->id,
            'account_type' => $parent->account_type ?? $parent->account_primary_type,
            'account_category' => $parent->account_category,
            'created_by' => Auth::id(),
            'status' => 'active',
            'gl_code' => AccountingUtil::next_GLC($parent->id),
        ];

        $child = AccountingAccount::query()->create($payload);

        if (Schema::hasColumn('accounting_accounts', 'allow_direct_posting')) {
            $parent->forceFill(['allow_direct_posting' => false])->save();
            $child->forceFill(['allow_direct_posting' => true])->save();
        }

        if (Schema::hasColumn('accounting_accounts', 'coa_level')) {
            $child->forceFill([
                'coa_level' => max(3, (int) ($parent->coa_level ?? 2) + 1),
            ])->save();
        }

        if (Schema::hasColumn('accounting_accounts', 'show_in_coa')) {
            $child->forceFill([
                'show_in_coa' => ContactAccountSettings::showInCoaEnabled(),
            ])->save();
        }

        return $child->fresh() ?? $child;
    }

    /**
     * Auto-create under the configured parent when it is still a folder.
     * If that account already has postings, use the first child folder without movements
     * (standard CoA: 113 الذمم المدينة → 11301 العملاء - محلي).
     *
     * @throws ValidationException
     */
    public static function creatableParent(AccountingAccount $configured, string $businessType = 'customer'): AccountingAccount
    {
        if (! self::hasDirectMovements((int) $configured->id)) {
            return $configured;
        }

        $children = AccountingAccount::query()
            ->where('parent_account_id', $configured->id)
            ->orderBy('gl_code')
            ->get();

        $open = $children->filter(static fn (AccountingAccount $child) => ! self::hasDirectMovements((int) $child->id));
        if ($open->isEmpty()) {
            throw ValidationException::withMessages([
                'account_id' => __('accounting::lang.contact_accounts_parent_has_movements', [
                    'gl' => $configured->gl_code,
                    'name' => $configured->name_ar ?: $configured->name_en,
                ]),
            ]);
        }

        $preferredGl = $businessType === 'supplier'
            ? ContactAccountSettings::DEFAULT_SUPPLIER_PARENT_GL.'01'
            : ContactAccountSettings::DEFAULT_CUSTOMER_PARENT_GL.'01';
        $preferredName = $businessType === 'supplier' ? 'مورد' : 'محلي';

        $preferred = $open->first(static function (AccountingAccount $child) use ($preferredGl, $preferredName) {
            $gl = (string) $child->gl_code;
            $name = (string) ($child->name_ar ?: $child->name_en);

            return $gl === $preferredGl || mb_stripos($name, $preferredName) !== false;
        });
        if ($preferred) {
            return $preferred;
        }

        if (Schema::hasColumn('accounting_accounts', 'allow_direct_posting')) {
            $folder = $open->first(static fn (AccountingAccount $child) => $child->allow_direct_posting === false);
            if ($folder) {
                return $folder;
            }
        }

        return $open->first();
    }

    public static function hasDirectMovements(int $accountId): bool
    {
        if ($accountId <= 0) {
            return false;
        }

        return AccountingAccountsTransaction::query()
            ->where('accounting_account_id', $accountId)
            ->exists();
    }
}
