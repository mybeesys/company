<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountsRoting;

class FiscalCloseRoutingResolver
{
    public const SECTION = 'fiscal_close';

    public const TYPE_CURRENT_PERIOD_RESULT = 'fiscal_close_current_period_result';

    public const TYPE_RETAINED_EARNINGS = 'fiscal_close_retained_earnings';

    public function currentPeriodResultAccount(): ?AccountingAccount
    {
        return $this->resolveAccount(self::TYPE_CURRENT_PERIOD_RESULT);
    }

    public function retainedEarningsAccount(): ?AccountingAccount
    {
        return $this->resolveAccount(self::TYPE_RETAINED_EARNINGS);
    }

    public function isComplete(): bool
    {
        return $this->currentPeriodResultAccount() !== null
            && $this->retainedEarningsAccount() !== null;
    }

    /**
     * @return array{
     *     complete: bool,
     *     current_period_result: array{id: int, label: string}|null,
     *     retained_earnings: array{id: int, label: string}|null,
     *     missing: list<string>
     * }
     */
    public function status(): array
    {
        $current = $this->currentPeriodResultAccount();
        $retained = $this->retainedEarningsAccount();
        $missing = [];

        if ($current === null) {
            $missing[] = self::TYPE_CURRENT_PERIOD_RESULT;
        }

        if ($retained === null) {
            $missing[] = self::TYPE_RETAINED_EARNINGS;
        }

        return [
            'complete' => $missing === [],
            'current_period_result' => $current ? $this->presentAccount($current) : null,
            'retained_earnings' => $retained ? $this->presentAccount($retained) : null,
            'missing' => $missing,
        ];
    }

    /**
     * @return list<string>
     */
    public function validationErrors(): array
    {
        $errors = [];
        $current = $this->currentPeriodResultAccount();
        $retained = $this->retainedEarningsAccount();

        if ($current === null) {
            $errors[] = __('accounting::fiscal_close.routing_missing_current_result');
        } elseif (! $this->isEquityAccount($current)) {
            $errors[] = __('accounting::fiscal_close.routing_invalid_current_result');
        }

        if ($retained === null) {
            $errors[] = __('accounting::fiscal_close.routing_missing_retained_earnings');
        } elseif (! $this->isEquityAccount($retained)) {
            $errors[] = __('accounting::fiscal_close.routing_invalid_retained_earnings');
        }

        if ($current && $retained && (int) $current->id === (int) $retained->id) {
            $errors[] = __('accounting::fiscal_close.routing_accounts_must_differ');
        }

        return $errors;
    }

    private function resolveAccount(string $type): ?AccountingAccount
    {
        $routing = AccountsRoting::query()
            ->where('section', self::SECTION)
            ->where('type', $type)
            ->first();

        if ($routing === null || empty($routing->account_id)) {
            return null;
        }

        $account = AccountingAccount::query()
            ->where('id', $routing->account_id)
            ->where('status', 'active')
            ->first();

        return $account;
    }

    private function isEquityAccount(AccountingAccount $account): bool
    {
        return in_array((string) $account->account_primary_type, ['equity'], true)
            || in_array((string) $account->account_type, ['equity'], true);
    }

    /**
     * @return array{id: int, label: string, gl_code: string}
     */
    private function presentAccount(AccountingAccount $account): array
    {
        $name = app()->getLocale() === 'ar'
            ? ($account->name_ar ?: $account->name_en)
            : ($account->name_en ?: $account->name_ar);

        return [
            'id' => (int) $account->id,
            'label' => trim((string) $account->gl_code.' — '.$name),
            'gl_code' => (string) $account->gl_code,
        ];
    }
}
