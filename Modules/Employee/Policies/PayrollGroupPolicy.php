<?php

namespace Modules\Employee\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Employee\Support\EmployeeAccess;
use Modules\Employee\Support\EmployeePermissions;

class PayrollGroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::PAYROLLS_GROUPS_SHOW);
    }

    public function view(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::PAYROLLS_GROUP_SHOW);
    }

    public function print(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::PAYROLLS_GROUPS_PRINT);
    }

    public function printAll(?Authenticatable $user): bool
    {
        return $this->print($user);
    }

    public function update(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::PAYROLLS_GROUP_UPDATE);
    }

    public function delete(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::PAYROLLS_GROUP_DELETE);
    }
}
