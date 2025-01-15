<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\General\Http\Requests\StoreNotificationRequest;
use Modules\General\Models\NotificationSetting;
use Modules\General\Models\NotificationSettingParameter;

class NotificationSettingController extends Controller
{
    public function storeNotificationsSettings(StoreNotificationRequest $request, string $notificationType)
    {
        $notificationKeys = [
            'email' => [
                'fields' => [
                    "{$notificationType}_email_notification_title_ar",
                    "{$notificationType}_email_notification_title_en",
                    "{$notificationType}_email_notification_body_ar",
                    "{$notificationType}_email_notification_body_en",
                    "{$notificationType}_email_notification_bcc",
                    "{$notificationType}_email_notification_cc",
                ],
                'is_active' => "{$notificationType}_email_notification_active",
                'recipient' => "employee_to_receive_{$notificationType}_email_notification",
            ],
            'sms' => [
                'fields' => [
                    "{$notificationType}_sms_notification_body_ar",
                    "{$notificationType}_sms_notification_body_en",
                ],
                'is_active' => "{$notificationType}_sms_notification_active",
                'recipient' => "employee_to_receive_{$notificationType}_sms_notification",
            ],
            'internal' => [
                'fields' => [
                    "{$notificationType}_internal_notification_title_ar",
                    "{$notificationType}_internal_notification_title_en",
                    "{$notificationType}_internal_notification_body_ar",
                    "{$notificationType}_internal_notification_body_en",
                ],
                'is_active' => "{$notificationType}_internal_notification_active",
                'recipient' => "employee_to_receive_{$notificationType}_internal_notification",
            ],
        ];

        foreach ($notificationKeys as $sendType => $details) {
            $template = [];
            foreach ($details['fields'] as $field) {
                $template[$field] = $request->validated($field);
            }

            $data = [
                'template' => $template,
                'is_active' => $request->validated($details['is_active']),
            ];

            $notificationSetting = NotificationSetting::updateOrCreate(
                ['type' => $notificationType, 'sendType' => $sendType],
                $data
            );

            if ($request->validated($details['recipient'])) {
                $notificationSetting->notifiable()->sync($request->validated($details['recipient']));
            }
        }

        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    public function storeNotificationSettingsParameters(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'array'],
            'key.*' => ['string'],
            'key.index' => ['string']
        ]);
        foreach ($validated['key'] as $index => $value) {
            NotificationSettingParameter::updateOrCreate(['key' => $index], ['value' => $value]);
        }
        return response()->json(['message' => __('employee::responses.operation_success')]);
    }
}
