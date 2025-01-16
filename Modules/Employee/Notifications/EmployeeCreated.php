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
    public function __construct(protected Employee $employee, protected string $password = "__('messages.in_active')")
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
        $mail = $this->getInternalNotificationContent();

        return (new MailMessage)
            ->view('general::mail.employee-created-mail', ['notifiable' => $notifiable, 'body' => $mail['body']])
            ->subject($mail['subject']);
    }

    /**
     * Get the array representation of the notification.
     */
    // public function toArray($notifiable): array
    // {
    //     return $this->getInternalNotificationContent();
    // }

    public function getInternalNotificationContent()
    {
        $notification_setting = NotificationSetting::where('type', 'created_emp')
            ->where('sendType', 'email')
            ->first();

        $getLocalizedContent = function ($arKey, $enKey) use ($notification_setting) {
            $locale = session('locale');
            $template = $notification_setting->template;

            return ($locale === 'ar' ? $template[$arKey] : $template[$enKey])
                ?? $template[$enKey]
                ?? $template[$arKey]
                ?? '';
        };

        $creatorName = $this->employee?->createdBy?->{get_name_by_lang()}
            ?? __('employee::general.admin');
        $creationDate = $this->employee?->created_at
            ? date_format($this->employee->created_at, 'm-d')
            : '';

        $creationTime = $this->employee?->created_at
            ? date_format($this->employee->created_at, 'H:i')
            : '';


        $subject = $getLocalizedContent(
            'created_emp_email_notification_subject_ar',
            'created_emp_email_notification_subject_en'
        );
        $subject = str_replace('{created_by}', $creatorName, $subject);
        $subject = str_replace('{created_date}', $creationDate, $subject);
        $subject = str_replace('{created_time}', $creationTime, $subject);
        $subject = str_replace('{employee_name}', $this->employee->{get_name_by_lang()}, $subject);
        $subject = str_replace('{employee_username}', $this->employee?->user_name ?? __('messages.in_active'), $subject);
        $subject = str_replace('{employee_password}', $this->password, $subject);
        $subject = str_replace('{employee_pin}', $this->employee->pin, $subject);
        $subject = str_replace('{employee_total_wage}', ($this->employee->wage->rate ?? 0) + ($this->employee->allowances()?->always()?->sum('amount') ?? 0), $subject);

        $body = $getLocalizedContent(
            'created_emp_email_notification_body_ar',
            'created_emp_email_notification_body_en'
        );
        $body = str_replace('{created_by}', $creatorName, $body);
        $body = str_replace('{created_date}', $creationDate, $body);
        $body = str_replace('{created_time}', $creationTime, $body);
        $body = str_replace('{employee_name}', $this->employee->{get_name_by_lang()}, $body);
        $body = str_replace('{employee_username}', $this->employee?->user_name ?? __('messages.in_active'), $body);
        $body = str_replace('{employee_password}', $this->password, $body);
        $body = str_replace('{employee_pin}', $this->employee->pin, $body);
        $body = str_replace('{employee_total_wage}', ($this->employee->wage->rate ?? 0) + ($this->employee->allowances()?->always()?->sum('amount') ?? 0), $body);


        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }
}
