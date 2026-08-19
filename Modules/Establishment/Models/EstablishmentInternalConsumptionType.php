<?php

namespace Modules\Establishment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Establishment\Models\Concerns\HasEstablishmentAssignments;
use Modules\General\Models\Transaction;

class EstablishmentInternalConsumptionType extends Model
{
    use HasEstablishmentAssignments;

    public const VALUE_TYPE_COST = 'cost';

    public const VALUE_TYPE_PERCENT = 'percent';

    public const VALUE_TYPE_FIXED = 'fixed';

    protected $table = 'est_establishment_internal_consumption_types';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function assignmentPivotTable(): string
    {
        return 'est_internal_consumption_type_establishment';
    }

    protected function assignmentForeignPivotKey(): string
    {
        return 'internal_consumption_type_id';
    }

    protected $casts = [
        'value' => 'float',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'account_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'internal_consumption_type_id');
    }

    public function displayName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return $locale === 'ar'
            ? (string) ($this->name_ar ?: $this->name_en)
            : (string) ($this->name_en ?: $this->name_ar);
    }

    public function normalizedValueType(): string
    {
        $valueType = strtolower(trim((string) ($this->value_type ?: self::VALUE_TYPE_COST)));

        return in_array($valueType, [self::VALUE_TYPE_COST, self::VALUE_TYPE_PERCENT, self::VALUE_TYPE_FIXED], true)
            ? $valueType
            : self::VALUE_TYPE_COST;
    }

    public function valueTypeLabel(string $locale): string
    {
        $key = 'establishment::fields.internal_consumption_value_type_'.$this->normalizedValueType();

        return (string) trans($key, [], $locale);
    }

    public function calculationHint(string $locale): string
    {
        $valueType = $this->normalizedValueType();
        $key = 'establishment::fields.internal_consumption_calc_'.$valueType;
        $value = $this->value !== null ? rtrim(rtrim(number_format((float) $this->value, 4, '.', ''), '0'), '.') : '0';

        return (string) trans($key, ['value' => $value], $locale);
    }
}
