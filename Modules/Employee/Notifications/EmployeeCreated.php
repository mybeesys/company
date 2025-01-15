<?php

namespace Modules\Employee\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Employee\Models\Employee;
use Modules\General\Models\NotificationSetting;

class EmployeeCreated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Employee $employee)
    {

    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $notification_setting = NotificationSetting::where('type', 'created_emp')
        ->where('sendType', 'internal')
        ->first();

        return (new MailMessage)
            ->view('general::mail.employee-created-mail', ['notifiable' => $notifiable])
            ->subject('Hello');
    }

    /**
     * Get the array representation of the notification.
     */
    // public function toArray($notifiable): array
    // {
    //     return $this->getInternalNotificationContent();
    // }

    // public function getInternalNotificationContent()
    // {
    //     $notification_setting = NotificationSetting::where('type', 'employeeCreated')
    //         ->where('sendType', 'internal')
    //         ->firstOrFail();

    //     $getLocalizedContent = function ($arKey, $enKey) use ($notification_setting) {
    //         $locale = session('locale');
    //         $template = $notification_setting->template;

    //         return ($locale === 'ar' ? $template[$arKey] : $template[$enKey])
    //             ?? $template[$enKey]
    //             ?? $template[$arKey]
    //             ?? '';
    //     };

    //     $creatorName = $this->employee?->createdBy?->{get_name_by_lang()}
    //         ?? __('employee::general.admin');
    //     $creationDate = $this->employee?->created_at
    //         ? date_format($this->employee->created_at, 'm-d')
    //         : '';

    //     $creationTime = $this->employee?->created_at
    //         ? date_format($this->employee->created_at, 'H:i')
    //         : '';

    //     $title = $getLocalizedContent(
    //         'created_emp_internal_notification_title_ar',
    //         'created_emp_internal_notification_title_en'
    //     );
    //     $title = str_replace('{created_by}', $creatorName, $title);
    //     $title = str_replace('{created_date}', $creationDate, $title);
    //     $title = str_replace('{created_time}', $creationTime, $title);
    //     $title = str_replace('{employee_name}', $this->employee->{get_name_by_lang()}, $title);

    //     $body = $getLocalizedContent(
    //         'created_emp_internal_notification_body_ar',
    //         'created_emp_internal_notification_body_en'
    //     );
    //     $body = str_replace('{created_by}', $creatorName, $body);
    //     $body = str_replace('{created_date}', $creationDate, $body);
    //     $body = str_replace('{created_time}', $creationTime, $body);
    //     $body = str_replace('{employee_name}', $this->employee->{get_name_by_lang()}, $body);

    //     return [
    //         'title' => $title,
    //         'body' => $body,
    //         'icon' => 'ki-user-square'
    //     ];
    // }
}
