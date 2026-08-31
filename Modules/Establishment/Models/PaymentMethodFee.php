<?php

namespace Modules\Establishment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethodFee extends Model
{
    public const FEE_TYPE_AMOUNT = '0';

    public const FEE_TYPE_PERCENT = '1';

    public const APPLY_ITEM = '0';

    public const APPLY_ORDER = '1';

    public const CALC_BEFORE_TAX = '0';

    public const CALC_AFTER_TAX = '1';

    protected $table = 'est_payment_method_fees';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'amount' => 'float',
        'is_active' => 'boolean',
        'taxable' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(EstablishmentPaymentAccount::class, 'payment_method_id');
    }

    public function isPercent(): bool
    {
        return (string) $this->fee_type === self::FEE_TYPE_PERCENT;
    }

    public function isItemLevel(): bool
    {
        return (string) $this->application_type === self::APPLY_ITEM;
    }

    public function isCalculatedAfterTax(): bool
    {
        return (string) $this->calculation_method === self::CALC_AFTER_TAX;
    }

    public function appliesTo(): string
    {
        return $this->isItemLevel() ? 'item' : 'order';
    }

    public function calculatedOn(): string
    {
        return $this->isCalculatedAfterTax() ? 'after_tax' : 'before_tax';
    }

    public function displayName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return $locale === 'ar'
            ? (string) ($this->name_ar ?: $this->name_en)
            : (string) ($this->name_en ?: $this->name_ar);
    }

    public function feeTypeLabel(string $locale): string
    {
        $key = $this->isPercent()
            ? 'establishment::fields.payment_method_fee_type_percent'
            : 'establishment::fields.payment_method_fee_type_amount';

        return (string) trans($key, [], $locale);
    }

    public function applicationLabel(string $locale): string
    {
        $key = $this->isItemLevel()
            ? 'establishment::fields.service_fee_app_type_item'
            : 'establishment::fields.service_fee_app_type_order';

        return (string) trans($key, [], $locale);
    }

    public function calculationMethodLabel(string $locale): string
    {
        $key = $this->isCalculatedAfterTax()
            ? 'establishment::fields.service_fee_calc_method_taxable'
            : 'establishment::fields.service_fee_calc_method_total';

        return (string) trans($key, [], $locale);
    }

    /**
     * حساب قيمة الرسم بناء على سياق الفاتورة (بدون ضريبة الرسم).
     *
     * @param  float  $orderNet
     * @param  array<array{net: float, qty: float, gross?: float, vat?: float, tax_rate?: float}>  $lines
     */
    public function computeAmount(float $orderNet, array $lines = [], ?float $orderGross = null): float
    {
        $value = (float) $this->amount;

        if ($this->isItemLevel()) {
            $total = 0.0;
            foreach ($lines as $line) {
                $qty = max(0, (float) ($line['qty'] ?? 1));
                $net = max(0, (float) ($line['net'] ?? 0));
                $gross = max(0, (float) ($line['gross'] ?? $net));
                $base = $this->isCalculatedAfterTax() ? $gross : $net;

                if ($this->isPercent()) {
                    $total += round($base * ($value / 100), 4);
                } else {
                    $total += round($value * $qty, 4);
                }
            }

            return round($total, 2);
        }

        $base = $this->isCalculatedAfterTax()
            ? max(0, (float) ($orderGross ?? $orderNet))
            : max(0, $orderNet);

        if ($this->isPercent()) {
            return round($base * ($value / 100), 2);
        }

        return round($value, 2);
    }
}
