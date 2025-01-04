<?php

namespace Modules\Employee\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollGroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.payrolls_groups.show');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.payrolls_group.show');
    }

    public function print()
    {
        return auth()->user()->hasDashboardPermission('employees.payrolls_groups.print');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.payrolls_group.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.payrolls_group.delete');
    }
}
