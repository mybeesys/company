<?php

namespace Modules\Screen\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Modules\Establishment\Models\Establishment;
use Modules\Screen\Models\Device;

class ScreenEstablishmentService
{
    /**
     * الفروع المتاحة لقوائم التشغيل: الفروع النشطة غير الرئيسية + أي فرع مربوط بجهاز شاشة.
     */
    public function queryForPlaylists(): Builder
    {
        $deviceEstablishmentIds = $this->deviceEstablishmentIds();

        return Establishment::query()
            ->active()
            ->where(function (Builder $query) use ($deviceEstablishmentIds) {
                $query->where('is_main', false);

                if ($deviceEstablishmentIds !== []) {
                    $query->orWhereIn('id', $deviceEstablishmentIds);
                }
            })
            ->orderBy('name');
    }

    /**
     * @return list<int>
     */
    public function idsForPlaylists(): array
    {
        return $this->queryForPlaylists()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function listForPlaylists(): Collection
    {
        return $this->queryForPlaylists()->get(['id', 'name']);
    }

    public function allSelectableChosen(array $establishmentIds): bool
    {
        $selected = array_values(array_unique(array_filter(array_map('intval', $establishmentIds))));
        $all = $this->idsForPlaylists();

        if ($all === [] || $selected === []) {
            return false;
        }

        sort($selected);
        sort($all);

        return $selected === $all;
    }

    /**
     * @return list<int>
     */
    public function resolvePlaylistEstablishmentIds(array $deviceIds, array $selectedEstablishmentIds): array
    {
        $selected = array_values(array_unique(array_filter(array_map('intval', $selectedEstablishmentIds))));

        if (! Schema::hasColumn('screen_devices', 'establishment_id')) {
            return $selected;
        }

        $fromDevices = Device::query()
            ->whereIn('id', array_values(array_unique(array_filter(array_map('intval', $deviceIds)))))
            ->whereNotNull('establishment_id')
            ->pluck('establishment_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return array_values(array_unique(array_merge($selected, $fromDevices)));
    }

    /**
     * @return list<int>
     */
    protected function deviceEstablishmentIds(): array
    {
        if (! Schema::hasColumn('screen_devices', 'establishment_id')) {
            return [];
        }

        return Device::query()
            ->whereNotNull('establishment_id')
            ->distinct()
            ->pluck('establishment_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
