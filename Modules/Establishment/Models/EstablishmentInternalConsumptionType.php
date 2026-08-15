<?php

namespace Modules\Establishment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Accounting\Models\AccountingAccount;
use Modules\General\Models\Transaction;

class EstablishmentInternalConsumptionType extends Model
{
    public const VALUE_TYPE_COST = 'cost';

    public const VALUE_TYPE_PERCENT = 'percent';

    public const VALUE_TYPE_FIXED = 'fixed';

    protected $table = 'est_establishment_internal_consumption_types';

    protected $guarded = ['id', 'created_at', 'updated_at'];

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
}
