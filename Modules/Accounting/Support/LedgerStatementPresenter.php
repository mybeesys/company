<?php

namespace Modules\Accounting\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;

final class LedgerStatementPresenter
{
    public static function defaultCurrency(): string
    {
        return 'SAR';
    }

    public static function companyDisplayName(?object $company, bool $localeAr): string
    {
        if (! $company) {
            return (string) config('app.name', '');
        }

        if ($localeAr) {
            return trim((string) ($company->name_ar ?? $company->name ?? ''));
        }

        return trim((string) ($company->name ?? $company->name_ar ?? ''));
    }

    /** @return list<string> */
    public static function companyAddressLines(?object $company, bool $localeAr): array
    {
        if (! $company) {
            return [];
        }

        $lines = [];
        $commercial = trim((string) ($company->commercial_name_ar ?? $company->commercial_name ?? ''));
        if ($commercial !== '' && $localeAr) {
            $lines[] = $commercial;
        }

        $national = trim((string) ($company->national_address ?? ''));
        if ($national !== '') {
            $lines[] = $national;
        }

        $street = trim((string) ($company->street_name ?? $company->address ?? ''));
        $city = trim((string) ($company->city ?? ''));
        $state = trim((string) ($company->state ?? ''));
        $postal = trim((string) ($company->postal_code ?? $company->zip_code ?? ''));

        $cityLine = collect([$street, $city, $state, $postal])->filter()->implode(', ');
        if ($cityLine !== '') {
            $lines[] = $cityLine;
        }

        $country = trim((string) ($company->country ?? ''));
        if ($country !== '') {
            $lines[] = $country;
        }

        return array_values(array_unique($lines));
    }

    public static function companyLogoPath(?object $company): ?string
    {
        if (! $company) {
            return null;
        }

        foreach (['logo', 'logo_path', 'image', 'company_logo'] as $col) {
            if (empty($company->{$col})) {
                continue;
            }
            $path = (string) $company->{$col};
            if (is_file($path)) {
                return $path;
            }
            $public = public_path(ltrim($path, '/'));
            if (is_file($public)) {
                return $public;
            }
            if (function_exists('central_public_storage_url_for_path')) {
                $url = central_public_storage_url_for_path($path);
                if ($url && is_string($url) && is_file($url)) {
                    return $url;
                }
            }
        }

        return null;
    }

    /** URL for browser print; file path for mPDF when no public URL applies. */
    public static function companyLogoSrc(?object $company, bool $forPdf = false): ?string
    {
        $path = self::companyLogoPath($company);
        if (! $path) {
            return null;
        }

        if ($forPdf && is_file($path)) {
            return $path;
        }

        $publicRoot = str_replace('\\', '/', public_path());
        $normalized = str_replace('\\', '/', $path);
        if (str_starts_with($normalized, $publicRoot)) {
            return asset(ltrim(substr($normalized, strlen($publicRoot)), '/'));
        }

        if (function_exists('central_public_storage_url_for_path') && $company) {
            foreach (['logo', 'logo_path', 'image', 'company_logo'] as $col) {
                if (! empty($company->{$col})) {
                    $url = central_public_storage_url_for_path((string) $company->{$col});
                    if (is_string($url) && $url !== '') {
                        return $url;
                    }
                }
            }
        }

        return is_file($path) ? $path : null;
    }

    public static function formatDate(?string $date): string
    {
        if (! $date) {
            return '';
        }

        return Carbon::parse($date)->format('n/j/Y');
    }

    public static function formatAmount(?float $amount, bool $emptyIfZero = false): string
    {
        if ($amount === null) {
            return $emptyIfZero ? '' : '0.00';
        }
        if ($emptyIfZero && abs($amount) < 0.00001) {
            return '';
        }

        return number_format($amount, 2, '.', ',');
    }

    public static function formatSignedBalance(float $balance): string
    {
        return self::formatAmount($balance);
    }

    /**
     * @param  Collection<int, AccountingAccountsTransaction>  $transactions
     * @return list<array{
     *   date: string,
     *   ref: string,
     *   description: string,
     *   due: string,
     *   currency: string,
     *   debit: string,
     *   credit: string,
     *   balance: string,
     *   balance_raw: float,
     * }>
     */
    public static function buildLines(
        Collection $transactions,
        float $openingBalance,
        bool $isDebitNature,
        bool $localeAr,
        bool $showTransactionType
    ): array {
        $currency = self::defaultCurrency();
        $balance = $openingBalance;
        $rows = [];

        foreach ($transactions as $tx) {
            $debitAmt = $tx->type === 'debit' ? (float) $tx->amount : 0.0;
            $creditAmt = $tx->type === 'credit' ? (float) $tx->amount : 0.0;

            if ($isDebitNature) {
                $balance += $debitAmt - $creditAmt;
            } else {
                $balance += $creditAmt - $debitAmt;
            }

            $ref = $tx->accTransMapping?->ref_no ?? $tx->transaction?->ref_no ?? '—';
            if ($showTransactionType && $tx->sub_type) {
                $typeLabel = \Illuminate\Support\Facades\Lang::has('accounting::lang.'.$tx->sub_type)
                    ? __('accounting::lang.'.$tx->sub_type)
                    : $tx->sub_type;
                $ref = trim($ref.' '.$typeLabel);
            }

            $description = trim((string) ($tx->note ?? ''));
            if ($description === '' && $tx->accTransMapping?->note) {
                $description = trim((string) $tx->accTransMapping->note);
            }
            if ($description === '') {
                $description = '—';
            }

            $opDate = $tx->operation_date ? (string) $tx->operation_date : null;

            $rows[] = [
                'date' => self::formatDate($opDate),
                'ref' => $ref,
                'description' => $description,
                'due' => self::formatDate($opDate),
                'currency' => $currency,
                'debit' => self::formatAmount($debitAmt, true),
                'credit' => self::formatAmount($creditAmt, true),
                'balance' => self::formatSignedBalance($balance),
                'balance_raw' => $balance,
            ];
        }

        return $rows;
    }

    public static function accountClassLabel(AccountingAccount $account, bool $localeAr): string
    {
        $primary = $account->account_primary_type
            ? (__('accounting::lang.'.$account->account_primary_type))
            : '';

        $sub = '';
        if ($account->account_sub_type) {
            $subType = $account->account_sub_type;
            $sub = $localeAr
                ? ($subType['name_ar'] ?? '')
                : ($subType['name_en'] ?? '');
        }

        return trim($primary.($sub !== '' ? ' · '.$sub : ''));
    }
}
