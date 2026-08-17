<?php

declare(strict_types=1);

namespace Modules\Establishment\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Establishment\Models\Establishment;

trait HasEstablishmentAssignments
{
    abstract protected function assignmentPivotTable(): string;

    abstract protected function assignmentForeignPivotKey(): string;

    public function assignedEstablishments(): BelongsToMany
    {
        return $this->belongsToMany(
            Establishment::class,
            $this->assignmentPivotTable(),
            $this->assignmentForeignPivotKey(),
            'establishment_id'
        );
    }

    public function scopeForEstablishment(Builder $query, int $establishmentId): Builder
    {
        if ($establishmentId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($establishmentId) {
            $q->where($this->getTable().'.establishment_id', $establishmentId)
                ->orWhereHas('assignedEstablishments', function ($rel) use ($establishmentId) {
                    $rel->where('est_establishments.id', $establishmentId);
                });
        });
    }

    /**
     * @return list<int>
     */
    public function assignedEstablishmentIds(): array
    {
        $ids = $this->relationLoaded('assignedEstablishments')
            ? $this->assignedEstablishments->pluck('id')->all()
            : $this->assignedEstablishments()->pluck('est_establishments.id')->all();

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === [] && $this->establishment_id) {
            return [(int) $this->establishment_id];
        }

        return $ids;
    }

    /**
     * @param  list<int|string>|array<int, mixed>  $ids
     */
    public function syncAssignedEstablishments(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $id) => $id > 0)));
        $this->assignedEstablishments()->sync($ids);
        $this->establishment_id = $ids[0] ?? null;
        $this->save();
    }
}
