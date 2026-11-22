<?php

namespace Modules\General\Services\Notifications;

use Illuminate\Support\Collection;
use Modules\Employee\Models\Employee;

/**
 * Recipients: explicit employee IDs from settings when provided,
 * otherwise EMS permission holders for the catalog entry.
 */
final class NotificationRecipientResolver
{
    /**
     * @param  list<string>  $emsPermissions
     * @param  list<int>|null  $explicitEmployeeIds
     * @return Collection<int, Employee>
     */
    public function forInternal(
        array $emsPermissions,
        ?int $excludeUserId = null,
        bool $notifyActor = false,
        ?array $explicitEmployeeIds = null
    ): Collection {
        return $this->applyActorFilter(
            $this->resolvePool($emsPermissions, $explicitEmployeeIds),
            $excludeUserId,
            $notifyActor
        )->values();
    }

    /**
     * Critical email recipients who have an email address.
     *
     * @param  list<string>  $emsPermissions
     * @param  list<int>|null  $explicitEmployeeIds
     * @return Collection<int, Employee>
     */
    public function forCriticalMail(
        array $emsPermissions,
        ?int $excludeUserId = null,
        bool $notifyActor = false,
        ?array $explicitEmployeeIds = null
    ): Collection {
        return $this->applyActorFilter(
            $this->resolvePool($emsPermissions, $explicitEmployeeIds),
            $excludeUserId,
            $notifyActor
        )->filter(fn (Employee $e) => filled($e->email))->values();
    }

    /**
     * @param  list<string>  $emsPermissions
     * @param  list<int>|null  $explicitEmployeeIds
     * @return Collection<int, Employee>
     */
    protected function resolvePool(array $emsPermissions, ?array $explicitEmployeeIds): Collection
    {
        if (is_array($explicitEmployeeIds) && $explicitEmployeeIds !== []) {
            return Employee::query()
                ->whereNull('deleted_at')
                ->whereIn('id', $explicitEmployeeIds)
                ->get()
                ->values();
        }

        return $this->employeesWithAnyPermission($emsPermissions);
    }

    /**
     * @param  list<string>  $permissions
     * @return Collection<int, Employee>
     */
    protected function employeesWithAnyPermission(array $permissions): Collection
    {
        $permissions = array_values(array_filter($permissions));
        if ($permissions === []) {
            return collect();
        }

        return Employee::query()
            ->whereNull('deleted_at')
            ->get()
            ->filter(function (Employee $employee) use ($permissions) {
                foreach ($permissions as $permission) {
                    if ($employee->hasDashboardPermission($permission)) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return Collection<int, Employee>
     */
    protected function applyActorFilter(Collection $employees, ?int $excludeUserId, bool $notifyActor): Collection
    {
        if ($notifyActor || ! $excludeUserId) {
            return $employees;
        }

        return $employees->reject(fn (Employee $e) => (int) $e->id === (int) $excludeUserId)->values();
    }
}
