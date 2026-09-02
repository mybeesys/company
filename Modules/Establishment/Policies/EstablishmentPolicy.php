<?php

namespace Modules\Establishment\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Establishment\Support\EstablishmentAccess;
use Modules\Establishment\Support\EstablishmentPermissions;

class EstablishmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(): bool
    {
        return EstablishmentAccess::can([
            EstablishmentPermissions::ESTABLISHMENTS_SHOW,
            EstablishmentPermissions::ESTABLISHMENT_SHOW,
        ]);
    }

    public function view(): bool
    {
        return EstablishmentAccess::can(EstablishmentPermissions::ESTABLISHMENT_SHOW);
    }

    public function print(): bool
    {
        return EstablishmentAccess::can(EstablishmentPermissions::ALL_PRINT);
    }

    public function create(): bool
    {
        return EstablishmentAccess::can(EstablishmentPermissions::ESTABLISHMENT_CREATE);
    }

    public function update(): bool
    {
        return EstablishmentAccess::can(EstablishmentPermissions::ESTABLISHMENT_UPDATE);
    }

    public function delete(): bool
    {
        return EstablishmentAccess::can(EstablishmentPermissions::ESTABLISHMENT_DELETE);
    }

    public function restore(): bool
    {
        return EstablishmentAccess::can(EstablishmentPermissions::ESTABLISHMENT_UPDATE);
    }

    public function forceDelete(): bool
    {
        return EstablishmentAccess::can(EstablishmentPermissions::ESTABLISHMENT_DELETE);
    }
}
