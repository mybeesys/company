<?php

namespace Modules\General\Services\Notifications;

use Illuminate\Support\Collection;
use Modules\Employee\Models\Employee;

/**
 * Recipients are resolved from EMS permissions only.
 * Channel enablement does not depend on NotificationSetting toggles:
 * - Internal (in-app) is always on
 * - Email is always on for critical events
 */
final class NotificationRecipientResolver
{
    /**
     * @param  list<string>  $emsPermissions
     * @return Collection<int, Employee>
     */
    public function forInternal(array $emsPermissions, ?int $excludeUserId = null, bool $notifyActor = false): Collection
    {
        return $this->applyActorFilter(
            $this->employeesWithAnyPermission($emsPermissions),
            $excludeUserId,
            $notifyActor
        )->values();
    }

    /**
     * Critical email recipients: permission holders who have an email address.
     *
     * @param  list<string>  $emsPermissions
     * @return Collection<int, Employee>
     */
    public function forCriticalMail(array $emsPermissions, ?int $excludeUserId = null, bool $notifyActor = false): Collection
    {
        return $this->applyActorFilter(
            $this->employeesWithAnyPermission($emsPermissions),
            $excludeUserId,
            $notifyActor
        )->filter(fn (Employee $e) => filled($e->email))->values();
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
