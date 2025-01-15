<?php

namespace Modules\General\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class StoreNotificationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['email', 'internal', 'sms'] as $type) {
            $key = "employee_to_receive_{$this->route()->notificationType}_{$type}_notification";
            if ($this->$key) {
                $this->merge([$key => explode(',', $this->$key)]);
            }
        }
    }

    public function rules(): array
    {
        $notificationType = $this->route()->notificationType;
        return [
            "{$notificationType}_internal_notification_active" => ['nullable', 'boolean'],
            "{$notificationType}_email_notification_active" => ['nullable', 'boolean'],
            "{$notificationType}_sms_notification_active" => ['nullable', 'boolean'],
            
            "{$notificationType}_internal_notification_title_ar" => ['required_if:{$notificationType}_internal_notification_active,1', 'nullable', 'string', 'max:100'],
            "{$notificationType}_internal_notification_title_en" => ['required_if:{$notificationType}_internal_notification_active,1', 'nullable', 'string', 'max:100'],
            "{$notificationType}_internal_notification_body_ar" => ['required_if:{$notificationType}_internal_notification_active,1', 'nullable', 'string', 'max:100'],
            "{$notificationType}_internal_notification_body_en" => ['required_if:{$notificationType}_internal_notification_active,1', 'nullable', 'string', 'max:100'],

            "employee_to_receive_{$notificationType}_internal_notification" => ['required_if:{$notificationType}_internal_notification_active,1', 'array', 'nullable'],
            "employee_to_receive_{$notificationType}_internal_notification.*" => ['exists:emp_employees,id'],

            "{$notificationType}_sms_notification_body_ar" => ['required_if:{$notificationType}_sms_notification_active,1', 'nullable', 'string', 'max:100'],
            "{$notificationType}_sms_notification_body_en" => ['required_if:{$notificationType}_sms_notification_active,1', 'nullable', 'string', 'max:100'],

            "employee_to_receive_{$notificationType}_sms_notification" => ['required_if:{$notificationType}_sms_notification_active,1', 'array', 'nullable'],
            "employee_to_receive_{$notificationType}_sms_notification.*" => ['exists:emp_employees,id'],

            "{$notificationType}_email_notification_subject_ar" => ['required_if:{$notificationType}_email_notification_active,1', 'nullable', 'string', 'max:100'],
            "{$notificationType}_email_notification_subject_en" => ['required_if:{$notificationType}_email_notification_active,1', 'nullable', 'string', 'max:100'],
            "{$notificationType}_email_notification_body_ar" => ['required_if:{$notificationType}_email_notification_active,1', 'nullable', 'string', 'max:1000'],
            "{$notificationType}_email_notification_body_en" => ['required_if:{$notificationType}_email_notification_active,1', 'nullable', 'string', 'max:1000'],
            "{$notificationType}_email_notification_bcc" => ['required_if:{$notificationType}_email_notification_active,1', 'nullable', 'string', 'max:100'],
            "{$notificationType}_email_notification_cc" => ['required_if:{$notificationType}_email_notification_active,1', 'nullable', 'string', 'max:100'],

            "employee_to_receive_{$notificationType}_email_notification" => ['required_if:{$notificationType}_email_notification_active,1', 'array', 'nullable'],
            "employee_to_receive_{$notificationType}_email_notification.*" => ['exists:emp_employees,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
