<?php

namespace Modules\Establishment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablishmentServiceFee extends Model
{
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
}
