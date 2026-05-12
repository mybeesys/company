<?php

namespace Modules\Accounting\Utils;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccountsTransaction;

class StandaloneVoucherHelper
{
    /**
     * @return array{0: AccountingAccountsTransaction, 1: AccountingAccountsTransaction} [debit, credit]
     */
    public static function receiptLines(int $lineId): array
    {
        return self::pairedLines($lineId, 'receipt_voucher');
    }

    /**
     * @return array{0: AccountingAccountsTransaction, 1: AccountingAccountsTransaction} [debit, credit]
     */
    public static function paymentLines(int $lineId): array
    {
        return self::pairedLines($lineId, 'payment_voucher');
    }

    /**
     * @return array<string, mixed>
     */
    public static function receiptFormPayload(int $lineId): array
    {
        [$debit, $credit] = self::receiptLines($lineId);

        return [
            'account_id' => $debit->accounting_account_id,
            'from_account' => $credit->accounting_account_id,
            'paid_amount' => $debit->amount,
            'pament_on' => self::formatDateInput($debit->operation_date),
            'cost_center_id' => $debit->cost_center_id,
            'additionalNotes' => $debit->note ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function paymentFormPayload(int $lineId): array
    {
        [$debit, $credit] = self::paymentLines($lineId);

        return [
            'account_id' => $credit->accounting_account_id,
            'from_account' => $debit->accounting_account_id,
            'paid_amount' => $debit->amount,
            'pament_on' => self::formatDateInput($debit->operation_date),
            'cost_center_id' => $debit->cost_center_id,
            'additionalNotes' => $debit->note ?? '',
        ];
    }

    /**
     * @return array{0: AccountingAccountsTransaction, 1: AccountingAccountsTransaction}
     */
    private static function pairedLines(int $lineId, string $subType): array
    {
        $line = AccountingAccountsTransaction::query()
            ->where('sub_type', $subType)
            ->findOrFail($lineId);

        $other = self::partnerOrFail($line, $subType);

        $debit = $line->type === 'debit' ? $line : $other;
        $credit = $line->type === 'credit' ? $line : $other;

        if ($debit->type !== 'debit' || $credit->type !== 'credit') {
            throw (new ModelNotFoundException)->setModel(AccountingAccountsTransaction::class, [$lineId]);
        }

        return [$debit, $credit];
    }

    private static function partnerOrFail(AccountingAccountsTransaction $line, string $subType): AccountingAccountsTransaction
    {
        if (! $line->transaction_id) {
            throw (new ModelNotFoundException)->setModel(AccountingAccountsTransaction::class);
        }

        $other = AccountingAccountsTransaction::query()->find($line->transaction_id);
        if (! $other || $other->sub_type !== $subType) {
            throw (new ModelNotFoundException)->setModel(AccountingAccountsTransaction::class);
        }

        return $other;
    }

    private static function formatDateInput($operationDate): string
    {
        if ($operationDate === null || $operationDate === '') {
            return Carbon::now()->format('Y-m-d');
        }

        return Carbon::parse($operationDate)->format('Y-m-d');
    }

    /**
     * Payload for voucher show / print (classic bilingual layout).
     *
     * @param  'receipt_voucher'|'payment_voucher'  $subType
     * @return array<string, mixed>
     */
    public static function buildVoucherViewPayload(int $lineId, string $subType): array
    {
        [$debit, $credit] = $subType === 'payment_voucher'
            ? self::paymentLines($lineId)
            : self::receiptLines($lineId);

        $debit->loadMissing(['account', 'createdBy', 'costCenter']);
        $credit->loadMissing(['account']);

        $localeAr = app()->getLocale() === 'ar';
        $costCenterLabel = $debit->costCenter
            ? ($debit->costCenter->account_center_number.' - '.($localeAr ? $debit->costCenter->name_ar : $debit->costCenter->name_en))
            : '—';
        $createdByLabel = $debit->createdBy?->name ?? '—';
        $debitAccountLabel = $debit->account->gl_code.' - '.($localeAr ? $debit->account->name_ar : $debit->account->name_en);
        $creditAccountLabel = $credit->account->gl_code.' - '.($localeAr ? $credit->account->name_ar : $credit->account->name_en);

        $isReceipt = $subType === 'receipt_voucher';
        $debitHint = $isReceipt
            ? __('accounting::lang.voucher_receipt_debit_hint')
            : __('accounting::lang.voucher_payment_debit_hint');
        $creditHint = $isReceipt
            ? __('accounting::lang.voucher_receipt_credit_hint')
            : __('accounting::lang.voucher_payment_credit_hint');

        $amountStr = number_format((float) $debit->amount, 2, '.', '');
        $parts = explode('.', $amountStr, 2);
        $amountRiyals = $parts[0];
        $amountHalalas = $parts[1] ?? '00';

        $intPart = (int) floor((float) $debit->amount);
        $amountWordsAr = self::spelloutInteger($intPart, 'ar_SA');
        $amountWordsEn = self::spelloutInteger($intPart, 'en_US');
        if ($amountWordsAr === '') {
            $amountWordsAr = $amountRiyals.' '.__('accounting::lang.voucher_tpl_riyal_label');
        }
        if ($amountWordsEn === '') {
            $amountWordsEn = $amountRiyals.' '.__('accounting::lang.voucher_tpl_riyal_label');
        }
        $amountWords = $localeAr ? $amountWordsAr : $amountWordsEn;

        $companyName = (string) config('app.name');
        $companyNameAr = $companyName;
        $companyTaglineAr = '';
        $companyLogoUrl = '';
        $companyTaxNumber = '';
        $companyTaxName = '';

        if (function_exists('get_company_id') && get_company_id()) {
            $row = DB::connection('mysql')->table('companies')->find(get_company_id());
            if ($row) {
                $companyName = (string) ($row->name ?? $companyName);
                $companyNameAr = (string) ($row->name_ar ?? $row->name ?? $companyName);
                $companyTaglineAr = (string) ($row->commercial_name_ar ?? $row->tagline_ar ?? '');
                $companyTaxNumber = trim((string) ($row->tax_number ?? ''));
                $companyTaxName = trim((string) ($row->tax_name ?? ''));
                foreach (['logo', 'logo_path', 'image', 'company_logo'] as $col) {
                    if (! empty($row->{$col})) {
                        $path = (string) $row->{$col};
                        $companyLogoUrl = function_exists('central_public_storage_url_for_path')
                            ? central_public_storage_url_for_path($path)
                            : $path;
                        break;
                    }
                }
            }
        }

        $opDate = $debit->operation_date ? (string) $debit->operation_date : null;
        $hijriDateLatin = self::formatHijriDate($opDate, true);
        $hijriDateArabic = self::formatHijriDate($opDate, false);

        // Receipt: Dr cash/bank, Cr payer. Payment: Dr payee, Cr bank/cash.
        if ($isReceipt) {
            $counterpartValue = $creditAccountLabel;
            $bankCashValue = $debitAccountLabel;
        } else {
            $counterpartValue = $debitAccountLabel;
            $bankCashValue = $creditAccountLabel;
        }

        return [
            'voucherSubType' => $subType,
            'voucherIsReceipt' => $isReceipt,
            'voucherNo' => str_pad((string) $debit->id, 4, '0', STR_PAD_LEFT),
            'pageTitle' => $isReceipt
                ? __('menuItemLang.receipt_vouchers')
                : __('menuItemLang.payment_vouchers'),
            'date' => $debit->operation_date,
            'amount' => $debit->amount,
            'note' => $debit->note,
            'debitAccountLabel' => $debitAccountLabel,
            'creditAccountLabel' => $creditAccountLabel,
            'debitHint' => $debitHint,
            'creditHint' => $creditHint,
            'costCenterLabel' => $costCenterLabel,
            'createdByLabel' => $createdByLabel,
            'amountRiyals' => $amountRiyals,
            'amountHalalas' => $amountHalalas,
            'amountFormatted' => number_format((float) $debit->amount, 2),
            'amountWords' => $amountWords,
            'amountWordsAr' => $amountWordsAr,
            'amountWordsEn' => $amountWordsEn,
            'gregorianDateFormatted' => Carbon::parse($debit->operation_date)->format('d/m/Y'),
            'hijriDateLatin' => $hijriDateLatin,
            'hijriDateArabic' => $hijriDateArabic,
            'companyName' => $companyName,
            'companyNameAr' => $companyNameAr,
            'companyTaglineAr' => $companyTaglineAr,
            'companyLogoUrl' => $companyLogoUrl,
            'companyTaxNumber' => $companyTaxNumber,
            'companyTaxName' => $companyTaxName,
            'voucherLocaleAr' => $localeAr,
            'counterpartValue' => $counterpartValue,
            'bankCashValue' => $bankCashValue,
        ];
    }

    private static function spelloutInteger(int $intPart, string $locale): string
    {
        if (! class_exists(\NumberFormatter::class) || $intPart < 0) {
            return '';
        }
        try {
            $fmt = new \NumberFormatter($locale, \NumberFormatter::SPELLOUT);

            return (string) $fmt->format($intPart);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Umm al-Qura (Saudi) Hijri date; empty string if Intl unavailable or on failure.
     */
    private static function formatHijriDate(?string $operationDate, bool $latinDigits): string
    {
        if ($operationDate === null || $operationDate === '') {
            return '';
        }
        if (! extension_loaded('intl')) {
            return '';
        }
        try {
            $locale = $latinDigits
                ? 'en_GB@calendar=islamic-umalqura'
                : 'ar_SA@calendar=islamic-umalqura';
            $d = Carbon::parse($operationDate)->toDateTime();
            $f = new \IntlDateFormatter(
                $locale,
                \IntlDateFormatter::NONE,
                \IntlDateFormatter::NONE,
                'Asia/Riyadh',
                \IntlDateFormatter::TRADITIONAL,
                'd/MM/y'
            );
            if ($f === false) {
                return '';
            }
            $out = $f->format($d);

            if ($out === false || $out === '') {
                return '';
            }

            return $latinDigits ? $out.' AH' : $out;
        } catch (\Throwable) {
            return '';
        }
    }
}
