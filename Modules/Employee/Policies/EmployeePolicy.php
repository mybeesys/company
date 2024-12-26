<?php

namespace Modules\Employee\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
class EmployeePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.employees.show');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.employee.show');
    }

    public function print()
    {
        return auth()->user()->hasDashboardPermission('employees.employees.print');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.employee.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.employee.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.employee.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.employee.edit');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.employee.delete');
    }
}
