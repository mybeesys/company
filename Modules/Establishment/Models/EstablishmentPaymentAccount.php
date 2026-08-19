<?php

namespace Modules\Establishment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Establishment\Models\Concerns\HasEstablishmentAssignments;

class EstablishmentPaymentAccount extends Model
{
    use HasEstablishmentAssignments;

    protected $table = 'est_establishment_payment_accounts';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function assignmentPivotTable(): string
    {
        return 'est_payment_account_establishment';
    }

    protected function assignmentForeignPivotKey(): string
    {
        return 'payment_account_id';
    }

    public function assignedEstablishments(): BelongsToMany
    {
        return $this->belongsToMany(
            Establishment::class,
            $this->assignmentPivotTable(),
            $this->assignmentForeignPivotKey(),
            'establishment_id'
        )->withPivot('account_id')->withTimestamps();
    }

    public function accountIdForEstablishment(int $establishmentId): ?int
    {
        if ($establishmentId <= 0) {
            return $this->account_id ? (int) $this->account_id : null;
        }

        $assigned = $this->relationLoaded('assignedEstablishments')
            ? $this->assignedEstablishments->firstWhere('id', $establishmentId)
            : $this->assignedEstablishments()->where('est_establishments.id', $establishmentId)->first();

        $pivotAccountId = (int) ($assigned?->pivot?->account_id ?? 0);
        if ($pivotAccountId > 0) {
            return $pivotAccountId;
        }

        return $this->account_id ? (int) $this->account_id : null;
    }

    /**
     * @param  list<int|string>|array<int, mixed>  $ids
     */
    public function syncAssignedEstablishments(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $id) => $id > 0)));
        $existing = $this->assignedEstablishments()->get()->keyBy('id');
        $sync = [];
        foreach ($ids as $id) {
            $sync[$id] = [
                'account_id' => (int) ($existing->get($id)?->pivot?->account_id ?: $this->account_id ?: 0) ?: null,
            ];
        }
        $this->assignedEstablishments()->sync($sync);
        $this->establishment_id = $ids[0] ?? null;
        $this->save();
    }

    /**
     * @param  array<int, int|string|null>  $branchAccounts  establishment_id => account_id
     */
    public function syncBranchAccounts(array $branchAccounts): void
    {
        $sync = [];
        foreach ($branchAccounts as $establishmentId => $accountId) {
            $estId = (int) $establishmentId;
            $accId = (int) $accountId;
            if ($estId <= 0 || $accId <= 0) {
                continue;
            }
            $sync[$estId] = ['account_id' => $accId];
        }

        $this->assignedEstablishments()->sync($sync);
        $ids = array_keys($sync);
        $this->establishment_id = $ids[0] ?? null;
        $this->account_id = $ids !== [] ? $sync[$ids[0]]['account_id'] : $this->account_id;
        $this->save();
    }

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'account_id');
    }

    public function fees(): HasMany
    {
        return $this->hasMany(PaymentMethodFee::class, 'payment_method_id')->orderBy('sort_order')->orderBy('id');
    }

    public function activeFees(): HasMany
    {
        return $this->hasMany(PaymentMethodFee::class, 'payment_method_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
