<?php

namespace Modules\Employee\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Employee\Support\EmployeeAccess;
use Modules\Employee\Support\EmployeePermissions;

class TimeSheetRulePolicy
{
    use HandlesAuthorization;

    public function viewAny(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::TIME_SHEET_RULES_SHOW);
    }

    public function update(?Authenticatable $user): bool
    {
        return EmployeeAccess::allows($user, EmployeePermissions::TIME_SHEET_RULES_UPDATE);
    }
}
