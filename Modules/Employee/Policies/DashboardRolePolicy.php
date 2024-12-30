<?php

namespace Modules\Employee\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class DashboardRolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.dashboard_roles.show');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.dashboard_role.show');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.dashboard_role.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.dashboard_role.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(): bool
    {
        return auth()->user()->hasDashboardPermission('employees.dashboard_role.delete');
    }
}
