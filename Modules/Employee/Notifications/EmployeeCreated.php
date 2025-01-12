<?php

namespace Modules\Employee\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Employee\Models\Employee;

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
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail($notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->line('The introduction to the notification.')
    //         ->action('Notification Action', 'https://laravel.com')
    //         ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => __('employee::general.new_employee_created'),
            'body' => __('employee::general.employee_created_by', ['employee_name' => $this->employee->{get_name_by_lang()}, 'admin' => $this->employee?->createdBy?->{get_name_by_lang()} ?? __('employee::general.admin')]),
            'icon' => 'ki-user-square'
        ];
    }
}
