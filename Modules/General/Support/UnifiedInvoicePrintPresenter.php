<?php

namespace Modules\General\Support;

use Carbon\Carbon;
use Modules\Accounting\Support\LedgerStatementPresenter;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionSellLine;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use NumberFormatter;
use Throwable;

/**
 * Builds ZATCA-like print/PDF data for sell, purchases, and their returns.
 * Does not touch the dedicated ZATCA Phase-2 document template.
 */
final class UnifiedInvoicePrintPresenter
{
    /** @var list<string> */
    public const SUPPORTED_TYPES = ['sell', 'purchases', 'sell-return', 'purchases-return'];

    public static function supports(?string $type): bool
    {
        return in_array((string) $type, self::SUPPORTED_TYPES, true);
    }

    /**
     * @return array<string, mixed>
     */
    public static function build(Transaction $transaction, object $company, string $qrCodeSvg, bool $forPdf = false): array
    {
        $transaction->loadMissing([
            'client.billingAddress.country_',
            'sell_lines.product',
            'purchases_lines.product',
            'parentSell',
            'payment',
            'zatcaInvoiceSync',
        ]);

        $type = (string) $transaction->type;
        $isPurchaseSide = in_array($type, ['purchases', 'purchases-return'], true);
        $isReturn = in_array($type, ['sell-return', 'purchases-return'], true);
        $localeAr = self::localeIsArabic();

        $companyParty = self::companyParty($company, $localeAr);
        $contactParty = self::contactParty($transaction, $localeAr);

        // Sales / sales-return: our company sells. Purchases / purchase-return: supplier sells.
        if ($isPurchaseSide) {
            $seller = $contactParty;
            $buyer = $companyParty;
            $sellerRoleAr = 'المورد';
            $sellerRoleEn = 'Supplier';
            $buyerRoleAr = 'المشتري';
            $buyerRoleEn = 'Buyer';
        } else {
            $seller = $companyParty;
            $buyer = $contactParty;
            $sellerRoleAr = 'البائع';
            $sellerRoleEn = 'Seller';
            $buyerRoleAr = 'العميل';
            $buyerRoleEn = 'Customer';
        }

        [$docTitleAr, $docTitleEn] = self::documentTitles($type);
        $lineRows = self::mapLines($transaction, $localeAr);
        $totalLineDiscount = round(array_sum(array_column($lineRows, 'discount')), 2);
        $invoiceDiscount = self::resolveInvoiceDiscount($transaction);

        $subtotalExVat = round((float) ($transaction->total_before_tax ?: 0), 2);
        $vatTotal = round((float) ($transaction->tax_amount ?: 0), 2);
        $grandTotal = round((float) ($transaction->final_total ?: 0), 2);
        $paidAmount = round((float) $transaction->payment->sum('amount'), 2);
        $dueAmount = max(0, round($grandTotal - $paidAmount, 2));

        $paymentStatus = strtolower((string) ($transaction->payment_status ?: 'due'));
        [$paymentStatusLabel, $paymentStatusLabelEn] = match ($paymentStatus) {
            'paid' => [__('general::lang.paid'), 'Paid'],
            'partial' => [__('general::lang.partial'), 'Partially paid'],
            default => [__('general::lang.due'), 'Due'],
        };

        $primaryColor = (string) config('zatca.pdf_primary_color', '#e9b71f');
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $primaryColor)) {
            $primaryColor = '#e9b71f';
        }
        $pr = hexdec(substr($primaryColor, 1, 2));
        $pg = hexdec(substr($primaryColor, 3, 2));
        $pb = hexdec(substr($primaryColor, 5, 2));
        $blend = static function (int $c, float $alpha): int {
            return (int) round(255 + ($c - 255) * $alpha);
        };
        $primarySoft = sprintf('#%02X%02X%02X', $blend($pr, 0.10), $blend($pg, 0.10), $blend($pb, 0.10));

        $sync = $transaction->zatcaInvoiceSync;
        $hasZatcaQr = $sync
            && $sync->status === ZatcaInvoiceSync::STATUS_SYNCED
            && filled($sync->qr_tlv);

        $parentRef = optional($transaction->parentSell)->ref_no;
        if (! $parentRef && $transaction->parent_id) {
            $parentRef = optional(Transaction::query()->find($transaction->parent_id))->ref_no;
        }

        return [
            'transaction' => $transaction,
            'company' => $company,
            'qrCode' => $qrCodeSvg,
            'forPdf' => $forPdf,
            'autoPrint' => ! $forPdf,
            'isPurchaseSide' => $isPurchaseSide,
            'isReturn' => $isReturn,
            'docTitleAr' => $docTitleAr,
            'docTitleEn' => $docTitleEn,
            'docBadgeAr' => self::documentBadgeAr($type),
            'docBadgeEn' => self::documentBadgeEn($type),
            'seller' => $seller,
            'buyer' => $buyer,
            'sellerRoleAr' => $sellerRoleAr,
            'sellerRoleEn' => $sellerRoleEn,
            'buyerRoleAr' => $buyerRoleAr,
            'buyerRoleEn' => $buyerRoleEn,
            'localeAr' => $localeAr,
            'lineRows' => $lineRows,
            'totalLineDiscount' => $totalLineDiscount,
            'invoiceDiscount' => $invoiceDiscount,
            'subtotalExVat' => $subtotalExVat,
            'vatTotal' => $vatTotal,
            'grandTotal' => $grandTotal,
            'paidAmount' => $paidAmount,
            'dueAmount' => $dueAmount,
            'amountWordsAr' => self::amountInWords($grandTotal, 'ar'),
            'amountWordsEn' => self::amountInWords($grandTotal, 'en'),
            'paymentStatusLabel' => $paymentStatusLabel,
            'paymentStatusLabelEn' => $paymentStatusLabelEn,
            'issueDate' => Carbon::parse($transaction->transaction_date)->format('Y-m-d'),
            'dueDate' => $transaction->due_date
                ? Carbon::parse($transaction->due_date)->format('Y-m-d')
                : '',
            'parentRef' => $parentRef,
            'logoSrc' => LedgerStatementPresenter::companyLogoSrc($company, $forPdf),
            'primaryColor' => $primaryColor,
            'primarySoft' => $primarySoft,
            'hasZatcaQr' => $hasZatcaQr,
            'afterPrintUrl' => self::afterPrintUrl($type),
        ];
    }

    /** @return array{0: string, 1: string} */
    private static function documentTitles(string $type): array
    {
        return match ($type) {
            'sell-return' => [
                __('general::lang.unified_doc_sell_return'),
                'Sales Return / Credit Note',
            ],
            'purchases' => [
                __('general::lang.unified_doc_purchases'),
                'Purchase Tax Invoice',
            ],
            'purchases-return' => [
                __('general::lang.unified_doc_purchases_return'),
                'Purchase Return',
            ],
            default => [
                __('general::lang.unified_doc_sell'),
                'Tax Invoice',
            ],
        };
    }

    private static function documentBadgeAr(string $type): string
    {
        return match ($type) {
            'sell' => 'مبيعات',
            'sell-return' => 'مردود مبيعات',
            'purchases' => 'مشتريات',
            'purchases-return' => 'مردود مشتريات',
            default => 'فاتورة',
        };
    }

    private static function documentBadgeEn(string $type): string
    {
        return match ($type) {
            'sell' => 'Sales',
            'sell-return' => 'Sales Return',
            'purchases' => 'Purchases',
            'purchases-return' => 'Purchase Return',
            default => 'Invoice',
        };
    }

    private static function afterPrintUrl(string $type): ?string
    {
        return match ($type) {
            'sell' => url('invoices'),
            'purchases' => url('purchase-invoices'),
            'sell-return' => url('sell-return'),
            'purchases-return' => url('purchases-return'),
            default => null,
        };
    }

    private static function localeIsArabic(): bool
    {
        return str_starts_with(strtolower((string) app()->getLocale()), 'ar');
    }

    /**
     * @return array{name: string, address: string, address_lines: list<string>, vat: string, cr: string, mobile: string}
     */
    private static function companyParty(object $company, bool $localeAr): array
    {
        $name = LedgerStatementPresenter::companyDisplayName($company, $localeAr);
        if ($name === '') {
            $name = trim((string) ($company->name ?? $company->name_ar ?? '')) ?: '—';
        }

        $addressLines = self::companyAddressLines($company, $localeAr);

        return [
            'name' => $name,
            'address' => implode($localeAr ? '، ' : ', ', $addressLines),
            'address_lines' => $addressLines,
            'vat' => preg_replace('/\D+/', '', (string) ($company->tax_number ?? '')) ?: '',
            'cr' => trim((string) ($company->commercial_register ?? $company->commercial_registration_number ?? '')),
            'mobile' => trim((string) ($company->mobile ?? $company->phone ?? '')),
        ];
    }

    /**
     * @return array{name: string, address: string, address_lines: list<string>, vat: string, cr: string, mobile: string}
     */
    private static function contactParty(Transaction $transaction, bool $localeAr): array
    {
        $empty = [
            'name' => '—',
            'address' => '',
            'address_lines' => [],
            'vat' => '',
            'cr' => '',
            'mobile' => '',
        ];

        $client = $transaction->client;
        if (! $client) {
            return $empty;
        }

        $addressLines = self::contactAddressLines($client->billingAddress, $localeAr);

        return [
            'name' => trim((string) ($client->name ?? '')) ?: '—',
            'address' => implode($localeAr ? '، ' : ', ', $addressLines),
            'address_lines' => $addressLines,
            'vat' => preg_replace('/\D+/', '', (string) ($client->tax_number ?? '')) ?: '',
            'cr' => trim((string) ($client->commercial_register ?? '')),
            'mobile' => trim((string) ($client->mobile_number ?? '')),
        ];
    }

    /** @return list<string> */
    private static function companyAddressLines(object $company, bool $localeAr): array
    {
        $lines = [];

        $national = trim((string) ($company->national_address ?? ''));
        if ($national !== '') {
            $lines[] = $national;
        }

        $street = trim((string) ($company->street_name ?? $company->address ?? ''));
        $building = trim((string) ($company->building_number ?? ''));
        $district = trim((string) ($company->district ?? ''));
        $state = trim((string) ($company->state ?? ''));
        $city = trim((string) ($company->city ?? ''));
        $postal = trim((string) ($company->postal_code ?? $company->zip_code ?? $company->zipcode ?? ''));
        $country = self::resolveCountryName(
            $company->country_id ?? $company->country ?? null,
            $localeAr,
            trim((string) ($company->country_name ?? $company->country ?? ''))
        );

        if ($street !== '') {
            $lines[] = $localeAr ? 'الشارع: '.$street : 'Street: '.$street;
        }
        if ($building !== '') {
            $lines[] = $localeAr ? 'رقم المبنى: '.$building : 'Building No.: '.$building;
        }
        if ($district !== '') {
            $lines[] = $localeAr ? 'الحي: '.$district : 'District: '.$district;
        } elseif ($state !== '' && $state !== $city) {
            $lines[] = $localeAr ? 'المنطقة: '.$state : 'State: '.$state;
        }
        if ($city !== '') {
            $lines[] = $localeAr ? 'المدينة: '.$city : 'City: '.$city;
        }
        if ($postal !== '') {
            $lines[] = $localeAr ? 'الرمز البريدي: '.$postal : 'Postal Code: '.$postal;
        }
        if ($country !== '') {
            $lines[] = $localeAr ? 'الدولة: '.$country : 'Country: '.$country;
        }

        if ($lines === []) {
            $lines = LedgerStatementPresenter::companyAddressLines($company, $localeAr);
        }

        return array_values(array_unique(array_filter($lines, static fn ($line) => trim((string) $line) !== '')));
    }

    /** @return list<string> */
    private static function contactAddressLines(?object $billing, bool $localeAr): array
    {
        if (! $billing) {
            return [];
        }

        $lines = [];
        $street = trim((string) ($billing->street_name ?? ''));
        $building = trim((string) ($billing->building_number ?? ''));
        $district = trim((string) ($billing->district ?? $billing->state ?? ''));
        $city = trim((string) ($billing->city ?? ''));
        $postal = trim((string) ($billing->postal_code ?? ''));
        $country = '';
        if (method_exists($billing, 'countryLabel')) {
            try {
                $country = self::countryLabelForLocale($billing, $localeAr);
            } catch (Throwable) {
                $country = '';
            }
        }
        if ($country === '') {
            $country = self::resolveCountryName($billing->country ?? null, $localeAr, '');
        }

        if ($street !== '') {
            $lines[] = $localeAr ? 'الشارع: '.$street : 'Street: '.$street;
        }
        if ($building !== '') {
            $lines[] = $localeAr ? 'رقم المبنى: '.$building : 'Building No.: '.$building;
        }
        if ($district !== '') {
            $lines[] = $localeAr ? 'الحي: '.$district : 'District: '.$district;
        }
        if ($city !== '') {
            $lines[] = $localeAr ? 'المدينة: '.$city : 'City: '.$city;
        }
        if ($postal !== '') {
            $lines[] = $localeAr ? 'الرمز البريدي: '.$postal : 'Postal Code: '.$postal;
        }
        if ($country !== '') {
            $lines[] = $localeAr ? 'الدولة: '.$country : 'Country: '.$country;
        }

        return $lines;
    }

    private static function countryLabelForLocale(object $billing, bool $localeAr): string
    {
        $country = $billing->country_ ?? null;
        if (! $country) {
            return '';
        }

        $ar = trim((string) ($country->name_ar ?? ''));
        $en = trim((string) ($country->name_en ?? ''));

        if ($localeAr) {
            return $ar !== '' ? $ar : $en;
        }

        return $en !== '' ? $en : $ar;
    }

    private static function resolveCountryName(mixed $countryIdOrCode, bool $localeAr, string $fallback = ''): string
    {
        $fallback = trim($fallback);
        if (is_object($countryIdOrCode)) {
            $ar = trim((string) ($countryIdOrCode->name_ar ?? ''));
            $en = trim((string) ($countryIdOrCode->name_en ?? ''));
            if ($localeAr) {
                return $ar !== '' ? $ar : ($en !== '' ? $en : $fallback);
            }

            return $en !== '' ? $en : ($ar !== '' ? $ar : $fallback);
        }

        if (is_numeric($countryIdOrCode) && (int) $countryIdOrCode > 0) {
            try {
                $row = \Modules\General\Models\Country::query()->find((int) $countryIdOrCode);
                if ($row) {
                    return self::resolveCountryName($row, $localeAr, $fallback);
                }
            } catch (Throwable) {
                // ignore and fall through
            }
        }

        $raw = trim((string) ($countryIdOrCode ?? ''));
        if ($raw !== '' && ! is_numeric($raw)) {
            return $raw;
        }

        return $fallback;
    }

    /**
     * @return list<array{seq:int,name:string,qty:float,unit:string,unit_price:float,discount:float,tax_percent:string,tax:float,total:float}>
     */
    private static function mapLines(Transaction $transaction, bool $localeAr): array
    {
        $type = (string) $transaction->type;
        // Mirror ERP storage: sell-return uses purchase lines; purchases-return uses sell lines.
        $lines = match ($type) {
            'purchases', 'sell-return' => $transaction->purchases_lines,
            default => $transaction->sell_lines,
        };

        $rows = [];
        $seq = 1;
        foreach ($lines as $line) {
            if ((string) ($line->parent_id ?? '') !== '' && (int) $line->parent_id > 0) {
                continue;
            }
            if (isset($line->is_show) && (string) $line->is_show === '0') {
                continue;
            }
            if (! $line->product && empty($line->product_id)) {
                continue;
            }
            $rows[] = self::mapLine($line, $seq, $localeAr);
            $seq++;
        }

        return $rows;
    }

    /**
     * @return array{seq:int,name:string,qty:float,unit:string,unit_price:float,discount:float,tax_percent:string,tax:float,total:float}
     */
    private static function mapLine(TransactionSellLine|TransactionePurchasesLine $line, int $seq, bool $localeAr): array
    {
        $qty = (float) ($line->qyt ?: 1);
        if ($qty <= 0) {
            $qty = 1.0;
        }

        $discount = self::resolveLineDiscount($line, $qty);
        $lineNet = round((float) ($line->total_before_vat ?: 0), 2);
        if ($lineNet <= 0) {
            $unit = (float) ($line->unit_price ?: 0);
            $lineNet = round(($unit * $qty) - $discount, 2);
        }
        if ($lineNet < 0) {
            $lineNet = 0.0;
        }

        $grossBeforeDiscount = round($lineNet + $discount, 2);
        $unitPrice = $qty > 0
            ? round($grossBeforeDiscount / $qty, 2)
            : round((float) ($line->unit_price_before_discount ?: $line->unit_price ?: 0), 2);
        $tax = round((float) ($line->tax_value ?: 0), 2);
        $total = round((float) ($line->unit_price_inc_tax ?: ($lineNet + $tax)), 2);

        $taxPercent = '0';
        if (isset($line->tax_rate_percent) && $line->tax_rate_percent !== null && $line->tax_rate_percent !== '') {
            $taxPercent = (string) preg_replace('/[^\d.]+/', '', (string) $line->tax_rate_percent) ?: '0';
        } elseif ($lineNet > 0 && $tax > 0) {
            $taxPercent = (string) round(($tax / $lineNet) * 100, 2);
        }

        $product = $line->product;
        $nameAr = trim((string) ($product?->name_ar ?? ''));
        $nameEn = trim((string) ($product?->name_en ?? ''));
        if ($localeAr) {
            $name = $nameAr !== '' ? $nameAr : ($nameEn !== '' ? $nameEn : ('#'.$line->product_id));
        } else {
            $name = $nameEn !== '' ? $nameEn : ($nameAr !== '' ? $nameAr : ('#'.$line->product_id));
        }

        $unit = '';
        try {
            $unit = trim((string) ($line->unitTransfer?->unit1 ?? ''));
        } catch (Throwable) {
            $unit = '';
        }

        return [
            'seq' => $seq,
            'name' => $name,
            'qty' => $qty,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'tax_percent' => $taxPercent,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    private static function resolveLineDiscount(TransactionSellLine|TransactionePurchasesLine $line, float $qty): float
    {
        $amount = (float) ($line->discount_amount ?: 0);
        if ($amount <= 0) {
            return 0.0;
        }

        $type = strtolower((string) ($line->discount_type ?: 'fixed'));
        if (in_array($type, ['percentage', 'percent', '%'], true)) {
            $base = (float) ($line->unit_price_before_discount ?: $line->unit_price ?: 0) * $qty;

            return round($base * ($amount / 100), 2);
        }

        return round($amount, 2);
    }

    private static function resolveInvoiceDiscount(Transaction $transaction): float
    {
        $amount = (float) ($transaction->discount_amount ?: 0);
        if ($amount <= 0) {
            return 0.0;
        }

        $type = strtolower((string) ($transaction->discount_type ?: 'fixed'));
        if (in_array($type, ['percentage', 'percent', '%'], true)) {
            $base = (float) ($transaction->total_before_tax ?: 0);

            return round($base * ($amount / 100), 2);
        }

        return round($amount, 2);
    }

    private static function amountInWords(float $amount, string $locale): string
    {
        $amount = round(abs($amount), 2);
        $major = (int) floor($amount);
        $minor = (int) round(($amount - $major) * 100);

        try {
            $formatter = new NumberFormatter($locale === 'ar' ? 'ar' : 'en', NumberFormatter::SPELLOUT);
            $majorWords = $formatter->format($major);
            $minorWords = $minor > 0 ? $formatter->format($minor) : null;
        } catch (Throwable) {
            return number_format($amount, 2).' SAR';
        }

        if ($locale === 'ar') {
            $text = $majorWords.' ريالاً';
            if ($minorWords) {
                $text .= ' و '.$minorWords.' هللة';
            }

            return $text.' فقط لا غير';
        }

        $text = ucfirst((string) $majorWords).' Saudi Riyal';
        if ($minorWords) {
            $text .= ' and '.$minorWords.' Halala';
        }

        return $text.' only';
    }
}
