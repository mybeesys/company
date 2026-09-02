<?php

namespace Modules\Employee\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Employee\Support\EmployeeAccess;
use Modules\Employee\Support\EmployeePermissions;

class PayrollAdjustmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::ALLOWANCES_SHOW);
    }

    public function create(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::ALLOWANCE_CREATE);
    }

    public function update(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::ALLOWANCE_UPDATE);
    }

    public function delete(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::ALLOWANCE_DELETE);
    }
}
