<?php

namespace Modules\Establishment\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Establishment\Support\EstablishmentAccess;
use Modules\Establishment\Support\EstablishmentPermissions;

class CompanyPolicy
{
    use HandlesAuthorization;

    public function viewAny(): bool
    {
        return EstablishmentAccess::can([
            EstablishmentPermissions::COMPANY_SHOW,
            \Modules\General\Support\SettingPermissions::GENERAL_SHOW,
        ]);
    }

    public function update(): bool
    {
        return EstablishmentAccess::can([
            EstablishmentPermissions::COMPANY_UPDATE,
            \Modules\General\Support\SettingPermissions::GENERAL_UPDATE,
        ]);
    }
}
