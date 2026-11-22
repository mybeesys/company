<?php

namespace Modules\General\Services\Notifications;

use Modules\General\Models\NotificationSetting;

/**
 * Per-catalog-key channel preferences:
 * - internal (bell / in-app)
 * - email
 * Optional shared recipient list (empty = EMS permission holders).
 */
final class NotificationPreferenceService
{
    public const SEND_INTERNAL = 'internal';

    public const SEND_EMAIL = 'email';

    /**
     * @return array<string, array{
     *   internal_active: bool,
     *   email_active: bool,
     *   recipient_ids: list<int>,
     *   definition: array<string, mixed>
     * }>
     */
    public function mapForCatalog(): array
    {
        $catalogKeys = array_keys(NotificationCatalog::all());

        $settings = NotificationSetting::query()
            ->with('notifiable:id')
            ->whereIn('sendType', [self::SEND_INTERNAL, self::SEND_EMAIL])
            ->whereIn('type', $catalogKeys)
            ->get()
            ->groupBy('type');

        $out = [];
        foreach (NotificationCatalog::all() as $key => $definition) {
            $group = $settings->get($key, collect());
            /** @var NotificationSetting|null $internal */
            $internal = $group->firstWhere('sendType', self::SEND_INTERNAL);
            /** @var NotificationSetting|null $email */
            $email = $group->firstWhere('sendType', self::SEND_EMAIL);

            $severity = (string) ($definition['severity'] ?? 'info');

            $out[$key] = [
                // No row yet = enabled (current product behavior).
                'internal_active' => $internal ? (bool) $internal->is_active : true,
                // Preserve prior behavior: critical emails on by default; others off until enabled.
                'email_active' => $email
                    ? (bool) $email->is_active
                    : ($severity === 'critical'),
                'recipient_ids' => $internal
                    ? $internal->notifiable->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
                    : [],
                'definition' => $definition,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, array{
     *   internal_active?: bool|int|string,
     *   email_active?: bool|int|string,
     *   recipients?: list<int|string>
     * }>  $alerts
     */
    public function save(array $alerts): void
    {
        $catalogKeys = array_keys(NotificationCatalog::all());

        foreach ($alerts as $key => $payload) {
            $key = (string) $key;
            if (! in_array($key, $catalogKeys, true)) {
                continue;
            }

            $internalActive = filter_var($payload['internal_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $emailActive = filter_var($payload['email_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $recipientIds = collect($payload['recipients'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $internal = $this->upsertChannel($key, self::SEND_INTERNAL, $internalActive);
            $this->upsertChannel($key, self::SEND_EMAIL, $emailActive);

            // Recipients live on the internal row (shared for both channels).
            $internal->notifiable()->sync($recipientIds);
        }
    }

    public function isInternalEnabled(string $key): bool
    {
        return $this->isChannelEnabled($key, self::SEND_INTERNAL, true);
    }

    public function isEmailEnabled(string $key): bool
    {
        $definition = NotificationCatalog::get($key);
        $default = (($definition['severity'] ?? '') === 'critical');

        return $this->isChannelEnabled($key, self::SEND_EMAIL, $default);
    }

    /**
     * @return list<int>|null  null = use permission resolver; list = explicit recipients
     */
    public function explicitRecipientIds(string $key): ?array
    {
        $setting = NotificationSetting::query()
            ->with('notifiable:id')
            ->where('type', $key)
            ->where('sendType', self::SEND_INTERNAL)
            ->first();

        if (! $setting) {
            return null;
        }

        $ids = $setting->notifiable->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        return $ids === [] ? null : $ids;
    }

    /**
     * @return array<string, list<array{key: string, definition: array<string, mixed>}>>
     */
    public function groupedCatalog(): array
    {
        $grouped = [];
        foreach (NotificationCatalog::all() as $key => $definition) {
            $module = (string) ($definition['module'] ?? 'system');
            $grouped[$module][] = [
                'key' => $key,
                'definition' => $definition,
            ];
        }

        return $grouped;
    }

    protected function isChannelEnabled(string $key, string $sendType, bool $defaultWhenMissing): bool
    {
        $setting = NotificationSetting::query()
            ->where('type', $key)
            ->where('sendType', $sendType)
            ->first();

        if (! $setting) {
            return $defaultWhenMissing;
        }

        return (bool) $setting->is_active;
    }

    protected function upsertChannel(string $key, string $sendType, bool $active): NotificationSetting
    {
        $setting = NotificationSetting::query()->firstOrNew([
            'type' => $key,
            'sendType' => $sendType,
        ]);
        $setting->is_active = $active;
        if (! $setting->exists && $setting->template === null) {
            $setting->template = [];
        }
        $setting->save();

        return $setting;
    }
}
