<?php

namespace Modules\Employee\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Employee\Support\EmployeeAccess;
use Modules\Employee\Support\EmployeePermissions;

class PosRolePolicy
{
    use HandlesAuthorization;

    public function viewAny(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::POS_ROLES_SHOW);
    }

    public function view(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::POS_ROLE_SHOW);
    }

    public function create(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::POS_ROLE_CREATE);
    }

    public function update(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::POS_ROLE_UPDATE);
    }

    public function delete(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::POS_ROLE_DELETE);
    }
}
