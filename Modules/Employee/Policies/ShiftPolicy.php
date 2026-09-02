<?php

namespace Modules\Employee\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Employee\Support\EmployeeAccess;
use Modules\Employee\Support\EmployeePermissions;

class ShiftPolicy
{
    use HandlesAuthorization;

    public function viewAny(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::SHIFTS_SHOW);
    }

    public function print(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::SHIFTS_PRINT);
    }

    public function update(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::SHIFTS_UPDATE);
    }
}
