<?php

namespace Modules\Establishment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Establishment\Models\Concerns\HasEstablishmentAssignments;

class EstablishmentServiceFee extends Model
{
    use HasEstablishmentAssignments;

    public const TYPE_AMOUNT = '0';

    public const TYPE_PERCENT = '1';

    public const APPLY_ITEM = '0';

    public const APPLY_ORDER = '1';

    public const CALC_BEFORE_TAX = '0';

    public const CALC_AFTER_TAX = '1';

    public const AUTO_DINING = '0';

    public const AUTO_GUEST = '1';

    public const AUTO_PAYMENT = '2';

    public const AUTO_TIME = '3';

    protected $table = 'est_establishment_service_fees';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function assignmentPivotTable(): string
    {
        return 'est_service_fee_establishment';
    }

    protected function assignmentForeignPivotKey(): string
    {
        return 'service_fee_id';
    }

    protected $casts = [
        'amount' => 'float',
        'taxable' => 'boolean',
        'is_active' => 'boolean',
        'dining_type_ids' => 'array',
        'guest_count' => 'integer',
        'sort_order' => 'integer',
        'from_date' => 'datetime',
        'to_date' => 'datetime',
    ];

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }

    public function cashierPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(EstablishmentPaymentAccount::class, 'cashier_payment_method_id');
    }

    public function displayName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return $locale === 'ar'
            ? (string) ($this->name_ar ?: $this->name_en)
            : (string) ($this->name_en ?: $this->name_ar);
    }

    public function isPercent(): bool
    {
        return (string) $this->service_fee_type === self::TYPE_PERCENT;
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

    public function autoApplyKey(): string
    {
        return match ((string) ($this->auto_apply_type ?? '')) {
            self::AUTO_DINING => 'dining',
            self::AUTO_GUEST => 'guest_count',
            self::AUTO_PAYMENT => 'payment_method',
            self::AUTO_TIME => 'time_slot',
            default => 'always',
        };
    }

    public function feeTypeLabel(string $locale): string
    {
        $key = $this->isPercent()
            ? 'establishment::fields.service_fee_type_percentage'
            : 'establishment::fields.service_fee_type_amount';

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

    public function autoApplyLabel(string $locale): string
    {
        $key = match ($this->autoApplyKey()) {
            'dining' => 'establishment::fields.service_fee_auto_apply_dining',
            'guest_count' => 'establishment::fields.service_fee_auto_apply_guest_count',
            'payment_method' => 'establishment::fields.service_fee_auto_apply_payment',
            'time_slot' => 'establishment::fields.service_fee_auto_apply_time_slot',
            default => 'establishment::fields.service_fee_auto_apply_always',
        };

        return (string) trans($key, [], $locale);
    }
}
