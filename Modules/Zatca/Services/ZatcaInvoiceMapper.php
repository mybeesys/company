<?php

namespace Modules\Zatca\Services;

use Bl\FatooraZatca\Classes\InvoiceType;
use Bl\FatooraZatca\Classes\PaymentType;
use Bl\FatooraZatca\Classes\TaxCategoryCode;
use Bl\FatooraZatca\Objects\ChargeItem;
use Bl\FatooraZatca\Objects\Client;
use Bl\FatooraZatca\Objects\DiscountItem;
use Bl\FatooraZatca\Objects\Invoice;
use Bl\FatooraZatca\Objects\InvoiceItem;
use Bl\FatooraZatca\Objects\Seller;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionSellLine;
use Modules\General\Support\TransactionLineTaxRate;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use Modules\Zatca\Models\ZatcaSetting;
use RuntimeException;

/**
 * Maps ERP sell documents to Bl\FatooraZatca value objects.
 */
class ZatcaInvoiceMapper
{
    public function toSeller(ZatcaSetting $setting): Seller
    {
        $credentials = $setting->generated_credentials ?? [];
        $privateKey = (string) ($credentials['private_key'] ?? '');
        $certificate = (string) ($credentials['cert_production'] ?? '');
        $secret = (string) ($credentials['secret_production'] ?? '');

        if ($privateKey === '' || $certificate === '' || $secret === '') {
            throw new RuntimeException(__('zatca::lang.credentials_incomplete'));
        }

        $street = trim((string) ($setting->street_name ?: 'Street'));
        $building = $this->normalizeBuildingNumber((string) $setting->building_number);
        $plot = trim((string) ($setting->plot_identification ?: $building));
        $district = trim((string) ($setting->district ?: $setting->city ?: 'District'));
        $city = trim((string) ($setting->city ?: 'Riyadh'));
        $postal = $this->normalizePostalCode((string) $setting->postal_code);
        $crn = trim((string) $setting->commercial_registration_number);
        $vat = trim((string) $setting->vat_number);
        $name = trim((string) ($setting->organization_name ?: $setting->seller_name));

        foreach ([
            'commercial_registration_number' => $crn,
            'vat_number' => $vat,
            'organization_name' => $name,
            'city' => $city,
            'district' => $district,
            'building_number' => $building,
            'postal_code' => $postal,
            'street_name' => $street,
        ] as $field => $value) {
            if ($value === '') {
                throw new RuntimeException(__('zatca::lang.seller_field_missing', ['field' => $field]));
            }
        }

        if (! preg_match('/^\d{4}$/', $building)) {
            throw new RuntimeException(__('zatca::lang.zatca_err_building_number'));
        }
        if (! preg_match('/^\d{5}$/', $postal)) {
            throw new RuntimeException(__('zatca::lang.zatca_err_postal_code'));
        }

        return new Seller(
            $crn,
            $street,
            $building,
            $plot,
            $district,
            $city,
            $postal,
            $vat,
            $name,
            $privateKey,
            $certificate,
            $secret,
            (string) ($setting->country_code ?: 'SA')
        );
    }

    public function toClient(Transaction $transaction): Client
    {
        $contact = $transaction->client;
        if (! $contact) {
            throw new RuntimeException(__('zatca::lang.b2b_client_required'));
        }

        $billing = $contact->billingAddress;
        $taxNumber = preg_replace('/\D+/', '', (string) ($contact->tax_number ?? '')) ?: null;

        if (! $taxNumber || ! preg_match('/^3\d{13}3$/', $taxNumber)) {
            throw new RuntimeException(__('zatca::lang.b2b_client_vat_invalid', [
                'name' => $contact->name,
            ]));
        }

        $street = trim((string) ($billing?->street_name ?: 'Street'));
        $building = $this->normalizeBuildingNumber((string) ($billing?->building_number ?: '0000'));
        $plot = trim((string) ($billing?->building_number ?: $building));
        $district = trim((string) ($billing?->state ?: $billing?->city ?: 'District'));
        $city = trim((string) ($billing?->city ?: 'Riyadh'));
        $postal = $this->normalizePostalCode((string) ($billing?->postal_code ?: '00000'));

        return new Client(
            (string) $contact->name,
            $taxNumber,
            $postal,
            $street,
            $building,
            $plot,
            $district,
            $city,
            'SA',
            null
        );
    }

    /**
     * @return array{invoice: Invoice, invoice_items: array<int, InvoiceItem>}
     */
    public function toInvoice(
        Transaction $transaction,
        int $invoiceCounter,
        string $invoiceUuid,
        ?string $previousHash
    ): array {
        $lines = $transaction->sell_lines;
        if ($lines->isEmpty()) {
            throw new RuntimeException(__('zatca::lang.invoice_has_no_lines', [
                'ref' => $transaction->ref_no,
            ]));
        }

        $invoiceItems = [];
        $lineIndex = 1;
        $sumLinePrice = 0.0;
        $sumLineNet = 0.0;

        foreach ($lines as $line) {
            if ((string) ($line->parent_id ?? '') !== '' && (int) $line->parent_id > 0) {
                continue;
            }
            if (isset($line->is_show) && (string) $line->is_show === '0') {
                continue;
            }

            $item = $this->mapLine($line, $lineIndex);
            $invoiceItems[] = $item;
            $sumLinePrice += (float) $item->price;
            $sumLineNet += ((float) $item->price - (float) $item->discount);
            $lineIndex++;
        }

        if ($invoiceItems === []) {
            throw new RuntimeException(__('zatca::lang.invoice_has_no_lines', [
                'ref' => $transaction->ref_no,
            ]));
        }

        $docVat = $this->dominantStandardVat($invoiceItems);

        $discountItems = [];
        $invoiceDiscount = $this->resolveInvoiceDiscount($transaction);
        if ($invoiceDiscount > 0) {
            // Cap discount so tax-exclusive amount never goes negative.
            $invoiceDiscount = min($invoiceDiscount, round($sumLineNet, 2));
            $discountItems[] = new DiscountItem(
                __('zatca::lang.invoice_discount_reason'),
                $invoiceDiscount,
                $docVat['percent'],
                $docVat['category']
            );
        }

        $chargeItems = [];
        $serviceFee = round((float) ($transaction->service_fee_amount ?: 0), 2);
        $serviceFeeTaxStored = round((float) ($transaction->service_fee_tax ?: 0), 2);
        $serviceFeeTax = 0.0;
        if ($serviceFee > 0) {
            if ($serviceFeeTaxStored > 0) {
                $rawFeeRate = round(($serviceFeeTaxStored / $serviceFee) * 100, 2);
                $feeVat = $this->normalizeVatProfile($rawFeeRate);
                if ($feeVat['category'] !== TaxCategoryCode::STANDARD_RATE) {
                    $feeVat = ['percent' => 15.0, 'category' => TaxCategoryCode::STANDARD_RATE];
                }
            } elseif ($docVat['category'] === TaxCategoryCode::STANDARD_RATE) {
                $feeVat = $docVat;
            } else {
                // Keep charge in the same VAT group as lines so BG-23 stays consistent.
                $feeVat = $docVat;
            }

            $serviceFeeTax = $feeVat['category'] === TaxCategoryCode::STANDARD_RATE
                ? round($serviceFee * ($feeVat['percent'] / 100), 2)
                : 0.0;

            $chargeItems[] = new ChargeItem(
                'SAA',
                __('zatca::lang.service_fee_reason'),
                $serviceFee,
                $feeVat['percent'],
                $feeVat['category']
            );
        }

        /*
         | BR-CO-13:
         | BT-109 (tax exclusive) = Σ line nets (BT-131) - doc allowances (BT-107) + doc charges (BT-108)
         | We derive totals from lines so XML amounts stay consistent.
         */
        $taxExclusive = round($sumLineNet - $invoiceDiscount + $serviceFee, 2);
        $tax = $this->computeDocumentTax($invoiceItems, $invoiceDiscount, $serviceFeeTax, $docVat);
        $total = round($taxExclusive + $tax, 2);
        $price = round($sumLinePrice, 2);

        $issuedAt = $this->resolveIssueDateTime($transaction);

        $invoice = new Invoice(
            $invoiceCounter,
            (string) ($transaction->ref_no ?: ('INV-'.$transaction->id)),
            $invoiceUuid,
            $issuedAt['date'],
            $issuedAt['time'],
            InvoiceType::TAX_INVOICE,
            $this->mapPaymentType($transaction),
            $price,
            $discountItems,
            $tax,
            $total,
            $invoiceItems,
            $previousHash,
            null,
            $transaction->description ?: null,
            null,
            'SAR',
            15,
            $issuedAt['date'],
            0,
            0,
            $chargeItems
        );

        return [
            'invoice' => $invoice,
            'invoice_items' => $invoiceItems,
        ];
    }

    /**
     * Map sell-return → ZATCA Credit Note (381) with BillingReference to the original invoice.
     *
     * @return array{invoice: Invoice, invoice_items: array<int, InvoiceItem>, parent: Transaction, parent_sync: ZatcaInvoiceSync}
     */
    public function toCreditNote(
        Transaction $returnTxn,
        int $invoiceCounter,
        string $invoiceUuid,
        ?string $previousHash
    ): array {
        if ((string) $returnTxn->type !== 'sell-return') {
            throw new RuntimeException(__('zatca::lang.credit_note_type_invalid'));
        }

        $parent = Transaction::query()
            ->with(['client.billingAddress'])
            ->find($returnTxn->parent_id);

        if (! $parent || (string) $parent->type !== 'sell') {
            throw new RuntimeException(__('zatca::lang.credit_note_parent_missing', [
                'ref' => $returnTxn->ref_no,
            ]));
        }

        $parentSync = ZatcaInvoiceSync::query()
            ->where('transaction_id', $parent->id)
            ->where('status', ZatcaInvoiceSync::STATUS_SYNCED)
            ->first();

        if (! $parentSync) {
            throw new RuntimeException(__('zatca::lang.credit_note_parent_not_synced', [
                'ref' => $parent->ref_no,
            ]));
        }

        $lines = $returnTxn->purchases_lines;
        if ($lines->isEmpty()) {
            $lines = TransactionePurchasesLine::query()
                ->with('product')
                ->where('transaction_id', $returnTxn->id)
                ->get();
            $returnTxn->setRelation('purchases_lines', $lines);
        }

        if ($lines->isEmpty()) {
            throw new RuntimeException(__('zatca::lang.invoice_has_no_lines', [
                'ref' => $returnTxn->ref_no,
            ]));
        }

        $invoiceItems = [];
        $lineIndex = 1;
        $sumLinePrice = 0.0;
        $sumLineNet = 0.0;

        foreach ($lines as $line) {
            $item = $this->mapPurchaseLine($line, $lineIndex);
            $invoiceItems[] = $item;
            $sumLinePrice += (float) $item->price;
            $sumLineNet += ((float) $item->price - (float) $item->discount);
            $lineIndex++;
        }

        $docVat = $this->dominantStandardVat($invoiceItems);

        $discountItems = [];
        $invoiceDiscount = $this->resolveInvoiceDiscount($returnTxn);
        if ($invoiceDiscount > 0) {
            $invoiceDiscount = min($invoiceDiscount, round($sumLineNet, 2));
            $discountItems[] = new DiscountItem(
                __('zatca::lang.invoice_discount_reason'),
                $invoiceDiscount,
                $docVat['percent'],
                $docVat['category']
            );
        }

        $taxExclusive = round($sumLineNet - $invoiceDiscount, 2);
        $tax = $this->computeDocumentTax($invoiceItems, $invoiceDiscount, 0.0, $docVat);
        $total = round($taxExclusive + $tax, 2);
        $price = round($sumLinePrice, 2);
        $issuedAt = $this->resolveIssueDateTime($returnTxn);

        $reason = trim((string) ($returnTxn->description ?: $returnTxn->notice ?: ''));
        if ($reason === '') {
            $reason = __('zatca::lang.credit_note_default_reason');
        }

        $billingRef = (string) ($parent->ref_no ?: ('INV-'.$parent->id));

        $invoice = new Invoice(
            $invoiceCounter,
            (string) ($returnTxn->ref_no ?: ('CN-'.$returnTxn->id)),
            $invoiceUuid,
            $issuedAt['date'],
            $issuedAt['time'],
            InvoiceType::CREDIT_NOTE,
            $this->mapPaymentType($returnTxn),
            $price,
            $discountItems,
            $tax,
            $total,
            $invoiceItems,
            $previousHash,
            $billingRef,
            $reason,
            null,
            'SAR',
            $docVat['percent'] > 0 ? $docVat['percent'] : 15.0,
            $issuedAt['date'],
            0,
            0,
            []
        );

        return [
            'invoice' => $invoice,
            'invoice_items' => $invoiceItems,
            'parent' => $parent,
            'parent_sync' => $parentSync,
        ];
    }

    private function mapPurchaseLine(TransactionePurchasesLine $line, int $index): InvoiceItem
    {
        $qty = (float) ($line->qyt ?: 1);
        if ($qty <= 0) {
            $qty = 1;
        }

        $discount = $this->resolvePurchaseLineDiscount($line, $qty);
        $vat = $this->resolveVatProfileFromFields(
            $line->tax_id !== null ? (string) $line->tax_id : null,
            (float) ($line->total_before_vat ?: 0),
            (float) ($line->tax_value ?: 0)
        );

        $lineNet = round((float) ($line->total_before_vat ?: 0), 2);
        if ($lineNet <= 0) {
            $unit = (float) ($line->unit_price ?: 0);
            $lineNet = round(($unit * $qty) - $discount, 2);
        }
        if ($lineNet < 0) {
            $lineNet = 0.0;
        }

        $price = round($lineNet + $discount, 2);
        $calculatedTax = round($lineNet * ($vat['percent'] / 100), 2);
        $storedTax = round((float) ($line->tax_value ?: 0), 2);

        if ($vat['category'] !== TaxCategoryCode::STANDARD_RATE) {
            $tax = 0.0;
        } else {
            $tax = abs($storedTax - $calculatedTax) <= 0.05 ? $storedTax : $calculatedTax;
        }

        $total = round($lineNet + $tax, 2);
        $productName = $line->product?->name_ar
            ?: $line->product?->name_en
            ?: ('Item #'.$line->product_id);

        return new InvoiceItem(
            $index,
            (string) $productName,
            $qty,
            $price,
            $discount,
            $tax,
            $vat['percent'],
            $total,
            $discount > 0 ? __('zatca::lang.line_discount_reason') : null,
            $vat['category']
        );
    }

    private function resolvePurchaseLineDiscount(TransactionePurchasesLine $line, float $qty): float
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

    /**
     * @return array{percent: float, category: string}
     */
    private function resolveVatProfileFromFields(?string $taxId, float $excl, float $taxAmount): array
    {
        $raw = TransactionLineTaxRate::displayPercent($taxId);

        if ($raw === '--' || $raw === '') {
            if ($excl > 0 && $taxAmount > 0) {
                $rawPercent = round(($taxAmount / $excl) * 100, 2);
            } elseif ($taxAmount <= 0) {
                $rawPercent = 0.0;
            } else {
                $rawPercent = 15.0;
            }
        } else {
            $numeric = preg_replace('/[^\d.]+/', '', (string) $raw);
            $rawPercent = (float) ($numeric !== null && $numeric !== '' ? $numeric : 0);
        }

        if ($rawPercent > 20 && $excl > 0 && $taxAmount > 0) {
            $rawPercent = round(($taxAmount / $excl) * 100, 2);
        }
        if ($rawPercent > 0 && $rawPercent <= 1) {
            $rawPercent = round($rawPercent * 100, 2);
        }

        return $this->normalizeVatProfile($rawPercent);
    }

    private function mapLine(TransactionSellLine $line, int $index): InvoiceItem
    {
        $qty = (float) ($line->qyt ?: 1);
        if ($qty <= 0) {
            $qty = 1;
        }

        $discount = $this->resolveLineDiscount($line, $qty);
        $vat = $this->resolveLineVatProfile($line);

        // Line net excl. VAT after line discount (BT-131).
        $lineNet = round((float) ($line->total_before_vat ?: 0), 2);
        if ($lineNet <= 0) {
            $unit = (float) ($line->unit_price ?: 0);
            $lineNet = round(($unit * $qty) - $discount, 2);
        }
        if ($lineNet < 0) {
            $lineNet = 0.0;
        }

        // Package: netUnit = (price - discount) / qty  => price is excl-tax BEFORE discount.
        $price = round($lineNet + $discount, 2);

        $calculatedTax = round($lineNet * ($vat['percent'] / 100), 2);
        $storedTax = round((float) ($line->tax_value ?: 0), 2);

        // Zero-rated / exempt lines must not carry VAT amount.
        if ($vat['category'] !== TaxCategoryCode::STANDARD_RATE) {
            $tax = 0.0;
        } else {
            $tax = abs($storedTax - $calculatedTax) <= 0.05 ? $storedTax : $calculatedTax;
        }

        $total = round($lineNet + $tax, 2);

        $productName = $line->product?->name_ar
            ?: $line->product?->name_en
            ?: ('Item #'.$line->product_id);

        return new InvoiceItem(
            $index,
            (string) $productName,
            $qty,
            $price,
            $discount,
            $tax,
            $vat['percent'],
            $total,
            $discount > 0 ? __('zatca::lang.line_discount_reason') : null,
            $vat['category']
        );
    }

    /**
     * @return array{percent: float, category: string}
     */
    private function resolveLineVatProfile(TransactionSellLine $line): array
    {
        return $this->resolveVatProfileFromFields(
            $line->tax_id !== null ? (string) $line->tax_id : null,
            (float) ($line->total_before_vat ?: 0),
            (float) ($line->tax_value ?: 0)
        );
    }

    /**
     * ZATCA BR-KSA-84: for category S, rate must be 5 or 15.
     * Never emit category S with 0% (breaks BG-23 / BR-CO-18).
     *
     * @return array{percent: float, category: string}
     */
    public function normalizeVatProfile(float $rawPercent): array
    {
        if ($rawPercent <= 0.05) {
            return [
                'percent' => 0.0,
                'category' => TaxCategoryCode::ZERO_RATE,
            ];
        }

        if (abs($rawPercent - 5.0) <= 0.51) {
            return [
                'percent' => 5.0,
                'category' => TaxCategoryCode::STANDARD_RATE,
            ];
        }

        if (abs($rawPercent - 15.0) <= 1.0) {
            return [
                'percent' => 15.0,
                'category' => TaxCategoryCode::STANDARD_RATE,
            ];
        }

        // Unusual but taxable rate → snap to nearest allowed standard rate.
        if ($rawPercent > 0 && $rawPercent < 20) {
            $snapped = abs($rawPercent - 5.0) <= abs($rawPercent - 15.0) ? 5.0 : 15.0;

            return [
                'percent' => $snapped,
                'category' => TaxCategoryCode::STANDARD_RATE,
            ];
        }

        return [
            'percent' => 15.0,
            'category' => TaxCategoryCode::STANDARD_RATE,
        ];
    }

    /**
     * @param  array<int, InvoiceItem>  $invoiceItems
     * @return array{percent: float, category: string}
     */
    private function dominantStandardVat(array $invoiceItems): array
    {
        $counts = [];
        foreach ($invoiceItems as $item) {
            if ($item->tax_category_code !== TaxCategoryCode::STANDARD_RATE) {
                continue;
            }
            $key = (string) $item->tax_percent;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        if ($counts === []) {
            return [
                'percent' => 0.0,
                'category' => TaxCategoryCode::ZERO_RATE,
            ];
        }

        arsort($counts);
        $percent = (float) array_key_first($counts);

        return [
            'percent' => $percent,
            'category' => TaxCategoryCode::STANDARD_RATE,
        ];
    }

    /**
     * Mirror package tax-subtotal math so TOTAL_TAX_AMOUNT matches BG-23.
     *
     * @param  array<int, InvoiceItem>  $invoiceItems
     */
    private function computeDocumentTax(
        array $invoiceItems,
        float $invoiceDiscount,
        float $serviceFeeTax,
        array $docVat
    ): float {
        $sumLineTax = 0.0;
        $sumStandardNet = 0.0;

        foreach ($invoiceItems as $item) {
            $sumLineTax += (float) $item->tax;
            if ($item->tax_category_code === TaxCategoryCode::STANDARD_RATE) {
                $sumStandardNet += ((float) $item->price - (float) $item->discount);
            }
        }

        $tax = round($sumLineTax, 2);

        if ($invoiceDiscount > 0 && $sumStandardNet > 0 && $docVat['category'] === TaxCategoryCode::STANDARD_RATE) {
            // Package recalculates: (Σ standard nets - document allowance) * rate / 100
            $taxable = max(0, round($sumStandardNet - $invoiceDiscount, 2));
            $tax = round($taxable * ($docVat['percent'] / 100), 2);
        }

        return round($tax + $serviceFeeTax, 2);
    }

    private function resolveLineDiscount(TransactionSellLine $line, float $qty): float
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

    private function resolveInvoiceDiscount(Transaction $transaction): float
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

    private function mapPaymentType(Transaction $transaction): int
    {
        return match (strtolower((string) $transaction->payment_status)) {
            'paid' => PaymentType::CASH,
            'partial' => PaymentType::MULTIPLE,
            'due' => PaymentType::CREDIT,
            default => PaymentType::CASH,
        };
    }

    /**
     * @return array{date: string, time: string}
     */
    private function resolveIssueDateTime(Transaction $transaction): array
    {
        $date = Carbon::parse($transaction->transaction_date)->format('Y-m-d');
        $time = $transaction->created_at
            ? Carbon::parse($transaction->created_at)->format('H:i:s')
            : '12:00:00';

        return compact('date', 'time');
    }

    public function ensureUuid(?string $existing): string
    {
        if ($existing && Str::isUuid($existing)) {
            return $existing;
        }

        return (string) Str::uuid();
    }

    public function normalizeBuildingNumber(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?: '';

        if ($digits === '') {
            return '';
        }

        // ZATCA BR-KSA-37: exactly 4 digits.
        if (strlen($digits) > 4) {
            return substr($digits, -4);
        }

        return str_pad($digits, 4, '0', STR_PAD_LEFT);
    }

    public function normalizePostalCode(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?: '';

        if ($digits === '') {
            return '';
        }

        // ZATCA BR-KSA-66: exactly 5 digits.
        if (strlen($digits) > 5) {
            return substr($digits, 0, 5);
        }

        return str_pad($digits, 5, '0', STR_PAD_LEFT);
    }
}
