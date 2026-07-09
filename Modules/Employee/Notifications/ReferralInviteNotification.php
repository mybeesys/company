<?php

namespace Modules\Employee\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralInviteNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $referrerName,
        protected string $promotionalText,
        protected string $inviteUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('employee::referrals.email_subject', ['name' => $this->referrerName]))
            ->view('employee::mail.referral-invite', [
                'referrerName' => $this->referrerName,
                'promotionalText' => $this->promotionalText,
                'inviteUrl' => $this->inviteUrl,
            ]);
    }
}
