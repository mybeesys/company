<?php

namespace Modules\Establishment\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class EstablishmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(): bool
    {
        return auth()->user()->hasDashboardPermission('establishments.establishments.show');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(): bool
    {
        return auth()->user()->hasDashboardPermission('establishments.establishment.show');
    }

    public function print()
    {
        return auth()->user()->hasDashboardPermission('establishments.establishments.print');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        return auth()->user()->hasDashboardPermission('establishments.establishment.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(): bool
    {
        return auth()->user()->hasDashboardPermission('establishments.establishment.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(): bool
    {
        return auth()->user()->hasDashboardPermission('establishments.establishment.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(): bool
    {
        return auth()->user()->hasDashboardPermission('establishments.establishment.update');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(): bool
    {
        return auth()->user()->hasDashboardPermission('establishments.establishment.delete');
    }
}
