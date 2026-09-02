<?php

namespace Modules\Employee\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Employee\Support\EmployeeAccess;
use Modules\Employee\Support\EmployeePermissions;

class EmployeePolicy
{
    use HandlesAuthorization;

    public function viewAny(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::EMPLOYEES_SHOW);
    }

    public function view(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::EMPLOYEE_SHOW);
    }

    public function printAll(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::EMPLOYEES_PRINT);
    }

    public function print(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::EMPLOYEE_PRINT);
    }

    public function create(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::EMPLOYEE_CREATE);
    }

    public function update(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::EMPLOYEE_UPDATE);
    }

    public function delete(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::EMPLOYEE_DELETE);
    }

    public function restore(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::EMPLOYEE_UPDATE);
    }

    public function forceDelete(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::EMPLOYEE_DELETE);
    }
}
