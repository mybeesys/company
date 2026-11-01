<?php

namespace Modules\General\Services\Notifications;

use Illuminate\Support\Collection;
use Modules\Employee\Models\Employee;
use Modules\General\Models\NotificationSetting;

final class NotificationRecipientResolver
{
    /**
     * Internal inbox recipients: EMS permission holders ∪ explicit setting recipients.
     *
     * @param  list<string>  $emsPermissions
     * @return Collection<int, Employee>
     */
    public function forInternal(string $settingType, array $emsPermissions, ?int $excludeUserId = null, bool $notifyActor = false): Collection
    {
        $byPermission = $this->employeesWithAnyPermission($emsPermissions);
        $bySetting = $this->settingRecipients($settingType, 'internal');

        $merged = $byPermission->concat($bySetting)->unique('id');

        return $this->applyActorFilter($merged, $excludeUserId, $notifyActor)
            ->filter(fn (Employee $e) => filled($e->id))
            ->values();
    }

    /**
     * Critical email recipients: explicit email setting list if present, else permission holders.
     * If email setting exists and is inactive → empty (skip mail).
     *
     * @param  list<string>  $emsPermissions
     * @return Collection<int, Employee>
     */
    public function forCriticalMail(string $settingType, array $emsPermissions, ?int $excludeUserId = null, bool $notifyActor = false): Collection
    {
        $emailSetting = NotificationSetting::query()
            ->where('type', $settingType)
            ->where('sendType', 'email')
            ->first();

        if ($emailSetting && ! $emailSetting->is_active) {
            return collect();
        }

        if ($emailSetting && $emailSetting->is_active) {
            $recipients = $this->settingRecipients($settingType, 'email');
            if ($recipients->isNotEmpty()) {
                return $this->applyActorFilter($recipients, $excludeUserId, $notifyActor)
                    ->filter(fn (Employee $e) => filled($e->email))
                    ->values();
            }
        }

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
     * @return Collection<int, Employee>
     */
    protected function settingRecipients(string $settingType, string $sendType): Collection
    {
        if ($settingType === '') {
            return collect();
        }

        $setting = NotificationSetting::query()
            ->where('type', $settingType)
            ->where('sendType', $sendType)
            ->first();

        if (! $setting || ! $setting->is_active) {
            // For internal: missing setting still allows permission-based delivery (caller merges).
            // Explicit empty inactive means no setting recipients.
            if ($sendType === 'internal' && ! $setting) {
                return collect();
            }
            if ($setting && ! $setting->is_active) {
                return collect();
            }

            return collect();
        }

        $setting->loadMissing('notifiable');

        return $setting->notifiable
            ->filter(fn ($e) => $e instanceof Employee)
            ->values();
    }

    /**
     * When internal setting exists and is explicitly inactive, block DB delivery entirely.
     */
    public function isInternalChannelEnabled(?string $settingType): bool
    {
        if (! $settingType) {
            return true;
        }

        $setting = NotificationSetting::query()
            ->where('type', $settingType)
            ->where('sendType', 'internal')
            ->first();

        if (! $setting) {
            return true;
        }

        return (bool) $setting->is_active;
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
