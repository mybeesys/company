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

    protected $table = 'est_payment_method_fees';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'amount' => 'float',
        'is_active' => 'boolean',
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

    public function displayName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return $locale === 'ar'
            ? (string) ($this->name_ar ?: $this->name_en)
            : (string) ($this->name_en ?: $this->name_ar);
    }

    /**
     * حساب قيمة الرسم بناء على سياق الفاتورة.
     *
     * @param  float  $orderNet     إجمالي الفاتورة قبل الخصم
     * @param  array<array{net: float, qty: float}>  $lines  بنود المنتجات
     */
    public function computeAmount(float $orderNet, array $lines = []): float
    {
        $value = (float) $this->amount;

        if ($this->isItemLevel()) {
            // رسم على كل منتج: مبلغ × كمية  أو  نسبة × سعر المنتج
            $total = 0.0;
            foreach ($lines as $line) {
                $qty = max(0, (float) ($line['qty'] ?? 1));
                $net = max(0, (float) ($line['net'] ?? 0));

                if ($this->isPercent()) {
                    $total += round($net * ($value / 100), 4);
                } else {
                    $total += round($value * $qty, 4);
                }
            }

            return round($total, 2);
        }

        // رسم على إجمالي الفاتورة
        if ($this->isPercent()) {
            return round($orderNet * ($value / 100), 2);
        }

        return round($value, 2);
    }
}
