<?php

namespace Modules\General\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Delivered synchronously so in-app alerts appear without a queue worker.
 */
class SystemAlertNotification extends Notification
{
    /**
     * @param  array{title: string, body: string, icon: string, url: ?string, module: string, severity: string, key: string, meta?: array<string, mixed>}  $payload
     * @param  list<string>  $channels
     */
    public function __construct(
        protected array $payload,
        protected array $channels = ['database']
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = (string) ($this->payload['title'] ?? '');
        $body = (string) ($this->payload['body'] ?? '');
        $url = $this->payload['url'] ?? null;

        $html = '<p style="font-size:16px;font-weight:700;color:#1e2129;margin:0 0 12px;">'
            .e($title)
            .'</p><p style="font-size:14px;color:#5e6278;line-height:1.6;margin:0;">'
            .nl2br(e($body))
            .'</p>';

        if ($url) {
            $html .= '<p style="margin-top:18px;"><a href="'.e($url).'" style="color:#b88816;font-weight:600;">'
                .e(__('general::notifications.open_in_system'))
                .'</a></p>';
        }

        return (new MailMessage)
            ->subject($title)
            ->view('general::mail.employee-created-mail', [
                'notifiable' => $notifiable,
                'body' => $html,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->payload['title'] ?? '',
            'body' => $this->payload['body'] ?? '',
            'icon' => $this->payload['icon'] ?? 'ki-notification',
            'url' => $this->payload['url'] ?? null,
            'module' => $this->payload['module'] ?? 'system',
            'severity' => $this->payload['severity'] ?? 'info',
            'key' => $this->payload['key'] ?? '',
            'meta' => $this->payload['meta'] ?? [],
        ];
    }
}
