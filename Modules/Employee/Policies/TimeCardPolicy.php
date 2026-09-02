<?php

namespace Modules\Employee\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Employee\Support\EmployeeAccess;
use Modules\Employee\Support\EmployeePermissions;

class TimeCardPolicy
{
    use HandlesAuthorization;

    public function viewAny(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::TIMECARDS_SHOW);
    }

    public function view(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::TIMECARDS_SHOW);
    }

    public function print(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, [EmployeePermissions::TIMECARDS_PRINT, EmployeePermissions::TIMECARD_PRINT]);
    }

    public function create(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::TIMECARD_CREATE);
    }

    public function update(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::TIMECARD_UPDATE);
    }

    public function delete(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::TIMECARD_DELETE);
    }
}
