<?php

namespace Modules\Inventory\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\General\Models\NotificationSetting;
use Modules\Product\Models\Product;

class LowStockAmountNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Product $product, protected float $total_qty, protected float $threshold)
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
    public function toArray($notifiable): array
    {
        return [];
    }

    public function getInternalNotificationContent()
    {
        $notification_setting = NotificationSetting::where('type', 'low_stock_alert_notification')
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
        $subject = $getLocalizedContent(
            'low_stock_alert_notification_email_notification_subject_ar',
            'low_stock_alert_notification_email_notification_subject_en'
        );
        $subject = str_replace('{product_name_ar}', $this->product->name_ar, $subject);
        $subject = str_replace('{product_name_en}', $this->product->name_en, $subject);
        $subject = str_replace('{product_SKU}', $this->product->SKU, $subject);
        $subject = str_replace('{product_barcode}', $this->product->barcode, $subject);
        $subject = str_replace('{threshold}', $this->threshold, $subject);
        $subject = str_replace('{total_qty}', $this->total_qty, $subject);

        $body = $getLocalizedContent(
            'low_stock_alert_notification_email_notification_body_ar',
            'low_stock_alert_notification_email_notification_body_en'
        );
        $body = str_replace('{product_name_ar}', $this->product->name_ar, $body);
        $body = str_replace('{product_name_en}', $this->product->name_en, $body);
        $body = str_replace('{product_SKU}', $this->product->SKU, $body);
        $body = str_replace('{product_barcode}', $this->product->barcode, $body);
        $body = str_replace('{threshold}', $this->threshold, $body);
        $body = str_replace('{total_qty}', $this->total_qty, $body);
        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }
}
