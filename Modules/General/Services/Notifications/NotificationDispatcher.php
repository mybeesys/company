<?php

namespace Modules\General\Services\Notifications;

use Illuminate\Support\Facades\Log;
use Modules\General\Models\NotificationSetting;
use Modules\General\Notifications\SystemAlertNotification;

final class NotificationDispatcher
{
    public function __construct(
        protected NotificationRecipientResolver $recipients,
        protected NotificationRealtimePublisher $realtime,
    ) {}

    /**
     * @param  array<string, scalar|null>  $vars  Placeholder map for {key} replacement
     */
    public function dispatch(
        string $key,
        array $vars = [],
        ?string $actionUrl = null,
        ?int $actorUserId = null,
        array $meta = []
    ): void {
        $definition = NotificationCatalog::get($key);
        if (! $definition) {
            Log::warning("Unknown notification key: {$key}");

            return;
        }

        $settingType = (string) ($definition['setting_type'] ?? $key);
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $title = $this->resolveText($definition, $settingType, 'title', $locale, $vars);
        $body = $this->resolveText($definition, $settingType, 'body', $locale, $vars);

        $payload = [
            'title' => $title,
            'body' => $body,
            'icon' => $definition['icon'] ?? 'ki-notification',
            'url' => $actionUrl,
            'module' => $definition['module'] ?? 'system',
            'severity' => $definition['severity'] ?? 'info',
            'key' => $key,
            'meta' => $meta,
        ];

        $excludeId = $actorUserId ?? (auth()->id() ? (int) auth()->id() : null);
        $notifyActor = (bool) ($definition['notify_actor'] ?? false);
        $permissions = $definition['ems_permissions'] ?? [];

        $notifiedIds = [];

        // Internal inbox is always enabled (not gated by NotificationSetting toggles).
        $internalRecipients = $this->recipients->forInternal(
            $permissions,
            $excludeId,
            $notifyActor
        );

        foreach ($internalRecipients as $employee) {
            $employee->notify(new SystemAlertNotification($payload, ['database']));
            $notifiedIds[] = (int) $employee->id;
        }

        // Critical events always email permission holders (settings only customize copy).
        if (($definition['severity'] ?? '') === 'critical') {
            $mailRecipients = $this->recipients->forCriticalMail(
                $permissions,
                $excludeId,
                $notifyActor
            );

            foreach ($mailRecipients as $employee) {
                $employee->notify(new SystemAlertNotification($payload, ['mail']));
            }
        }

        if ($notifiedIds !== []) {
            $this->realtime->publishBadgeBump($notifiedIds, [
                'unread_hint' => true,
                'key' => $key,
                'title' => $title,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, scalar|null>  $vars
     */
    protected function resolveText(array $definition, string $settingType, string $field, string $locale, array $vars): string
    {
        $sendType = $field === 'title' || $field === 'body' ? 'internal' : 'internal';
        $template = $this->templateFromSettings($settingType, 'internal', $field, $locale)
            ?? $this->templateFromSettings($settingType, 'email', $field === 'title' ? 'subject' : 'body', $locale)
            ?? (string) ($definition[$field][$locale] ?? $definition[$field]['en'] ?? $definition[$field]['ar'] ?? '');

        return $this->interpolate($template, $vars);
    }

    protected function templateFromSettings(string $settingType, string $sendType, string $field, string $locale): ?string
    {
        if ($settingType === '') {
            return null;
        }

        $setting = NotificationSetting::query()
            ->where('type', $settingType)
            ->where('sendType', $sendType)
            ->first();

        if (! $setting || ! is_array($setting->template)) {
            return null;
        }

        $tpl = $setting->template;
        $suffix = $sendType === 'email'
            ? ($field === 'subject' || $field === 'title' ? 'subject' : 'body')
            : $field;

        $keyAr = "{$settingType}_{$sendType}_notification_{$suffix}_ar";
        $keyEn = "{$settingType}_{$sendType}_notification_{$suffix}_en";

        // Internal cards use title_ar / body_ar naming from settings UI.
        if ($sendType === 'internal') {
            $keyAr = "{$settingType}_internal_notification_{$field}_ar";
            $keyEn = "{$settingType}_internal_notification_{$field}_en";
        }

        $value = $locale === 'ar'
            ? ($tpl[$keyAr] ?? $tpl[$keyEn] ?? null)
            : ($tpl[$keyEn] ?? $tpl[$keyAr] ?? null);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, scalar|null>  $vars
     */
    protected function interpolate(string $template, array $vars): string
    {
        $replacements = [];
        foreach ($vars as $key => $value) {
            $replacements['{'.$key.'}'] = (string) ($value ?? '');
        }

        // Common aliases
        if (isset($vars['product_name_ar']) || isset($vars['product_name_en'])) {
            $replacements['{product_name}'] = (string) (
                app()->getLocale() === 'ar'
                    ? ($vars['product_name_ar'] ?? $vars['product_name_en'] ?? $vars['product_name'] ?? '')
                    : ($vars['product_name_en'] ?? $vars['product_name_ar'] ?? $vars['product_name'] ?? '')
            );
        }

        return strtr($template, $replacements);
    }
}
