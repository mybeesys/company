<?php

namespace Modules\Employee\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Employee\Support\EmployeeAccess;
use Modules\Employee\Support\EmployeePermissions;

class PayrollPolicy
{
    use HandlesAuthorization;

    public function viewAny(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::PAYROLLS_SHOW);
    }

    public function view(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::PAYROLLS_SHOW);
    }

    public function printAll(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::PAYROLLS_PRINT);
    }

    public function print(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::PAYROLL_PRINT);
    }

    public function create(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::PAYROLL_CREATE);
    }

    public function update(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::PAYROLL_CREATE);
    }

    public function delete(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::PAYROLLS_GROUP_DELETE);
    }
}
